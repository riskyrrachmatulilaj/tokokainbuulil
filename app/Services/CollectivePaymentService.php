<?php

namespace App\Services;

use App\Models\CollectivePayment;
use App\Models\Customer;
use App\Models\Debt;
use App\Models\PaymentHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CollectivePaymentService
{
    public function __construct(
        private readonly TransactionNumberService $numberService,
    ) {
    }

    /**
     * Memproses pembayaran kolektif dengan aturan FIFO:
     * nota dengan tanggal hutang paling tua dilunasi lebih dahulu.
     *
     * Seluruh proses berjalan dalam satu database transaction.
     *
     * @return array{collectivePayment: CollectivePayment, allocations: array}
     */
    public function process(array $data, ?User $user = null): array
    {
        $amount = round((float) ($data['amount'] ?? 0), 2);

        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'Nominal pembayaran harus lebih dari 0.',
            ]);
        }

        $customer = Customer::findOrFail($data['customer_id']);

        $debts = $customer->debts()
            ->where('status', Debt::STATUS_UNPAID)
            ->orderBy('debt_date')
            ->orderBy('id')
            ->get();

        $totalRemaining = $debts->sum(fn (Debt $debt) => (float) $debt->remaining_amount);

        if ($totalRemaining <= 0) {
            throw ValidationException::withMessages([
                'customer_id' => 'Supplier ini tidak memiliki hutang yang belum lunas.',
            ]);
        }

        if ($amount > $totalRemaining) {
            throw ValidationException::withMessages([
                'amount' => 'Pembayaran melebihi total sisa hutang supplier ('.number_format($totalRemaining, 2).').',
            ]);
        }

        return DB::transaction(function () use ($data, $amount, $customer, $debts, $user) {
            $collectivePayment = CollectivePayment::create([
                'transaction_number' => $this->numberService->nextTransactionNumber(),
                'customer_id' => $customer->id,
                'amount' => $amount,
                'payment_date' => $data['payment_date'] ?? today(),
                'description' => $data['description'] ?? null,
                'created_by' => $user?->id,
            ]);

            $transactionNumber = $collectivePayment->transaction_number;
            $allocations = [];

            $remainingPayment = $amount;

            foreach ($debts as $debt) {
                if ($remainingPayment <= 0) {
                    break;
                }

                $debt = Debt::whereKey($debt->id)->lockForUpdate()->first();

                if (! $debt || $debt->status === Debt::STATUS_PAID) {
                    continue;
                }

                $debtRemaining = (float) $debt->remaining_amount;

                if ($debtRemaining <= 0) {
                    continue;
                }

                $allocation = min($debtRemaining, $remainingPayment);

                $newPaid = round((float) $debt->paid_amount + $allocation, 2);
                $newRemaining = round((float) $debt->amount - $newPaid, 2);

                $debt->update([
                    'paid_amount' => $newPaid,
                    'remaining_amount' => max(0, $newRemaining),
                    'status' => $newRemaining <= 0 ? Debt::STATUS_PAID : Debt::STATUS_UNPAID,
                ]);

                PaymentHistory::create([
                    'transaction_number' => $transactionNumber,
                    'customer_id' => $customer->id,
                    'debt_id' => $debt->id,
                    'installment_id' => null,
                    'collective_payment_id' => $collectivePayment->id,
                    'payment_type' => PaymentHistory::TYPE_COLLECTIVE,
                    'amount' => $allocation,
                    'payment_date' => $collectivePayment->payment_date,
                    'description' => $data['description'] ?? null,
                    'created_by' => $user?->id,
                ]);

                $allocations[] = [
                    'debt_id' => $debt->id,
                    'invoice_number' => $debt->invoice_number,
                    'amount' => $allocation,
                    'remaining' => max(0, $newRemaining),
                    'status' => $debt->status,
                ];

                $remainingPayment = round($remainingPayment - $allocation, 2);
            }

            app(\App\Services\ActivityLogService::class)->log(
                'Hutang',
                'pay',
                "Memproses pembayaran kolektif hutang toko {$collectivePayment->transaction_number} (Pemasok: {$customer->name}) sebesar Rp " . number_format($amount, 0, ',', '.') . " untuk " . count($allocations) . " nota",
                $collectivePayment,
                ['amount' => $amount, 'customer' => $customer->name, 'allocations_count' => count($allocations)],
                $user
            );

            return [
                'collectivePayment' => $collectivePayment->fresh()->load('customer'),
                'allocations' => $allocations,
            ];
        });
    }

    /**
     * Membatalkan (menghapus) pembayaran kolektif.
     * Mengembalikan sisa hutang pada setiap nota yang terpengaruh,
     * menghapus riwayat pembayaran terkait, lalu menghapus record pembayaran kolektif.
     */
    public function reversePayment(CollectivePayment $collectivePayment): void
    {
        DB::transaction(function () use ($collectivePayment) {
            $histories = $collectivePayment->history()->get();

            foreach ($histories as $history) {
                $debt = Debt::whereKey($history->debt_id)->lockForUpdate()->first();

                if ($debt) {
                    $newPaid = round((float) $debt->paid_amount - (float) $history->amount, 2);
                    $newRemaining = round((float) $debt->amount - $newPaid, 2);

                    $debt->update([
                        'paid_amount' => max(0, $newPaid),
                        'remaining_amount' => max(0, $newRemaining),
                        'status' => $newRemaining > 0 ? Debt::STATUS_UNPAID : Debt::STATUS_PAID,
                    ]);
                }

                $history->delete();
            }

            app(\App\Services\ActivityLogService::class)->log(
                'Hutang',
                'cancel',
                "Membatalkan pembayaran kolektif hutang toko {$collectivePayment->transaction_number} (Pemasok: {$collectivePayment->customer->name}) sebesar Rp " . number_format((float)$collectivePayment->amount, 0, ',', '.'),
                $collectivePayment,
                ['amount' => $collectivePayment->amount, 'customer' => $collectivePayment->customer->name]
            );

            $collectivePayment->delete();
        });
    }
}

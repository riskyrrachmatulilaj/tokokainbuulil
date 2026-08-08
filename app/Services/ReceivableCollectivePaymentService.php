<?php

namespace App\Services;

use App\Models\Receivable;
use App\Models\ReceivableCollectivePayment;
use App\Models\ReceivableParty;
use App\Models\ReceivablePaymentHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReceivableCollectivePaymentService
{
    public function __construct(
        private readonly TransactionNumberService $numberService,
    ) {
    }

    /**
     * Memproses pembayaran kolektif piutang dengan aturan FIFO:
     * nota dengan tanggal piutang paling tua dilunasi lebih dahulu.
     *
     * Seluruh proses berjalan dalam satu database transaction.
     *
     * @return array{collectivePayment: ReceivableCollectivePayment, allocations: array}
     */
    public function process(array $data, ?User $user = null): array
    {
        $amount = round((float) ($data['amount'] ?? 0), 2);

        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'Nominal pembayaran harus lebih dari 0.',
            ]);
        }

        $party = ReceivableParty::findOrFail($data['receivable_party_id']);

        $receivables = $party->receivables()
            ->where('status', Receivable::STATUS_UNPAID)
            ->orderBy('receivable_date')
            ->orderBy('id')
            ->get();

        $totalRemaining = $receivables->sum(fn (Receivable $receivable) => (float) $receivable->remaining_amount);

        if ($totalRemaining <= 0) {
            throw ValidationException::withMessages([
                'receivable_party_id' => 'Debitur ini tidak memiliki piutang yang belum lunas.',
            ]);
        }

        if ($amount > $totalRemaining) {
            throw ValidationException::withMessages([
                'amount' => 'Pembayaran melebihi total sisa piutang debitur ('.number_format($totalRemaining, 2).').',
            ]);
        }

        return DB::transaction(function () use ($data, $amount, $party, $receivables, $user) {
            $collectivePayment = ReceivableCollectivePayment::create([
                'transaction_number' => $this->numberService->nextReceivableTransactionNumber(),
                'receivable_party_id' => $party->id,
                'amount' => $amount,
                'payment_date' => $data['payment_date'] ?? today(),
                'description' => $data['description'] ?? null,
                'created_by' => $user?->id,
            ]);

            $transactionNumber = $collectivePayment->transaction_number;
            $allocations = [];

            $remainingPayment = $amount;

            foreach ($receivables as $receivable) {
                if ($remainingPayment <= 0) {
                    break;
                }

                $receivable = Receivable::whereKey($receivable->id)->lockForUpdate()->first();

                if (! $receivable || $receivable->status === Receivable::STATUS_PAID) {
                    continue;
                }

                $receivableRemaining = (float) $receivable->remaining_amount;

                if ($receivableRemaining <= 0) {
                    continue;
                }

                $allocation = min($receivableRemaining, $remainingPayment);

                $newPaid = round((float) $receivable->paid_amount + $allocation, 2);
                $newRemaining = round((float) $receivable->amount - $newPaid, 2);

                $receivable->update([
                    'paid_amount' => $newPaid,
                    'remaining_amount' => max(0, $newRemaining),
                    'status' => $newRemaining <= 0 ? Receivable::STATUS_PAID : Receivable::STATUS_UNPAID,
                ]);

                ReceivablePaymentHistory::create([
                    'transaction_number' => $transactionNumber,
                    'receivable_party_id' => $party->id,
                    'receivable_id' => $receivable->id,
                    'installment_id' => null,
                    'collective_payment_id' => $collectivePayment->id,
                    'payment_type' => ReceivablePaymentHistory::TYPE_COLLECTIVE,
                    'amount' => $allocation,
                    'payment_date' => $collectivePayment->payment_date,
                    'description' => $data['description'] ?? null,
                    'created_by' => $user?->id,
                ]);

                $allocations[] = [
                    'receivable_id' => $receivable->id,
                    'invoice_number' => $receivable->invoice_number,
                    'amount' => $allocation,
                    'remaining' => max(0, $newRemaining),
                    'status' => $receivable->status,
                ];

                $remainingPayment = round($remainingPayment - $allocation, 2);
            }

            return [
                'collectivePayment' => $collectivePayment->fresh()->load('party'),
                'allocations' => $allocations,
            ];
        });
    }

    /**
     * Membatalkan (menghapus) pembayaran kolektif piutang.
     * Mengembalikan sisa piutang pada setiap nota yang terpengaruh,
     * menghapus riwayat pembayaran terkait, lalu menghapus record pembayaran kolektif.
     */
    public function reversePayment(ReceivableCollectivePayment $collectivePayment): void
    {
        DB::transaction(function () use ($collectivePayment) {
            $histories = $collectivePayment->history()->get();

            foreach ($histories as $history) {
                $receivable = Receivable::whereKey($history->receivable_id)->lockForUpdate()->first();

                if ($receivable) {
                    $newPaid = round((float) $receivable->paid_amount - (float) $history->amount, 2);
                    $newRemaining = round((float) $receivable->amount - $newPaid, 2);

                    $receivable->update([
                        'paid_amount' => max(0, $newPaid),
                        'remaining_amount' => max(0, $newRemaining),
                        'status' => $newRemaining > 0 ? Receivable::STATUS_UNPAID : Receivable::STATUS_PAID,
                    ]);
                }

                $history->delete();
            }

            $collectivePayment->delete();
        });
    }
}

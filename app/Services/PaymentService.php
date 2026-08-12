<?php

namespace App\Services;

use App\Models\Debt;
use App\Models\Installment;
use App\Models\PaymentHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public function __construct(
        private readonly TransactionNumberService $numberService,
    ) {
    }

    /**
     * Mencatat cicilan nota dengan database transaction.
     */
    public function recordInstallment(array $data, ?User $user = null): Installment
    {
        return DB::transaction(function () use ($data, $user) {
            $debt = Debt::whereKey($data['debt_id'])->lockForUpdate()->firstOrFail();

            $amount = round((float) $data['amount'], 2);

            if ($amount <= 0) {
                throw ValidationException::withMessages([
                    'amount' => 'Nominal cicilan harus lebih dari 0.',
                ]);
            }

            $remaining = (float) $debt->remaining_amount;

            if ($amount > $remaining) {
                throw ValidationException::withMessages([
                    'amount' => 'Pembayaran melebihi sisa hutang. Sisa hutang: '.number_format($remaining, 2).'.',
                ]);
            }

            $installment = Installment::create([
                'debt_id' => $debt->id,
                'installment_date' => $data['installment_date'] ?? today(),
                'amount' => $amount,
                'description' => $data['description'] ?? null,
                'created_by' => $user?->id,
            ]);

            $this->applyPaymentToDebt($debt, $amount);

            PaymentHistory::create([
                'transaction_number' => $this->numberService->nextTransactionNumber(),
                'customer_id' => $debt->customer_id,
                'debt_id' => $debt->id,
                'installment_id' => $installment->id,
                'collective_payment_id' => null,
                'payment_type' => PaymentHistory::TYPE_INSTALLMENT,
                'amount' => $amount,
                'payment_date' => $data['installment_date'] ?? today(),
                'description' => $data['description'] ?? null,
                'created_by' => $user?->id,
            ]);

            app(\App\Services\ActivityLogService::class)->log(
                'Hutang',
                'pay',
                "Mencatat pembayaran cicilan hutang toko {$debt->invoice_number} (Pemasok: {$debt->customer->name}) sebesar Rp " . number_format($amount, 0, ',', '.'),
                $installment,
                ['amount' => $amount, 'invoice' => $debt->invoice_number, 'supplier' => $debt->customer->name],
                $user
            );

            return $installment->load('debt.customer');
        });
    }

    /**
     * Membatalkan cicilan (soft delete) dan mengembalikan saldo nota.
     * Hanya diperbolehkan untuk Admin dan dicatat dalam transaction.
     */
    public function cancelInstallment(Installment $installment): void
    {
        DB::transaction(function () use ($installment) {
            $debt = Debt::whereKey($installment->debt_id)->lockForUpdate()->firstOrFail();

            $installment->paymentHistory()->delete();

            $newPaid = max(0, (float) $debt->paid_amount - (float) $installment->amount);
            $remaining = round((float) $debt->amount - $newPaid, 2);

            $debt->update([
                'paid_amount' => $newPaid,
                'remaining_amount' => $remaining,
                'status' => $remaining <= 0 ? Debt::STATUS_PAID : Debt::STATUS_UNPAID,
            ]);

            app(\App\Services\ActivityLogService::class)->log(
                'Hutang',
                'cancel',
                "Membatalkan cicilan hutang toko {$debt->invoice_number} (Pemasok: {$debt->customer->name}) sebesar Rp " . number_format((float)$installment->amount, 0, ',', '.'),
                $installment,
                ['amount' => $installment->amount, 'invoice' => $debt->invoice_number, 'supplier' => $debt->customer->name]
            );

            $installment->delete();
        });
    }

    /**
     * Menerapkan pembayaran ke nota dan memperbarui status.
     */
    protected function applyPaymentToDebt(Debt $debt, float $amount): void
    {
        $newPaid = round((float) $debt->paid_amount + $amount, 2);
        $remaining = round((float) $debt->amount - $newPaid, 2);

        $debt->update([
            'paid_amount' => $newPaid,
            'remaining_amount' => max(0, $remaining),
            'status' => $remaining <= 0 ? Debt::STATUS_PAID : Debt::STATUS_UNPAID,
        ]);
    }
}

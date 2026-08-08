<?php

namespace App\Services;

use App\Models\Receivable;
use App\Models\ReceivableInstallment;
use App\Models\ReceivablePaymentHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReceivablePaymentService
{
    public function __construct(
        private readonly TransactionNumberService $numberService,
    ) {
    }

    /**
     * Mencatat cicilan nota piutang dengan database transaction.
     */
    public function recordInstallment(array $data, ?User $user = null): ReceivableInstallment
    {
        return DB::transaction(function () use ($data, $user) {
            $receivable = Receivable::whereKey($data['receivable_id'])->lockForUpdate()->firstOrFail();

            $amount = round((float) $data['amount'], 2);

            if ($amount <= 0) {
                throw ValidationException::withMessages([
                    'amount' => 'Nominal cicilan harus lebih dari 0.',
                ]);
            }

            $remaining = (float) $receivable->remaining_amount;

            if ($amount > $remaining) {
                throw ValidationException::withMessages([
                    'amount' => 'Pembayaran melebihi sisa piutang. Sisa piutang: '.number_format($remaining, 2).'.',
                ]);
            }

            $installment = ReceivableInstallment::create([
                'receivable_id' => $receivable->id,
                'installment_date' => $data['installment_date'] ?? today(),
                'amount' => $amount,
                'description' => $data['description'] ?? null,
                'created_by' => $user?->id,
            ]);

            $this->applyPaymentToReceivable($receivable, $amount);

            ReceivablePaymentHistory::create([
                'transaction_number' => $this->numberService->nextReceivableTransactionNumber(),
                'receivable_party_id' => $receivable->receivable_party_id,
                'receivable_id' => $receivable->id,
                'installment_id' => $installment->id,
                'collective_payment_id' => null,
                'payment_type' => ReceivablePaymentHistory::TYPE_INSTALLMENT,
                'amount' => $amount,
                'payment_date' => $data['installment_date'] ?? today(),
                'description' => $data['description'] ?? null,
                'created_by' => $user?->id,
            ]);

            return $installment->load('receivable.party');
        });
    }

    /**
     * Membatalkan cicilan (soft delete) dan mengembalikan saldo nota piutang.
     * Hanya diperbolehkan untuk Admin dan dicatat dalam transaction.
     */
    public function cancelInstallment(ReceivableInstallment $installment): void
    {
        DB::transaction(function () use ($installment) {
            $receivable = Receivable::whereKey($installment->receivable_id)->lockForUpdate()->firstOrFail();

            $installment->paymentHistory()->delete();

            $newPaid = max(0, (float) $receivable->paid_amount - (float) $installment->amount);
            $remaining = round((float) $receivable->amount - $newPaid, 2);

            $receivable->update([
                'paid_amount' => $newPaid,
                'remaining_amount' => $remaining,
                'status' => $remaining <= 0 ? Receivable::STATUS_PAID : Receivable::STATUS_UNPAID,
            ]);

            $installment->delete();
        });
    }

    /**
     * Menerapkan pembayaran ke nota piutang dan memperbarui status.
     */
    protected function applyPaymentToReceivable(Receivable $receivable, float $amount): void
    {
        $newPaid = round((float) $receivable->paid_amount + $amount, 2);
        $remaining = round((float) $receivable->amount - $newPaid, 2);

        $receivable->update([
            'paid_amount' => $newPaid,
            'remaining_amount' => max(0, $remaining),
            'status' => $remaining <= 0 ? Receivable::STATUS_PAID : Receivable::STATUS_UNPAID,
        ]);
    }
}

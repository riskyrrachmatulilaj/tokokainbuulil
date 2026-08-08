<?php

namespace App\Services;

use App\Models\Receivable;
use App\Models\ReceivableParty;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReceivableService
{
    public function __construct(
        private readonly TransactionNumberService $numberService,
    ) {
    }

    /**
     * Membuat nota piutang baru beserta nomor nota otomatis.
     */
    public function createReceivable(array $data, ?User $user = null): Receivable
    {
        if ((float) ($data['amount'] ?? 0) < 0) {
            throw ValidationException::withMessages([
                'amount' => 'Nominal piutang tidak boleh negatif.',
            ]);
        }

        return DB::transaction(function () use ($data, $user) {
            $receivable = Receivable::create([
                'invoice_number' => $this->numberService->nextReceivableInvoiceNumber(),
                'receivable_party_id' => $data['receivable_party_id'],
                'amount' => $data['amount'],
                'paid_amount' => 0,
                'remaining_amount' => $data['amount'],
                'receivable_date' => $data['receivable_date'] ?? today(),
                'due_date' => $data['due_date'] ?? null,
                'status' => Receivable::STATUS_UNPAID,
                'description' => $data['description'] ?? null,
                'created_by' => $user?->id,
            ]);

            return $receivable->load('party');
        });
    }

    /**
     * Memperbarui nominal piutang. Seluruh cicilan yang sudah diterima
     * dipertahankan, hanya menyesuaikan sisa piutang.
     */
    public function updateReceivable(Receivable $receivable, array $data): Receivable
    {
        if ((float) ($data['amount'] ?? 0) < 0) {
            throw ValidationException::withMessages([
                'amount' => 'Nominal piutang tidak boleh negatif.',
            ]);
        }

        return DB::transaction(function () use ($receivable, $data) {
            $receivable = $receivable->lockForUpdate();

            $paid = $receivable->paid_amount;

            if ((float) $data['amount'] < (float) $paid) {
                throw ValidationException::withMessages([
                    'amount' => 'Nominal piutang tidak boleh lebih kecil dari total yang sudah diterima ('.number_format($paid, 2).').',
                ]);
            }

            $remaining = round((float) $data['amount'] - (float) $paid, 2);

            $receivable->update([
                'amount' => $data['amount'],
                'remaining_amount' => $remaining,
                'status' => $remaining <= 0 ? Receivable::STATUS_PAID : Receivable::STATUS_UNPAID,
                'receivable_date' => $data['receivable_date'] ?? $receivable->receivable_date,
                'due_date' => array_key_exists('due_date', $data) ? $data['due_date'] : $receivable->due_date,
                'description' => array_key_exists('description', $data) ? $data['description'] : $receivable->description,
            ]);

            return $receivable->fresh();
        });
    }

    /**
     * Menghapus nota, hanya diperbolehkan apabila belum ada pembayaran.
     */
    public function deleteReceivable(Receivable $receivable): void
    {
        if ($receivable->paymentHistories()->exists()) {
            throw ValidationException::withMessages([
                'receivable' => 'Nota sudah memiliki pembayaran dan tidak dapat dihapus.',
            ]);
        }

        $receivable->delete();
    }

    /**
     * Menghapus debitur. Dilarang apabila masih memiliki nota belum lunas.
     */
    public function deleteParty(ReceivableParty $party): void
    {
        if ($party->receivables()->where('status', Receivable::STATUS_UNPAID)->exists()) {
            throw ValidationException::withMessages([
                'party' => 'Debitur masih memiliki nota piutang yang belum lunas dan tidak dapat dihapus.',
            ]);
        }

        $party->delete();
    }

    public function totalRemaining(ReceivableParty $party): float
    {
        return (float) $party->receivables()
            ->where('status', Receivable::STATUS_UNPAID)
            ->sum('remaining_amount');
    }
}

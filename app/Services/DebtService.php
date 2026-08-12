<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Debt;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DebtService
{
    public function __construct(
        private readonly TransactionNumberService $numberService,
    ) {
    }

    /**
     * Membuat nota hutang baru beserta nomor nota otomatis.
     */
    public function createDebt(array $data, ?User $user = null): Debt
    {
        if ((float) ($data['amount'] ?? 0) < 0) {
            throw ValidationException::withMessages([
                'amount' => 'Nominal hutang tidak boleh negatif.',
            ]);
        }

        return DB::transaction(function () use ($data, $user) {
            $debt = Debt::create([
                'invoice_number' => $this->numberService->nextInvoiceNumber(),
                'customer_id' => $data['customer_id'],
                'amount' => $data['amount'],
                'paid_amount' => 0,
                'remaining_amount' => $data['amount'],
                'debt_date' => $data['debt_date'] ?? today(),
                'due_date' => $data['due_date'] ?? null,
                'status' => Debt::STATUS_UNPAID,
                'description' => $data['description'] ?? null,
                'created_by' => $user?->id,
            ]);

            app(\App\Services\ActivityLogService::class)->log(
                'Hutang',
                'create',
                "Mencatat nota hutang toko baru {$debt->invoice_number} (Pemasok: {$debt->customer->name}) sebesar Rp " . number_format((float)$debt->amount, 0, ',', '.'),
                $debt,
                ['invoice' => $debt->invoice_number, 'supplier' => $debt->customer->name, 'amount' => $debt->amount],
                $user
            );

            return $debt->load('customer');
        });
    }

    /**
     * Memperbarui nominal hutang. Seluruh cicilan yang sudah terbayar
     * dipertahankan, hanya menyesuaikan sisa hutang.
     */
    public function updateDebt(Debt $debt, array $data): Debt
    {
        if ((float) ($data['amount'] ?? 0) < 0) {
            throw ValidationException::withMessages([
                'amount' => 'Nominal hutang tidak boleh negatif.',
            ]);
        }

        return DB::transaction(function () use ($debt, $data) {
            $debt = $debt->lockForUpdate();

            $paid = $debt->paid_amount;

            if ((float) $data['amount'] < (float) $paid) {
                throw ValidationException::withMessages([
                    'amount' => 'Nominal hutang tidak boleh lebih kecil dari total yang sudah dibayar ('.number_format($paid, 2).').',
                ]);
            }

            $remaining = round((float) $data['amount'] - (float) $paid, 2);

            $debt->update([
                'amount' => $data['amount'],
                'remaining_amount' => $remaining,
                'status' => $remaining <= 0 ? Debt::STATUS_PAID : Debt::STATUS_UNPAID,
                'debt_date' => $data['debt_date'] ?? $debt->debt_date,
                'due_date' => array_key_exists('due_date', $data) ? $data['due_date'] : $debt->due_date,
                'description' => array_key_exists('description', $data) ? $data['description'] : $debt->description,
            ]);

            app(\App\Services\ActivityLogService::class)->log(
                'Hutang',
                'update',
                "Mengubah nota hutang toko {$debt->invoice_number} (Pemasok: {$debt->customer->name}) menjadi Rp " . number_format((float)$debt->amount, 0, ',', '.'),
                $debt,
                ['invoice' => $debt->invoice_number, 'amount' => $debt->amount]
            );

            return $debt->fresh();
        });
    }

    /**
     * Menghapus nota, hanya diperbolehkan apabila belum ada pembayaran.
     */
    public function deleteDebt(Debt $debt): void
    {
        if ($debt->paymentHistories()->exists()) {
            throw ValidationException::withMessages([
                'debt' => 'Nota sudah memiliki pembayaran dan tidak dapat dihapus.',
            ]);
        }

        app(\App\Services\ActivityLogService::class)->log(
            'Hutang',
            'delete',
            "Menghapus nota hutang toko {$debt->invoice_number} (Pemasok: {$debt->customer->name})",
            $debt,
            ['invoice' => $debt->invoice_number]
        );

        $debt->delete();
    }

    public function totalRemaining(Customer $customer): float
    {
        return (float) $customer->debts()
            ->where('status', Debt::STATUS_UNPAID)
            ->sum('remaining_amount');
    }
}

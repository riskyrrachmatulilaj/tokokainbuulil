<?php

namespace App\Services;

use App\Models\Debt;
use App\Models\Receivable;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;

class TransactionNumberService
{
    /**
     * Generate nomor nota piutang, contoh: INV-20260802-0001
     */
    public function nextInvoiceNumber(): string
    {
        return $this->generate(Debt::class, 'invoice_number', 'INV');
    }

    /**
     * Generate nomor nota piutang, contoh: PINV-20260803-0001
     */
    public function nextReceivableInvoiceNumber(): string
    {
        return $this->generate(Receivable::class, 'invoice_number', 'PINV');
    }

    /**
     * Generate nomor transaksi pembayaran, contoh: TRX-20260802-0001
     */
    public function nextTransactionNumber(): string
    {
        return $this->generateRaw('payment_histories', 'transaction_number', 'TRX');
    }

    /**
     * Generate nomor transaksi penerimaan piutang, contoh: PTRX-20260803-0001
     */
    public function nextReceivableTransactionNumber(): string
    {
        return $this->generateRaw('receivable_payment_histories', 'transaction_number', 'PTRX');
    }

    /**
     * Generate nomor transaksi penjualan (nota kasir), contoh: SLS-20260804-0001
     */
    public function nextSaleNumber(): string
    {
        return $this->generate(Sale::class, 'transaction_number', 'SLS');
    }

    private function generate(string $model, string $column, string $prefix): string
    {
        $todayStr = today()->format('Ymd');
        $pattern = $prefix.'-'.$todayStr.'-%';

        $query = $model::query();
        if (in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive($model))) {
            $query->withTrashed();
        }

        $latest = $query
            ->where($column, 'like', $pattern)
            ->orderByDesc('id')
            ->value($column);

        $sequence = 1;
        if ($latest !== null) {
            preg_match('/-(\d+)$/', $latest, $matches);
            $sequence = (int) ($matches[1] ?? 0) + 1;
        }

        return $prefix.'-'.$todayStr.'-'.str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    private function generateRaw(string $table, string $column, string $prefix): string
    {
        $todayStr = today()->format('Ymd');
        $pattern = $prefix.'-'.$todayStr.'-%';

        $latest = DB::table($table)
            ->where($column, 'like', $pattern)
            ->orderByDesc('id')
            ->value($column);

        $sequence = 1;
        if ($latest !== null) {
            preg_match('/-(\d+)$/', $latest, $matches);
            $sequence = (int) ($matches[1] ?? 0) + 1;
        }

        return $prefix.'-'.$todayStr.'-'.str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }
}

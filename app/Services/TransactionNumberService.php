<?php

namespace App\Services;

use App\Models\Debt;
use App\Models\Receivable;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;

class TransactionNumberService
{
    /**
     * Generate nomor nota hutang, contoh: INV-20260802-0001
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
        $sequence = DB::table('payment_histories')
            ->whereDate('created_at', today())
            ->distinct()
            ->count('transaction_number') + 1;

        return 'TRX-'.today()->format('Ymd').'-'.str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Generate nomor transaksi penerimaan piutang, contoh: PTRX-20260803-0001
     */
    public function nextReceivableTransactionNumber(): string
    {
        $sequence = DB::table('receivable_payment_histories')
            ->whereDate('created_at', today())
            ->distinct()
            ->count('transaction_number') + 1;

        return 'PTRX-'.today()->format('Ymd').'-'.str_pad($sequence, 4, '0', STR_PAD_LEFT);
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
        $latest = $model::query()
            ->whereDate('created_at', today())
            ->orderByDesc('id')
            ->value($column);

        $sequence = 1;
        if ($latest !== null) {
            preg_match('/-(\d+)$/', $latest, $matches);
            $sequence = (int) ($matches[1] ?? 0) + 1;
        }

        return $prefix.'-'.today()->format('Ymd').'-'.str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }
}

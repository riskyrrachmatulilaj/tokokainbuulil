<?php

namespace App\Services;

use App\Models\Sale;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class SaleReportService
{
    /**
     * Menyusun data laporan penjualan harian.
     *
     * @return array{date: string, summary: array, sales: Collection, items: Collection}
     */
    public function data(string $date): array
    {
        $day = Carbon::parse($date)->format('Y-m-d');

        $sales = Sale::query()
            ->with(['items', 'party'])
            ->whereDate('sale_date', $day)
            ->orderBy('created_at')
            ->get();

        $cashSales = $sales->where('payment_method', Sale::PAYMENT_METHOD_CASH);
        $receivableSales = $sales->where('payment_method', Sale::PAYMENT_METHOD_RECEIVABLE);
        $transferSales = $sales->where('payment_method', Sale::PAYMENT_METHOD_TRANSFER);
        $splitSales = $sales->where('payment_method', Sale::PAYMENT_METHOD_SPLIT);

        $cashRevenue = (float) $cashSales->sum('total_amount') + (float) $splitSales->sum('cash_amount');
        $transferRevenue = (float) $transferSales->sum('total_amount') + (float) $splitSales->sum('transfer_amount');

        $summary = [
            'transactions' => $sales->count(),
            'total_revenue' => round((float) $sales->sum('total_amount'), 2),
            'cash_count' => $cashSales->count(),
            'cash_revenue' => round($cashRevenue, 2),
            'transfer_count' => $transferSales->count(),
            'transfer_revenue' => round($transferRevenue, 2),
            'split_count' => $splitSales->count(),
            'split_revenue' => round((float) $splitSales->sum('total_amount'), 2),
            'receivable_count' => $receivableSales->count(),
            'receivable_revenue' => round((float) $receivableSales->sum('total_amount'), 2),
            'items_count' => $sales->sum(fn (Sale $sale) => $sale->items->sum('quantity')),
        ];

        $rows = $sales->map(fn (Sale $sale) => [
            'transaction_number' => $sale->transaction_number,
            'time' => $sale->created_at?->format('H:i'),
            'payment_method_label' => $sale->payment_method_label,
            'party' => $sale->party?->name,
            'items_count' => $sale->items->sum('quantity'),
            'total_amount' => $sale->total_amount,
        ]);

        $items = $sales
            ->flatMap(fn (Sale $sale) => $sale->items)
            ->groupBy('product_name')
            ->map(function (Collection $group) {
                return [
                    'product_name' => $group->first()->product_name,
                    'quantity' => $group->sum('quantity'),
                    'revenue' => round((float) $group->sum('subtotal'), 2),
                ];
            })
            ->sortByDesc('revenue')
            ->values();

        return [
            'date' => Carbon::parse($day)->format('d M Y'),
            'summary' => $summary,
            'sales' => $rows,
            'items' => $items,
        ];
    }
}

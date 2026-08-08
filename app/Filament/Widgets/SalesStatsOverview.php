<?php

namespace App\Filament\Widgets;

use App\Models\Sale;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SalesStatsOverview extends BaseWidget
{
    protected static ?int $sort = 6;

    protected static ?string $title = 'Ringkasan Penjualan Hari Ini';

    protected function getStats(): array
    {
        $todaySales = Sale::whereDate('sale_date', today())->get();
        $cash = $todaySales->where('payment_method', Sale::PAYMENT_METHOD_CASH);
        $transfer = $todaySales->where('payment_method', Sale::PAYMENT_METHOD_TRANSFER);
        $credit = $todaySales->where('payment_method', Sale::PAYMENT_METHOD_RECEIVABLE);

        return [
            Stat::make('Penjualan Hari Ini', rupiah($todaySales->sum('total_amount')))
                ->description($todaySales->count().' transaksi')
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color('primary'),

            Stat::make('Transaksi Tunai', rupiah($cash->sum('total_amount')))
                ->description($cash->count().' transaksi tunai')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('Transaksi Transfer', rupiah($transfer->sum('total_amount')))
                ->description($transfer->count().' transaksi transfer')
                ->descriptionIcon('heroicon-m-building-library')
                ->color($transfer->isNotEmpty() ? 'info' : 'gray'),

            Stat::make('Transaksi Kredit', rupiah($credit->sum('total_amount')))
                ->description($credit->count().' transaksi tercatat sebagai piutang')
                ->descriptionIcon('heroicon-m-receipt-percent')
                ->color($credit->isNotEmpty() ? 'warning' : 'gray'),

            Stat::make('Item Terjual', (string) $todaySales->sum(fn (Sale $sale) => $sale->items()->sum('quantity')))
                ->description('Barang terjual hari ini')
                ->descriptionIcon('heroicon-m-archive-box')
                ->color('info'),
        ];
    }
}

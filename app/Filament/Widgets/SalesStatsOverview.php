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
        $split = $todaySales->where('payment_method', Sale::PAYMENT_METHOD_SPLIT);
        $credit = $todaySales->filter(fn (Sale $s) => $s->isCredit());

        $cashTotal = (float) $cash->sum('total_amount')
            + (float) $split->sum('cash_amount')
            + (float) $todaySales->whereIn('payment_method', [Sale::PAYMENT_METHOD_CREDIT_CASH, Sale::PAYMENT_METHOD_CREDIT_SPLIT])->sum('cash_amount');

        $transferTotal = (float) $transfer->sum('total_amount')
            + (float) $split->sum('transfer_amount')
            + (float) $todaySales->whereIn('payment_method', [Sale::PAYMENT_METHOD_CREDIT_TRANSFER, Sale::PAYMENT_METHOD_CREDIT_SPLIT])->sum('transfer_amount');

        $creditTotal = (float) $credit->sum(fn (Sale $s) => $s->remaining_credit);

        return [
            Stat::make('Penjualan Hari Ini', rupiah($todaySales->sum('total_amount')))
                ->description($todaySales->count().' transaksi')
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color('primary'),

            Stat::make('Transaksi Tunai', rupiah($cashTotal))
                ->description($cash->count().' tunai, '.$todaySales->where('payment_method', Sale::PAYMENT_METHOD_CREDIT_CASH)->count().' kredit+tunai')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('Transaksi Transfer', rupiah($transferTotal))
                ->description($transfer->count().' transfer, '.$todaySales->where('payment_method', Sale::PAYMENT_METHOD_CREDIT_TRANSFER)->count().' kredit+transfer')
                ->descriptionIcon('heroicon-m-building-library')
                ->color($transferTotal > 0 ? 'info' : 'gray'),

            Stat::make('Transaksi Kredit', rupiah($creditTotal))
                ->description($credit->count().' transaksi (sisa piutang)')
                ->descriptionIcon('heroicon-m-receipt-percent')
                ->color($credit->isNotEmpty() ? 'warning' : 'gray'),

            Stat::make('Item Terjual', (string) $todaySales->sum(fn (Sale $sale) => $sale->items()->sum('quantity')))
                ->description('Barang terjual hari ini')
                ->descriptionIcon('heroicon-m-archive-box')
                ->color('info'),
        ];
    }
}

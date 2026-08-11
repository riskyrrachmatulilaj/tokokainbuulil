<?php

namespace App\Filament\Widgets;

use App\Models\Customer;
use App\Models\Debt;
use App\Models\PaymentHistory;
use App\Models\Sale;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $totalAmount = (float) Debt::sum('amount');
        $totalPaid = (float) Debt::sum('paid_amount');
        $totalRemaining = (float) Debt::sum('remaining_amount');

        $paymentToday = (float) PaymentHistory::whereDate('payment_date', today())
            ->sum('amount');

        $salesToday = (float) Sale::whereDate('sale_date', today())
            ->sum('total_amount');
        $salesCountToday = Sale::whereDate('sale_date', today())->count();

        return [
            Stat::make('Total Pelanggan', (string) Customer::count())
                ->description('Pelanggan terdaftar')
                ->descriptionIcon('heroicon-m-users')
                ->color('info'),

            Stat::make('Total Nota', (string) Debt::count())
                ->description('Nota piutang tercatat')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('primary'),

            Stat::make('Total Piutang', rupiah($totalAmount))
                ->description('Akumulasi nominal piutang')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('gray'),

            Stat::make('Total Sudah Dibayar', rupiah($totalPaid))
                ->description('Pembayaran yang diterima')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Total Sisa Piutang', rupiah($totalRemaining))
                ->description('Belum dibayar pelanggan')
                ->descriptionIcon('heroicon-m-clock')
                ->color($totalRemaining > 0 ? 'warning' : 'success'),

            Stat::make('Nota Belum Lunas', (string) Debt::unpaid()->count())
                ->description('Menunggu pembayaran')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger'),

            Stat::make('Nota Jatuh Tempo', (string) Debt::overdue()->count())
                ->description('Melewati tanggal jatuh tempo')
                ->descriptionIcon('heroicon-m-bell-alert')
                ->color(Debt::overdue()->count() > 0 ? 'danger' : 'success'),

            Stat::make('Pembayaran Hari Ini', rupiah($paymentToday))
                ->description(PaymentHistory::whereDate('payment_date', today())->count().' transaksi hari ini')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('info'),

            Stat::make('Penjualan Hari Ini', rupiah($salesToday))
                ->description($salesCountToday.' transaksi hari ini')
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color('success'),
        ];
    }
}

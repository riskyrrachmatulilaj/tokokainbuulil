<?php

namespace App\Filament\Widgets;

use App\Models\Receivable;
use App\Models\ReceivableParty;
use App\Models\ReceivablePaymentHistory;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ReceivableStatsOverview extends BaseWidget
{
    protected static ?int $sort = 4;

    protected static ?string $title = 'Ringkasan Piutang';

    protected function getStats(): array
    {
        $totalAmount = (float) Receivable::sum('amount');
        $totalPaid = (float) Receivable::sum('paid_amount');
        $totalRemaining = (float) Receivable::sum('remaining_amount');

        $paymentToday = (float) ReceivablePaymentHistory::whereDate('payment_date', today())
            ->sum('amount');

        return [
            Stat::make('Total Debitur', (string) ReceivableParty::count())
                ->description('Debitur terdaftar')
                ->descriptionIcon('heroicon-m-identification')
                ->color('info'),

            Stat::make('Total Nota Piutang', (string) Receivable::count())
                ->description('Nota piutang tercatat')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('primary'),

            Stat::make('Total Piutang', rupiah($totalAmount))
                ->description('Akumulasi nominal piutang')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('gray'),

            Stat::make('Total Sudah Diterima', rupiah($totalPaid))
                ->description('Pembayaran yang diterima')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Total Sisa Piutang', rupiah($totalRemaining))
                ->description('Belum dibayar debitur')
                ->descriptionIcon('heroicon-m-clock')
                ->color($totalRemaining > 0 ? 'warning' : 'success'),

            Stat::make('Nota Piutang Belum Lunas', (string) Receivable::unpaid()->count())
                ->description('Menunggu pembayaran')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger'),

            Stat::make('Nota Piutang Jatuh Tempo', (string) Receivable::overdue()->count())
                ->description('Melewati tanggal jatuh tempo')
                ->descriptionIcon('heroicon-m-bell-alert')
                ->color(Receivable::overdue()->count() > 0 ? 'danger' : 'success'),

            Stat::make('Penerimaan Hari Ini', rupiah($paymentToday))
                ->description(ReceivablePaymentHistory::whereDate('payment_date', today())->count().' transaksi hari ini')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('info'),
        ];
    }
}

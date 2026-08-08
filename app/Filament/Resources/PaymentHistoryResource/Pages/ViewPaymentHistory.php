<?php

namespace App\Filament\Resources\PaymentHistoryResource\Pages;

use App\Filament\Resources\PaymentHistoryResource;
use App\Models\PaymentHistory;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Filament\Resources\Pages\ViewRecord;

class ViewPaymentHistory extends ViewRecord
{
    protected static string $resource = PaymentHistoryResource::class;

    public function infolist(Schema $infolist): Schema
    {
        return $infolist
            ->schema([
                Section::make('Detail Pembayaran')
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('transaction_number')->label('No. Transaksi')->copyable(),
                            TextEntry::make('customer.name')->label('Pelanggan'),
                            TextEntry::make('debt.invoice_number')->label('Nota')
                                ->url(fn (PaymentHistory $record) => $record->debt ? \App\Filament\Resources\DebtResource::getUrl('view', ['record' => $record->debt]) : null),
                            TextEntry::make('payment_type')
                                ->label('Jenis Pembayaran')
                                ->badge()
                                ->color(fn (PaymentHistory $record) => $record->payment_type === PaymentHistory::TYPE_COLLECTIVE ? 'info' : 'primary')
                                ->state(fn (PaymentHistory $record) => $record->payment_type_label),
                            TextEntry::make('amount')->label('Nominal')->state(fn (PaymentHistory $record) => rupiah($record->amount)),
                            TextEntry::make('payment_date')->label('Tanggal')->date('d M Y'),
                            TextEntry::make('description')->label('Keterangan')->placeholder('-')->columnSpanFull(),
                            TextEntry::make('creator.name')->label('Oleh')->placeholder('-'),
                            TextEntry::make('created_at')->label('Dicatat')->dateTime('d M Y H:i'),
                        ]),
                    ]),
            ]);
    }
}

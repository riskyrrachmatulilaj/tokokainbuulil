<?php

namespace App\Filament\Resources\ReceivablePaymentHistoryResource\Pages;

use App\Filament\Resources\ReceivablePaymentHistoryResource;
use App\Models\ReceivablePaymentHistory;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Filament\Resources\Pages\ViewRecord;

class ViewReceivablePaymentHistory extends ViewRecord
{
    protected static string $resource = ReceivablePaymentHistoryResource::class;

    public function infolist(Schema $infolist): Schema
    {
        return $infolist
            ->schema([
                Section::make('Detail Pembayaran')
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('transaction_number')->label('No. Transaksi')->copyable(),
                            TextEntry::make('party.name')->label('Pelanggan'),
                            TextEntry::make('receivable.invoice_number')->label('Nota')
                                ->url(fn (ReceivablePaymentHistory $record) => $record->receivable ? \App\Filament\Resources\ReceivableResource::getUrl('view', ['record' => $record->receivable]) : null),
                            TextEntry::make('payment_type')
                                ->label('Jenis Pembayaran')
                                ->badge()
                                ->color(fn (ReceivablePaymentHistory $record) => $record->payment_type === ReceivablePaymentHistory::TYPE_COLLECTIVE ? 'info' : 'primary')
                                ->state(fn (ReceivablePaymentHistory $record) => $record->payment_type_label),
                            TextEntry::make('amount')->label('Nominal')->state(fn (ReceivablePaymentHistory $record) => rupiah($record->amount)),
                            TextEntry::make('payment_date')->label('Tanggal')->date('d M Y'),
                            TextEntry::make('description')->label('Keterangan')->placeholder('-')->columnSpanFull(),
                            TextEntry::make('creator.name')->label('Oleh')->placeholder('-'),
                            TextEntry::make('created_at')->label('Dicatat')->dateTime('d M Y H:i'),
                        ]),
                    ]),
            ]);
    }
}

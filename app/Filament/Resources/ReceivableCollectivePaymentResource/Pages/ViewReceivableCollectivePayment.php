<?php

namespace App\Filament\Resources\ReceivableCollectivePaymentResource\Pages;

use App\Filament\Resources\ReceivableCollectivePaymentResource;
use App\Models\ReceivableCollectivePayment;
use Filament\Actions;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Filament\Resources\Pages\ViewRecord;

class ViewReceivableCollectivePayment extends ViewRecord
{
    protected static string $resource = ReceivableCollectivePaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->label('Hapus Pembayaran')
                ->modalHeading('Hapus Pembayaran Kolektif Piutang')
                ->modalDescription('Apakah Anda yakin ingin menghapus pembayaran kolektif piutang ini? Semua alokasi pembayaran pada nota piutang akan dikembalikan (dibatalkan).')
                ->using(function (ReceivableCollectivePayment $record) {
                    app(\App\Services\ReceivableCollectivePaymentService::class)->reversePayment($record);
                })
                ->successRedirectUrl(ReceivableCollectivePaymentResource::getUrl()),
        ];
    }
    public function infolist(Schema $infolist): Schema
    {
        return $infolist
            ->schema([
                Section::make('Detail Pembayaran Kolektif Piutang')
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('transaction_number')->label('No. Transaksi')->copyable(),
                            TextEntry::make('party.name')->label('Pelanggan'),
                            TextEntry::make('amount')->label('Total Diterima')->state(fn (ReceivableCollectivePayment $record) => rupiah($record->amount)),
                            TextEntry::make('payment_date')->label('Tanggal')->date('d M Y'),
                            TextEntry::make('description')->label('Keterangan')->placeholder('-')->columnSpanFull(),
                            TextEntry::make('creator.name')->label('Oleh')->placeholder('-'),
                            TextEntry::make('created_at')->label('Dicatat')->dateTime('d M Y H:i'),
                        ]),
                    ]),
            ]);
    }
}

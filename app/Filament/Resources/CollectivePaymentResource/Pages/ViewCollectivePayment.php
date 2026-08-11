<?php

namespace App\Filament\Resources\CollectivePaymentResource\Pages;

use App\Filament\Resources\CollectivePaymentResource;
use App\Models\CollectivePayment;
use Filament\Actions;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Filament\Resources\Pages\ViewRecord;

class ViewCollectivePayment extends ViewRecord
{
    protected static string $resource = CollectivePaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->label('Hapus Pembayaran')
                ->modalHeading('Hapus Pembayaran Kolektif')
                ->modalDescription('Apakah Anda yakin ingin menghapus pembayaran kolektif ini? Semua alokasi pembayaran pada nota hutang akan dikembalikan (dibatalkan).')
                ->using(function (CollectivePayment $record) {
                    app(\App\Services\CollectivePaymentService::class)->reversePayment($record);
                })
                ->successRedirectUrl(CollectivePaymentResource::getUrl()),
        ];
    }

    public function infolist(Schema $infolist): Schema
    {
        return $infolist
            ->schema([
                Section::make('Detail Pembayaran Kolektif')
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('transaction_number')->label('No. Transaksi')->copyable(),
                            TextEntry::make('customer.name')->label('Supplier'),
                            TextEntry::make('amount')->label('Total Dibayar')->state(fn (CollectivePayment $record) => rupiah($record->amount)),
                            TextEntry::make('payment_date')->label('Tanggal')->date('d M Y'),
                            TextEntry::make('description')->label('Keterangan')->placeholder('-')->columnSpanFull(),
                            TextEntry::make('creator.name')->label('Oleh')->placeholder('-'),
                            TextEntry::make('created_at')->label('Dicatat')->dateTime('d M Y H:i'),
                        ]),
                    ]),
            ]);
    }
}

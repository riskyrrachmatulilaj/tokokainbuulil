<?php

namespace App\Filament\Resources\SaleResource\Pages;

use App\Filament\Resources\ReceivableResource;
use App\Filament\Resources\SaleResource;
use App\Models\Sale;
use App\Services\SalePdfService;
use App\Services\SaleThermalService;
use App\Services\SaleService;
use Filament\Actions;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Validation\ValidationException;

class ViewSale extends ViewRecord
{
    protected static string $resource = SaleResource::class;

    public function infolist(Schema $infolist): Schema
    {
        return $infolist
            ->schema([
                Section::make('Detail Penjualan')
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('transaction_number')->label('No. Transaksi')->copyable(),
                            TextEntry::make('sale_date')->label('Tanggal')->date('d M Y'),
                            TextEntry::make('created_at')->label('Jam')->dateTime('H:i'),
                            TextEntry::make('payment_method')->label('Metode Pembayaran')
                                ->badge()
                                ->color(fn (Sale $record) => $record->payment_method === Sale::PAYMENT_METHOD_CASH ? 'success' : 'warning')
                                ->state(fn (Sale $record) => $record->payment_method_label),
                            TextEntry::make('total_amount')->label('Total Penjualan')->state(fn (Sale $record) => rupiah($record->total_amount)),
                            TextEntry::make('received_amount')->label('Uang Diterima')->state(fn (Sale $record) => $record->received_amount !== null ? rupiah($record->received_amount) : '-'),
                            TextEntry::make('change_amount')->label('Kembalian')->state(fn (Sale $record) => $record->change_amount !== null ? rupiah($record->change_amount) : '-'),
                            TextEntry::make('party.name')->label('Pelanggan')
                                ->url(fn (Sale $record) => $record->party ? \App\Filament\Resources\ReceivablePartyResource::getUrl('view', ['record' => $record->party]) : null)
                                ->placeholder('-'),
                            TextEntry::make('receivable.invoice_number')->label('Nota Piutang')
                                ->url(fn (Sale $record) => $record->receivable ? ReceivableResource::getUrl('view', ['record' => $record->receivable]) : null)
                                ->placeholder('-'),
                            TextEntry::make('creator.name')->label('Kasir')->placeholder('-'),
                            TextEntry::make('description')->label('Keterangan')->placeholder('-')->columnSpanFull(),
                        ]),
                    ]),
                Section::make('Rincian Item')
                    ->schema([
                        RepeatableEntry::make('items')
                            ->label('Item Penjualan')
                            ->schema([
                                TextEntry::make('product_name')->label('Produk'),
                                TextEntry::make('quantity')->label('Jumlah'),
                                TextEntry::make('price')->label('Harga Satuan')->state(fn ($record) => rupiah($record->price)),
                                TextEntry::make('subtotal')->label('Subtotal')->state(fn ($record) => rupiah($record->subtotal)),
                            ])
                            ->columns(4),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ActionGroup::make([
                Actions\Action::make('copy_whatsapp')
                    ->label('Salin Pesan WA')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('info')
                    ->action(function () {
                        $text = addslashes($this->record->whatsapp_message_text ?? '');
                        $this->js("navigator.clipboard.writeText(`{$text}`);");

                        Notification::make()
                            ->success()
                            ->title('Pesan WA Berhasil Disalin!')
                            ->body('Silakan paste (Ctrl+V) pesan nota di chat WhatsApp Web/HP.')
                            ->send();
                    }),
                Actions\Action::make('send_whatsapp')
                    ->label('Buka Aplikasi WA')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('warning')
                    ->url(fn () => $this->record->whatsapp_link)
                    ->openUrlInNewTab(),
            ])
            ->label('WA Nota')
            ->icon('heroicon-o-chat-bubble-left-right')
            ->color('warning')
            ->visible(fn () => $this->record->party && $this->record->party->phone),
            Actions\ActionGroup::make([
                Actions\Action::make('print_thermal')
                    ->label('Cetak Struk Thermal')
                    ->icon('heroicon-o-receipt-percent')
                    ->color('success')
                    ->url(fn () => route('sales.thermal', ['sale' => $this->record->id]))
                    ->openUrlInNewTab(),
                Actions\Action::make('print_nota')
                    ->label('Cetak Nota A4')
                    ->icon('heroicon-o-document-text')
                    ->color('info')
                    ->url(fn () => route('sales.nota', ['sale' => $this->record->id]))
                    ->openUrlInNewTab(),
            ])->label('Cetak')->icon('heroicon-o-printer')->color('info'),
            Actions\DeleteAction::make()
                ->label('Batalkan Penjualan')
                ->requiresConfirmation()
                ->modalHeading('Batalkan Penjualan')
                ->modalDescription('Penjualan kredit yang sudah menerima pembayaran piutang tidak dapat dibatalkan.')
                ->visible(fn () => auth()->user()?->can('delete', $this->record))
                ->action(function () {
                    try {
                        app(SaleService::class)->deleteSale($this->record);

                        Notification::make()
                            ->success()
                            ->title('Penjualan dibatalkan')
                            ->send();
                    } catch (ValidationException $e) {
                        Notification::make()
                            ->danger()
                            ->title('Gagal membatalkan')
                            ->body(collect($e->errors())->flatten()->first())
                            ->send();

                        throw new Halt;
                    }
                }),
        ];
    }
}

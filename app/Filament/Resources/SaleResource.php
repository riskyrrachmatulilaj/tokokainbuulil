<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SaleResource\Pages;
use App\Models\Sale;
use App\Services\SalePdfService;
use App\Services\SaleThermalService;
use App\Services\SaleService;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Actions;
use Filament\Support\Exceptions\Halt;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class SaleResource extends Resource
{
    protected static ?string $model = Sale::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-shopping-cart';

    protected static string | \UnitEnum | null $navigationGroup = 'Transaksi Penjualan';

    protected static ?string $navigationLabel = 'Penjualan';

    protected static ?string $modelLabel = 'Penjualan';

    protected static ?string $pluralModelLabel = 'Penjualan';

    protected static ?int $navigationSort = 1;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('transaction_number')
                    ->label('No. Transaksi')
                    ->searchable()
                    ->copyable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('sale_date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Jam')
                    ->dateTime('H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Metode Pembayaran')
                    ->badge()
                    ->color(fn (Sale $record) => match ($record->payment_method) {
                        Sale::PAYMENT_METHOD_CASH => 'success',
                        Sale::PAYMENT_METHOD_TRANSFER => 'info',
                        Sale::PAYMENT_METHOD_SPLIT => 'primary',
                        Sale::PAYMENT_METHOD_RECEIVABLE => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (Sale $record) => $record->payment_method_label),
                Tables\Columns\TextColumn::make('party.name')
                    ->label('Pelanggan')
                    ->placeholder('-')
                    ->searchable(),
                Tables\Columns\TextColumn::make('items_sum_quantity')
                    ->label('Jumlah Item')
                    ->sum('items', 'quantity')
                    ->formatStateUsing(fn ($state) => formatQuantity($state))
                    ->badge()
                    ->color('gray')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total')
                    ->formatStateUsing(fn ($state) => rupiah($state))
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Kasir')
                    ->placeholder('-')
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('payment_method')
                    ->label('Metode Pembayaran')
                    ->options([
                        Sale::PAYMENT_METHOD_CASH => 'Tunai',
                        Sale::PAYMENT_METHOD_TRANSFER => 'Transfer',
                        Sale::PAYMENT_METHOD_SPLIT => 'Tunai + Transfer',
                        Sale::PAYMENT_METHOD_RECEIVABLE => 'Kredit (Piutang)',
                    ]),
                Tables\Filters\Filter::make('sale_date')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('Dari Tanggal'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn ($q) => $q->where('sale_date', '>=', $data['from']))
                            ->when($data['until'], fn ($q) => $q->where('sale_date', '<=', $data['until']));
                    }),
            ])
            ->actions([
                Actions\ViewAction::make(),
                Actions\ActionGroup::make([
                    Actions\Action::make('copy_whatsapp')
                        ->label('Salin Pesan WA')
                        ->icon('heroicon-o-document-duplicate')
                        ->color('info')
                        ->action(function (Sale $record, $livewire) {
                            $text = addslashes($record->whatsapp_message_text ?? '');
                            $livewire->js("navigator.clipboard.writeText(`{$text}`);");

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
                        ->url(fn (Sale $record) => $record->whatsapp_link)
                        ->openUrlInNewTab(),
                ])
                ->label('WA Nota')
                ->icon('heroicon-o-chat-bubble-left-right')
                ->color('warning')
                ->visible(fn (Sale $record) => $record->party && $record->party->phone),
                Actions\ActionGroup::make([
                    Actions\Action::make('print_continuous')
                        ->label('Continuous Ringkas (1-Baris)')
                        ->icon('heroicon-o-printer')
                        ->color('success')
                        ->url(fn (Sale $record) => route('sales.continuous', ['sale' => $record->id]))
                        ->openUrlInNewTab(),
                    Actions\Action::make('print_continuous_detail')
                        ->label('Continuous Detail (2-Baris)')
                        ->icon('heroicon-o-document-text')
                        ->color('warning')
                        ->url(fn (Sale $record) => route('sales.continuous-detail', ['sale' => $record->id]))
                        ->openUrlInNewTab(),
                    Actions\Action::make('print_thermal_roll')
                        ->label('Thermal Roll (72mm)')
                        ->icon('heroicon-o-receipt-percent')
                        ->color('gray')
                        ->url(fn (Sale $record) => route('sales.thermal-roll', ['sale' => $record->id]))
                        ->openUrlInNewTab(),
                    Actions\Action::make('print_nota')
                        ->label('Nota Faktur A4')
                        ->icon('heroicon-o-document')
                        ->color('info')
                        ->url(fn (Sale $record) => route('sales.nota', ['sale' => $record->id]))
                        ->openUrlInNewTab(),
                ])->label('Cetak')->icon('heroicon-o-printer')->color('info'),
                Actions\DeleteAction::make()
                    ->label('Batalkan')
                    ->requiresConfirmation()
                    ->modalHeading('Batalkan Penjualan')
                    ->modalDescription('Penjualan kredit yang sudah menerima pembayaran piutang tidak dapat dibatalkan.')
                    ->visible(fn (Sale $record) => auth()->user()?->can('delete', $record))
                    ->action(function (Sale $record) {
                        try {
                            app(SaleService::class)->deleteSale($record);

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
            ])
            ->bulkActions([
                Actions\DeleteBulkAction::make()
                    ->visible(fn () => auth()->user()?->isAdmin()),
            ])
            ->defaultSort('sale_date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSales::route('/'),
            'view' => Pages\ViewSale::route('/{record}'),
        ];
    }
}

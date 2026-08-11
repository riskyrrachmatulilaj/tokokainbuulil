<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CollectivePaymentResource\Pages;
use App\Filament\Resources\CollectivePaymentResource\RelationManagers\HistoryRelationManager;
use App\Models\CollectivePayment;
use Filament\Resources\Resource;
use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;

class CollectivePaymentResource extends Resource
{
    protected static ?string $model = CollectivePayment::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-arrow-right-circle';

    protected static string | \UnitEnum | null $navigationGroup = 'Transaksi Hutang';

    protected static ?string $navigationLabel = 'Laporan Pembayaran Kolektif Hutang';

    protected static ?string $modelLabel = 'Laporan Pembayaran Kolektif Hutang';

    protected static ?string $pluralModelLabel = 'Laporan Pembayaran Kolektif Hutang';

    protected static ?int $navigationSort = 3;

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
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Supplier')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Total Dibayar')
                    ->formatStateUsing(fn ($state) => rupiah($state))
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('history_count')
                    ->label('Nota Terkena')
                    ->counts('history')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('payment_date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Keterangan')
                    ->limit(40),
                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Oleh')
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dicatat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('customer_id')
                    ->label('Supplier')
                    ->relationship('customer', 'name')
                    ->searchable(),
                Tables\Filters\Filter::make('payment_date')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from')->label('Dari'),
                        \Filament\Forms\Components\DatePicker::make('until')->label('Sampai'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn ($q) => $q->where('payment_date', '>=', $data['from']))
                            ->when($data['until'], fn ($q) => $q->where('payment_date', '<=', $data['until']));
                    }),
            ])
            ->actions([
                Actions\ViewAction::make(),
                Actions\DeleteAction::make()
                    ->label('Hapus')
                    ->modalHeading('Hapus Pembayaran Kolektif Hutang')
                    ->modalDescription('Apakah Anda yakin ingin menghapus pembayaran kolektif ini? Semua alokasi pembayaran pada nota hutang akan dikembalikan (dibatalkan).')
                    ->using(function (CollectivePayment $record) {
                        app(\App\Services\CollectivePaymentService::class)->reversePayment($record);
                    }),
            ])
            ->defaultSort('payment_date', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            HistoryRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCollectivePayments::route('/'),
            'view' => Pages\ViewCollectivePayment::route('/{record}'),
        ];
    }
}


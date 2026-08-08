<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReceivablePaymentHistoryResource\Pages;
use App\Models\ReceivablePaymentHistory;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;

class ReceivablePaymentHistoryResource extends Resource
{
    protected static ?string $model = ReceivablePaymentHistory::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-clock';

    protected static string | \UnitEnum | null $navigationGroup = 'Transaksi Piutang';

    protected static ?string $navigationLabel = 'History Pembayaran Piutang';

    protected static ?string $modelLabel = 'Riwayat Pembayaran';

    protected static ?string $pluralModelLabel = 'History Pembayaran Piutang';

    protected static ?int $navigationSort = 2;

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
                Tables\Columns\TextColumn::make('party.name')
                    ->label('Debitur')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('receivable.invoice_number')
                    ->label('Nota')
                    ->searchable()
                    ->sortable()
                    ->url(fn (ReceivablePaymentHistory $record) => $record->receivable ? ReceivableResource::getUrl('view', ['record' => $record->receivable]) : null),
                Tables\Columns\TextColumn::make('payment_type')
                    ->label('Jenis Pembayaran')
                    ->badge()
                    ->color(fn (ReceivablePaymentHistory $record) => $record->payment_type === ReceivablePaymentHistory::TYPE_COLLECTIVE ? 'info' : 'primary')
                    ->formatStateUsing(fn (ReceivablePaymentHistory $record) => $record->payment_type_label)
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Nominal')
                    ->formatStateUsing(fn ($state) => rupiah($state))
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('payment_date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Keterangan')
                    ->limit(40)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Oleh')
                    ->placeholder('-')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dicatat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('receivable_party_id')
                    ->label('Debitur')
                    ->relationship('party', 'name')
                    ->searchable(),
                Tables\Filters\SelectFilter::make('payment_type')
                    ->label('Jenis Pembayaran')
                    ->options([
                        ReceivablePaymentHistory::TYPE_INSTALLMENT => 'Cicilan Nota',
                        ReceivablePaymentHistory::TYPE_COLLECTIVE => 'Pembayaran Kolektif',
                    ]),
                Tables\Filters\Filter::make('payment_date')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('Dari'),
                        Forms\Components\DatePicker::make('until')->label('Sampai'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn ($q) => $q->where('payment_date', '>=', $data['from']))
                            ->when($data['until'], fn ($q) => $q->where('payment_date', '<=', $data['until']));
                    }),
            ])
            ->actions([
                Actions\ViewAction::make(),
            ])
            ->defaultSort('payment_date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReceivablePaymentHistories::route('/'),
            'view' => Pages\ViewReceivablePaymentHistory::route('/{record}'),
        ];
    }
}

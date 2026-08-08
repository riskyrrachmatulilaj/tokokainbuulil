<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\PaymentHistoryResource;
use App\Models\PaymentHistory;
use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestPaymentsWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Pembayaran Terbaru';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                PaymentHistory::query()
                    ->with(['customer', 'debt', 'creator'])
                    ->latest('payment_date')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('transaction_number')
                    ->label('No. Transaksi')
                    ->searchable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Pelanggan'),
                Tables\Columns\TextColumn::make('debt.invoice_number')
                    ->label('Nota'),
                Tables\Columns\TextColumn::make('payment_type')
                    ->label('Jenis')
                    ->badge()
                    ->color(fn (PaymentHistory $record) => $record->payment_type === PaymentHistory::TYPE_COLLECTIVE ? 'info' : 'primary')
                    ->formatStateUsing(fn (PaymentHistory $record) => $record->payment_type_label),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Nominal')
                    ->formatStateUsing(fn ($state) => rupiah($state)),
                Tables\Columns\TextColumn::make('payment_date')
                    ->label('Tanggal')
                    ->date('d M Y'),
                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Oleh')
                    ->placeholder('-'),
            ])
            ->actions([
                Actions\Action::make('view')
                    ->label('Detail')
                    ->url(fn (PaymentHistory $record) => PaymentHistoryResource::getUrl('view', ['record' => $record])),
            ]);
    }
}

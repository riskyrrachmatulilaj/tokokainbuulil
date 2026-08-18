<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\ReceivablePaymentHistoryResource;
use App\Models\ReceivablePaymentHistory;
use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestReceivablePaymentsWidget extends BaseWidget
{
    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Penerimaan Piutang Terbaru';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ReceivablePaymentHistory::query()
                    ->with(['party', 'receivable', 'creator'])
                    ->latest('payment_date')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('transaction_number')
                    ->label('No. Transaksi')
                    ->searchable()
                    ->fontFamily(\Filament\Support\Enums\FontFamily::Mono)
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('party.name')
                    ->label('Debitur')
                    ->weight('medium'),
                Tables\Columns\TextColumn::make('receivable.invoice_number')
                    ->label('Nota')
                    ->fontFamily(\Filament\Support\Enums\FontFamily::Mono)
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('payment_type')
                    ->label('Jenis')
                    ->badge()
                    ->color(fn (ReceivablePaymentHistory $record) => $record->payment_type === ReceivablePaymentHistory::TYPE_COLLECTIVE ? 'info' : 'primary')
                    ->formatStateUsing(fn (ReceivablePaymentHistory $record) => $record->payment_type_label),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Nominal')
                    ->alignEnd()
                    ->fontFamily(\Filament\Support\Enums\FontFamily::Mono)
                    ->weight('bold')
                    ->formatStateUsing(fn ($state) => rupiah($state)),
                Tables\Columns\TextColumn::make('payment_date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Oleh')
                    ->placeholder('-'),
            ])
            ->actions([
                Actions\Action::make('view')
                    ->label('Detail')
                    ->url(fn (ReceivablePaymentHistory $record) => ReceivablePaymentHistoryResource::getUrl('view', ['record' => $record])),
            ]);
    }
}

<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\SaleResource;
use App\Models\Sale;
use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestSalesWidget extends BaseWidget
{
    protected static ?int $sort = 7;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Penjualan Terbaru';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Sale::query()
                    ->with(['party', 'creator'])
                    ->latest('sale_date')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('transaction_number')
                    ->label('No. Transaksi')
                    ->searchable()
                    ->fontFamily(\Filament\Support\Enums\FontFamily::Mono)
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('sale_date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Metode')
                    ->badge()
                    ->color(fn (Sale $record) => match ($record->payment_method) {
                        Sale::PAYMENT_METHOD_CASH => 'success',
                        Sale::PAYMENT_METHOD_TRANSFER => 'info',
                        Sale::PAYMENT_METHOD_SPLIT => 'primary',
                        default => 'warning',
                    })
                    ->formatStateUsing(fn (Sale $record) => $record->payment_method_label),
                Tables\Columns\TextColumn::make('party.name')
                    ->label('Pelanggan')
                    ->placeholder('-')
                    ->weight('medium'),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total')
                    ->alignEnd()
                    ->fontFamily(\Filament\Support\Enums\FontFamily::Mono)
                    ->weight('bold')
                    ->formatStateUsing(fn ($state) => rupiah($state)),
                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Kasir')
                    ->placeholder('-'),
            ])
            ->actions([
                Actions\Action::make('view')
                    ->label('Detail')
                    ->url(fn (Sale $record) => SaleResource::getUrl('view', ['record' => $record])),
            ]);
    }
}

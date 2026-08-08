<?php

namespace App\Filament\Resources\CollectivePaymentResource\RelationManagers;

use App\Models\PaymentHistory;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class HistoryRelationManager extends RelationManager
{
    protected static string $relationship = 'history';

    protected static ?string $title = 'Alokasi ke Nota (FIFO)';

    protected static string | \BackedEnum | null $icon = 'heroicon-o-banknotes';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('debt.invoice_number')
                    ->label('No. Nota')
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Nominal')
                    ->formatStateUsing(fn ($state) => rupiah($state))
                    ->sortable(),
                Tables\Columns\TextColumn::make('debt.debt_date')
                    ->label('Tanggal Nota')
                    ->date('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('debt.status')
                    ->label('Status Nota')
                    ->badge()
                    ->color(fn ($state) => $state === 'paid' ? 'success' : 'danger')
                    ->formatStateUsing(fn ($state) => $state === 'paid' ? 'Lunas' : 'Belum Lunas'),
            ])
            ->defaultSort('debt.debt_date');
    }
}

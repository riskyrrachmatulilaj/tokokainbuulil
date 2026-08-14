<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\DebtResource;
use App\Models\Debt;
use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class OverdueDebtsWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Nota Jatuh Tempo';

    public static function canView(): bool
    {
        return Debt::overdue()->exists();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Debt::query()
                    ->overdue()
                    ->with('customer')
                    ->orderBy('due_date')
            )
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('No. Nota')
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Pelanggan'),
                Tables\Columns\TextColumn::make('remaining_amount')
                    ->label('Sisa Hutang')
                    ->formatStateUsing(fn ($state) => rupiah($state))
                    ->badge()
                    ->color('danger'),
                Tables\Columns\TextColumn::make('due_date')
                    ->label('Jatuh Tempo')
                    ->date('d M Y')
                    ->badge()
                    ->color('danger'),
                Tables\Columns\TextColumn::make('debt_date')
                    ->label('Tanggal Hutang')
                    ->date('d M Y'),
            ])
            ->actions([
                Actions\Action::make('view')
                    ->label('Detail')
                    ->url(fn (Debt $record) => DebtResource::getUrl('view', ['record' => $record])),
            ]);
    }
}

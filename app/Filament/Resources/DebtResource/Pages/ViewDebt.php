<?php

namespace App\Filament\Resources\DebtResource\Pages;

use App\Filament\Resources\CustomerResource;
use App\Filament\Resources\DebtResource;
use App\Models\Debt;
use Filament\Actions;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Filament\Resources\Pages\ViewRecord;

class ViewDebt extends ViewRecord
{
    protected static string $resource = DebtResource::class;

    public function infolist(Schema $infolist): Schema
    {
        return $infolist
            ->schema([
                Section::make('Detail Nota')
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('invoice_number')->label('Nomor Nota')->copyable(),
                            TextEntry::make('customer.name')->label('Supplier')
                                ->url(fn (Debt $record) => $record->customer ? CustomerResource::getUrl('view', ['record' => $record->customer]) : null),
                            TextEntry::make('amount')->label('Nominal Hutang')->state(fn (Debt $record) => rupiah($record->amount)),
                            TextEntry::make('paid_amount')->label('Total Dibayar')->state(fn (Debt $record) => rupiah($record->paid_amount)),
                            TextEntry::make('remaining_amount')->label('Sisa Hutang')->state(fn (Debt $record) => rupiah($record->remaining_amount))
                                ->color(fn (Debt $record) => $record->remaining_amount > 0 ? 'warning' : 'success'),
                            TextEntry::make('status')->label('Status')
                                ->badge()
                                ->color(fn (Debt $record) => $record->status === Debt::STATUS_PAID ? 'success' : 'danger')
                                ->state(fn (Debt $record) => $record->status_label),
                            TextEntry::make('progress')->label('Progres Pembayaran')
                                ->state(fn (Debt $record) => $record->progress.'%')
                                ->badge()
                                ->color(fn (Debt $record) => $record->progress >= 100 ? 'success' : 'info'),
                            TextEntry::make('debt_date')->label('Tanggal Hutang')->date('d M Y'),
                            TextEntry::make('due_date')->label('Jatuh Tempo')->date('d M Y')->placeholder('-'),
                            TextEntry::make('created_at')->label('Dibuat')->dateTime('d M Y H:i'),
                            TextEntry::make('creator.name')->label('Dibuat Oleh')->placeholder('-'),
                            TextEntry::make('description')->label('Keterangan')->placeholder('-')->columnSpanFull(),
                        ]),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn () => auth()->user()?->can('update', $this->record)),
            Actions\DeleteAction::make()
                ->visible(fn () => auth()->user()?->can('delete', $this->record)),
        ];
    }
}

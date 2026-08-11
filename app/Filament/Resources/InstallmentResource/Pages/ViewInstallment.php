<?php

namespace App\Filament\Resources\InstallmentResource\Pages;

use App\Filament\Resources\InstallmentResource;
use App\Models\Installment;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Filament\Resources\Pages\ViewRecord;

class ViewInstallment extends ViewRecord
{
    protected static string $resource = InstallmentResource::class;

    public function infolist(Schema $infolist): Schema
    {
        return $infolist
            ->schema([
                Section::make('Detail Cicilan')
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('debt.invoice_number')->label('No. Nota')
                                ->url(fn (Installment $record) => $record->debt ? \App\Filament\Resources\DebtResource::getUrl('view', ['record' => $record->debt]) : null),
                            TextEntry::make('debt.customer.name')->label('Supplier'),
                            TextEntry::make('installment_date')->label('Tanggal')->date('d M Y'),
                            TextEntry::make('amount')->label('Nominal')->state(fn (Installment $record) => rupiah($record->amount)),
                            TextEntry::make('description')->label('Keterangan')->placeholder('-')->columnSpanFull(),
                            TextEntry::make('creator.name')->label('Dibuat Oleh')->placeholder('-'),
                            TextEntry::make('created_at')->label('Dicatat')->dateTime('d M Y H:i'),
                        ]),
                    ]),
            ]);
    }
}

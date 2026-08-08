<?php

namespace App\Filament\Resources\ReceivableInstallmentResource\Pages;

use App\Filament\Resources\ReceivableInstallmentResource;
use App\Models\ReceivableInstallment;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Filament\Resources\Pages\ViewRecord;

class ViewReceivableInstallment extends ViewRecord
{
    protected static string $resource = ReceivableInstallmentResource::class;

    public function infolist(Schema $infolist): Schema
    {
        return $infolist
            ->schema([
                Section::make('Detail Cicilan')
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('receivable.invoice_number')->label('No. Nota')
                                ->url(fn (ReceivableInstallment $record) => $record->receivable ? \App\Filament\Resources\ReceivableResource::getUrl('view', ['record' => $record->receivable]) : null),
                            TextEntry::make('receivable.party.name')->label('Debitur'),
                            TextEntry::make('installment_date')->label('Tanggal')->date('d M Y'),
                            TextEntry::make('amount')->label('Nominal')->state(fn (ReceivableInstallment $record) => rupiah($record->amount)),
                            TextEntry::make('description')->label('Keterangan')->placeholder('-')->columnSpanFull(),
                            TextEntry::make('creator.name')->label('Dibuat Oleh')->placeholder('-'),
                            TextEntry::make('created_at')->label('Dicatat')->dateTime('d M Y H:i'),
                        ]),
                    ]),
            ]);
    }
}

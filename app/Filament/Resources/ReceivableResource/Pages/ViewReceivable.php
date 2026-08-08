<?php

namespace App\Filament\Resources\ReceivableResource\Pages;

use App\Filament\Resources\ReceivablePartyResource;
use App\Filament\Resources\ReceivableResource;
use App\Models\Receivable;
use Filament\Actions;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Filament\Resources\Pages\ViewRecord;

class ViewReceivable extends ViewRecord
{
    protected static string $resource = ReceivableResource::class;

    public function infolist(Schema $infolist): Schema
    {
        return $infolist
            ->schema([
                Section::make('Detail Nota')
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('invoice_number')->label('Nomor Nota')->copyable(),
                            TextEntry::make('party.name')->label('Debitur')
                                ->url(fn (Receivable $record) => $record->party ? ReceivablePartyResource::getUrl('view', ['record' => $record->party]) : null),
                            TextEntry::make('amount')->label('Nominal Piutang')->state(fn (Receivable $record) => rupiah($record->amount)),
                            TextEntry::make('paid_amount')->label('Total Diterima')->state(fn (Receivable $record) => rupiah($record->paid_amount)),
                            TextEntry::make('remaining_amount')->label('Sisa Piutang')->state(fn (Receivable $record) => rupiah($record->remaining_amount))
                                ->color(fn (Receivable $record) => $record->remaining_amount > 0 ? 'warning' : 'success'),
                            TextEntry::make('status')->label('Status')
                                ->badge()
                                ->color(fn (Receivable $record) => $record->status === Receivable::STATUS_PAID ? 'success' : 'danger')
                                ->state(fn (Receivable $record) => $record->status_label),
                            TextEntry::make('progress')->label('Progres Penerimaan')
                                ->state(fn (Receivable $record) => $record->progress.'%')
                                ->badge()
                                ->color(fn (Receivable $record) => $record->progress >= 100 ? 'success' : 'info'),
                            TextEntry::make('receivable_date')->label('Tanggal Piutang')->date('d M Y'),
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

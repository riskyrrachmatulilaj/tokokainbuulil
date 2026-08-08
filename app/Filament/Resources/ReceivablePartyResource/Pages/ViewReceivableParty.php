<?php

namespace App\Filament\Resources\ReceivablePartyResource\Pages;

use App\Filament\Resources\ReceivablePartyResource;
use App\Models\ReceivableParty;
use App\Services\ReceivableStatementPdfService;
use Filament\Actions;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Filament\Resources\Pages\ViewRecord;

class ViewReceivableParty extends ViewRecord
{
    protected static string $resource = ReceivablePartyResource::class;

    public function infolist(Schema $infolist): Schema
    {
        return $infolist
            ->schema([
                Section::make('Data Pelanggan')
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('name')->label('Nama'),
                            TextEntry::make('phone')->label('Nomor Telepon')->placeholder('-'),
                            TextEntry::make('address')->label('Alamat')->placeholder('-')->columnSpanFull(),
                            TextEntry::make('created_at')->label('Dibuat')->dateTime('d M Y H:i'),
                            TextEntry::make('receivables_count')
                                ->label('Jumlah Nota')
                                ->state(fn (ReceivableParty $record) => $record->receivables()->count()),
                            TextEntry::make('total_remaining')
                                ->label('Total Sisa Piutang')
                                ->state(fn ($record) => rupiah($record->receivables()->sum('remaining_amount'))),
                        ]),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('share_pdf')
                ->label('Bagikan PDF Rincian Piutang')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(fn () => ReceivableStatementPdfService::generate($this->record)),
            Actions\EditAction::make()
                ->visible(fn () => auth()->user()?->can('update', $this->record)),
            Actions\DeleteAction::make()
                ->visible(fn () => auth()->user()?->can('delete', $this->record)),
        ];
    }
}

<?php

namespace App\Filament\Resources\ReceivablePartyResource\Pages;

use App\Exports\PartyImportTemplateExport;
use App\Filament\Actions\ImportPartiesAction;
use App\Filament\Resources\ReceivablePartyResource;
use App\Models\ReceivableParty;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Gate;

class ListReceivableParties extends ListRecords
{
    protected static string $resource = ReceivablePartyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('import_help')
                ->label('Petunjuk Import')
                ->icon('heroicon-o-question-mark-circle')
                ->color('gray')
                ->visible(fn (): bool => Gate::allows('import', ReceivableParty::class))
                ->modalHeading('Petunjuk Import Pelanggan')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Tutup')
                ->modalContent(fn () => view('filament.import.party-import-instructions'))
                ->extraModalFooterActions([
                    Actions\Action::make('downloadTemplate')
                        ->label('Unduh Template Excel')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->action(fn () => PartyImportTemplateExport::download(PartyImportTemplateExport::TYPE_DEBITUR)),
                ]),
            ImportPartiesAction::make(
                ReceivableParty::class,
                PartyImportTemplateExport::TYPE_DEBITUR,
                'Pelanggan',
            ),
            Actions\CreateAction::make(),
        ];
    }
}

<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Exports\PartyImportTemplateExport;
use App\Filament\Actions\ImportPartiesAction;
use App\Filament\Resources\CustomerResource;
use App\Models\Customer;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

class ListCustomers extends ListRecords
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('import_help')
                ->label('Petunjuk Import')
                ->icon('heroicon-o-question-mark-circle')
                ->color('gray')
                ->visible(fn (): bool => Gate::allows('import', Customer::class))
                ->modalHeading('Petunjuk Import Supplier')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Tutup')
                ->modalContent(fn () => view('filament.import.party-import-instructions'))
                ->extraModalFooterActions([
                    Actions\Action::make('downloadTemplate')
                        ->label('Unduh Template Excel')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->action(fn () => PartyImportTemplateExport::download(PartyImportTemplateExport::TYPE_PELANGGAN)),
                ]),
            ImportPartiesAction::make(
                Customer::class,
                PartyImportTemplateExport::TYPE_PELANGGAN,
                'Supplier',
            ),
            Actions\CreateAction::make()
                ->label('Tambah Supplier'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Semua Supplier')
                ->badge(fn () => Customer::count()),
            'with_debt' => Tab::make('Ada Sisa Hutang')
                ->badge(fn () => Customer::whereHas('debts', fn ($q) => $q->unpaid())->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('debts', fn ($q) => $q->unpaid())),
            'clear' => Tab::make('Lunas / Bebas Hutang')
                ->badge(fn () => Customer::whereDoesntHave('debts', fn ($q) => $q->unpaid())->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereDoesntHave('debts', fn ($q) => $q->unpaid())),
        ];
    }
}

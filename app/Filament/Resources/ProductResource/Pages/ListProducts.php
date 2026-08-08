<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Exports\ProductImportTemplateExport;
use App\Filament\Actions\ImportProductsAction;
use App\Filament\Resources\ProductResource;
use App\Models\Product;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Gate;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('import_help')
                ->label('Petunjuk Import')
                ->icon('heroicon-o-question-mark-circle')
                ->color('gray')
                ->visible(fn (): bool => Gate::allows('import', Product::class))
                ->modalHeading('Petunjuk Import Produk')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Tutup')
                ->modalContent(fn () => view('filament.import.product-import-instructions'))
                ->extraModalFooterActions([
                    Actions\Action::make('downloadTemplate')
                        ->label('Unduh Template Excel')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->action(fn () => ProductImportTemplateExport::download()),
                ]),
            ImportProductsAction::make(),
            Actions\CreateAction::make(),
        ];
    }
}

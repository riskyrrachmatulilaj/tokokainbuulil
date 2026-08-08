<?php

namespace App\Filament\Resources\ReceivableInstallmentResource\Pages;

use App\Filament\Resources\ReceivableInstallmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListReceivableInstallments extends ListRecords
{
    protected static string $resource = ReceivableInstallmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Cicilan'),
        ];
    }
}

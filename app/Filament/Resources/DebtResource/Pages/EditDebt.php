<?php

namespace App\Filament\Resources\DebtResource\Pages;

use App\Filament\Resources\DebtResource;
use App\Services\DebtService;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditDebt extends EditRecord
{
    protected static string $resource = DebtResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(DebtService::class)->updateDebt($record, $data);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}

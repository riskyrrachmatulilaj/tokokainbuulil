<?php

namespace App\Filament\Resources\ReceivableResource\Pages;

use App\Filament\Resources\ReceivableResource;
use App\Services\ReceivableService;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditReceivable extends EditRecord
{
    protected static string $resource = ReceivableResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(ReceivableService::class)->updateReceivable($record, $data);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}

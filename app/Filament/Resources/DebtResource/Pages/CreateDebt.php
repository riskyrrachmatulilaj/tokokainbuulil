<?php

namespace App\Filament\Resources\DebtResource\Pages;

use App\Filament\Resources\DebtResource;
use App\Services\DebtService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateDebt extends CreateRecord
{
    protected static string $resource = DebtResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(DebtService::class)->createDebt($data, auth()->user());
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

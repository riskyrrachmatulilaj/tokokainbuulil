<?php

namespace App\Filament\Resources\ReceivablePartyResource\Pages;

use App\Filament\Resources\ReceivablePartyResource;
use Filament\Resources\Pages\CreateRecord;

class CreateReceivableParty extends CreateRecord
{
    protected static string $resource = ReceivablePartyResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

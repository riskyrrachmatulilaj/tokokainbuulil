<?php

namespace App\Filament\Resources\ReceivablePartyResource\Pages;

use App\Filament\Resources\ReceivablePartyResource;
use Filament\Resources\Pages\EditRecord;

class EditReceivableParty extends EditRecord
{
    protected static string $resource = ReceivablePartyResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

<?php

namespace App\Filament\Resources\InstallmentResource\Pages;

use App\Filament\Resources\InstallmentResource;
use App\Services\PaymentService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class CreateInstallment extends CreateRecord
{
    protected static string $resource = InstallmentResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        try {
            return app(PaymentService::class)->recordInstallment($data, auth()->user());
        } catch (ValidationException $e) {
            Notification::make()
                ->danger()
                ->title('Pembayaran gagal')
                ->body(collect($e->errors())->flatten()->first())
                ->send();

            throw new Halt;
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

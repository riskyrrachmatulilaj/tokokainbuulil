<?php

namespace App\Filament\Resources\ReceivableInstallmentResource\Pages;

use App\Filament\Resources\ReceivableInstallmentResource;
use App\Services\ReceivablePaymentService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class CreateReceivableInstallment extends CreateRecord
{
    protected static string $resource = ReceivableInstallmentResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        try {
            return app(ReceivablePaymentService::class)->recordInstallment($data, auth()->user());
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

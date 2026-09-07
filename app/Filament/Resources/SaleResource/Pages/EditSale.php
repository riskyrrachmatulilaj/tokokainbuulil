<?php

namespace App\Filament\Resources\SaleResource\Pages;

use App\Filament\Resources\SaleResource;
use App\Models\Sale;
use App\Services\SaleService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class EditSale extends EditRecord
{
    protected static string $resource = SaleResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make()
                ->label('Batalkan Penjualan')
                ->requiresConfirmation()
                ->modalHeading('Batalkan Penjualan')
                ->modalDescription('Penjualan kredit yang sudah menerima pembayaran piutang tidak dapat dibatalkan.')
                ->visible(fn () => auth()->user()?->can('delete', $this->record))
                ->successNotificationTitle('Penjualan dibatalkan')
                ->action(function () {
                    try {
                        app(SaleService::class)->deleteSale($this->record);
                    } catch (ValidationException $e) {
                        Notification::make()
                            ->danger()
                            ->title('Gagal membatalkan')
                            ->body(collect($e->errors())->flatten()->first())
                            ->send();

                        throw new Halt;
                    }
                }),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $sale = $this->record->load(['items', 'party', 'receivable']);

        $data['items'] = $sale->items->map(fn ($item) => [
            'product_id' => $item->product_id,
            'quantity' => (float)$item->quantity == (int)$item->quantity ? (int)$item->quantity : (float)$item->quantity,
            'price' => (float)$item->price,
            'notes' => $item->notes,
            'subtotal' => (float)$item->subtotal,
        ])->toArray();

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Sale $record */
        try {
            return app(SaleService::class)->updateSale($record, $data, auth()->user());
        } catch (ValidationException $e) {
            Notification::make()
                ->danger()
                ->title('Gagal memperbarui penjualan')
                ->body(collect($e->errors())->flatten()->first())
                ->send();

            throw new Halt;
        } catch (\Throwable $e) {
            Notification::make()
                ->danger()
                ->title('Terjadi Kendala')
                ->body($e->getMessage() ?: 'Gagal menyimpan perubahan data penjualan.')
                ->send();

            throw new Halt;
        }
    }
}

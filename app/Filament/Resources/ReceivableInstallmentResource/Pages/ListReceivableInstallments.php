<?php

namespace App\Filament\Resources\ReceivableInstallmentResource\Pages;

use App\Filament\Resources\ReceivableInstallmentResource;
use App\Models\ReceivableInstallment;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListReceivableInstallments extends ListRecords
{
    protected static string $resource = ReceivableInstallmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Pembayaran Cicilan'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Semua')
                ->badge(fn () => ReceivableInstallment::count()),
            'today' => Tab::make('Hari Ini')
                ->badge(fn () => ReceivableInstallment::whereDate('installment_date', today())->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->whereDate('installment_date', today())),
            'this_month' => Tab::make('Bulan Ini')
                ->badge(fn () => ReceivableInstallment::whereMonth('installment_date', now()->month)->whereYear('installment_date', now()->year)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->whereMonth('installment_date', now()->month)->whereYear('installment_date', now()->year)),
        ];
    }
}

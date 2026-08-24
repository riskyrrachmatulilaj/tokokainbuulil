<?php

namespace App\Filament\Resources\DebtResource\Pages;

use App\Filament\Resources\DebtResource;
use App\Models\Debt;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListDebts extends ListRecords
{
    protected static string $resource = DebtResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Nota Hutang'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Semua')
                ->badge(fn () => Debt::count()),
            'unpaid' => Tab::make('Belum Lunas')
                ->badge(fn () => Debt::where('status', Debt::STATUS_UNPAID)->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', Debt::STATUS_UNPAID)),
            'in_progress' => Tab::make('Sedang Dicicil')
                ->badge(fn () => Debt::where('status', Debt::STATUS_UNPAID)->where('paid_amount', '>', 0)->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', Debt::STATUS_UNPAID)->where('paid_amount', '>', 0)),
            'overdue' => Tab::make('Lewat Jatuh Tempo')
                ->badge(fn () => Debt::overdue()->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query) => $query->overdue()),
            'paid' => Tab::make('Lunas')
                ->badge(fn () => Debt::where('status', Debt::STATUS_PAID)->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', Debt::STATUS_PAID)),
        ];
    }
}

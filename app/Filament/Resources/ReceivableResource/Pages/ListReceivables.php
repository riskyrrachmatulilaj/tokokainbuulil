<?php

namespace App\Filament\Resources\ReceivableResource\Pages;

use App\Filament\Resources\ReceivableResource;
use App\Models\Receivable;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListReceivables extends ListRecords
{
    protected static string $resource = ReceivableResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Nota Piutang'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Semua')
                ->badge(fn () => Receivable::count()),
            'unpaid' => Tab::make('Belum Lunas')
                ->badge(fn () => Receivable::where('status', Receivable::STATUS_UNPAID)->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', Receivable::STATUS_UNPAID)),
            'in_progress' => Tab::make('Sedang Dicicil')
                ->badge(fn () => Receivable::where('status', Receivable::STATUS_UNPAID)->where('paid_amount', '>', 0)->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', Receivable::STATUS_UNPAID)->where('paid_amount', '>', 0)),
            'overdue' => Tab::make('Lewat Jatuh Tempo')
                ->badge(fn () => Receivable::overdue()->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query) => $query->overdue()),
            'paid' => Tab::make('Lunas')
                ->badge(fn () => Receivable::where('status', Receivable::STATUS_PAID)->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', Receivable::STATUS_PAID)),
        ];
    }
}

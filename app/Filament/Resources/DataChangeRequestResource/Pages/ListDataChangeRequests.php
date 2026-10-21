<?php

namespace App\Filament\Resources\DataChangeRequestResource\Pages;

use App\Filament\Resources\DataChangeRequestResource;
use App\Models\DataChangeRequest;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListDataChangeRequests extends ListRecords
{
    protected static string $resource = DataChangeRequestResource::class;

    public function getTabs(): array
    {
        $user = auth()->user();
        $baseQuery = fn () => $user?->isKasir()
            ? DataChangeRequest::where('user_id', $user->id)
            : DataChangeRequest::query();

        return [
            'all' => Tab::make('Semua')
                ->badge(fn () => $baseQuery()->count()),
            'pending' => Tab::make('Menunggu Persetujuan')
                ->badge(fn () => $baseQuery()->where('status', DataChangeRequest::STATUS_PENDING)->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', DataChangeRequest::STATUS_PENDING)),
            'approved' => Tab::make('Disetujui')
                ->badge(fn () => $baseQuery()->where('status', DataChangeRequest::STATUS_APPROVED)->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', DataChangeRequest::STATUS_APPROVED)),
            'rejected' => Tab::make('Ditolak')
                ->badge(fn () => $baseQuery()->where('status', DataChangeRequest::STATUS_REJECTED)->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', DataChangeRequest::STATUS_REJECTED)),
        ];
    }
}

<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Actions;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Filament\Resources\Pages\ViewRecord;

class ViewProduct extends ViewRecord
{
    protected static string $resource = ProductResource::class;

    public function infolist(Schema $infolist): Schema
    {
        return $infolist
            ->schema([
                Section::make('Data Produk')
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('name')->label('Nama Produk'),
                            TextEntry::make('price')->label('Harga Jual')->state(fn ($record) => rupiah($record->price)),
                            TextEntry::make('is_active')->label('Status')
                                ->badge()
                                ->color(fn ($record) => $record->is_active ? 'success' : 'gray')
                                ->state(fn ($record) => $record->status_label),
                            TextEntry::make('updated_at')->label('Diperbarui')->dateTime('d M Y H:i'),
                            TextEntry::make('description')->label('Keterangan')->placeholder('-')->columnSpanFull(),
                        ]),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn () => auth()->user()?->can('update', $this->record)),
            Actions\DeleteAction::make()
                ->visible(fn () => auth()->user()?->can('delete', $this->record)),
        ];
    }
}

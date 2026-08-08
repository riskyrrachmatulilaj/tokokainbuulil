<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Filament\Resources\CustomerResource;
use App\Models\Customer;
use App\Services\DebtStatementPdfService;
use Filament\Actions;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Filament\Resources\Pages\ViewRecord;

class ViewCustomer extends ViewRecord
{
    protected static string $resource = CustomerResource::class;

    public function infolist(Schema $infolist): Schema
    {
        return $infolist
            ->schema([
                Section::make('Data Supplier')
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('name')->label('Nama'),
                            TextEntry::make('phone')->label('Nomor Telepon')->placeholder('-'),
                            TextEntry::make('address')->label('Alamat')->placeholder('-')->columnSpanFull(),
                            TextEntry::make('created_at')->label('Dibuat')->dateTime('d M Y H:i'),
                            TextEntry::make('debts_count')
                                ->label('Jumlah Nota')
                                ->state(fn (Customer $record) => $record->debts()->count()),
                            TextEntry::make('total_remaining')
                                ->label('Total Sisa Hutang')
                                ->state(fn ($record) => rupiah($record->debts()->sum('remaining_amount'))),
                        ]),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('share_pdf')
                ->label('Bagikan PDF Rincian Hutang')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(fn () => DebtStatementPdfService::generate($this->record)),
            Actions\EditAction::make()
                ->visible(fn () => auth()->user()?->can('update', $this->record)),
            Actions\DeleteAction::make()
                ->visible(fn () => auth()->user()?->can('delete', $this->record)),
        ];
    }
}

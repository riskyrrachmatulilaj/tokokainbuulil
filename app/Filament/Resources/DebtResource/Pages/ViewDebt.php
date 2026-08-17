<?php

namespace App\Filament\Resources\DebtResource\Pages;

use App\Filament\Resources\CustomerResource;
use App\Filament\Resources\DebtResource;
use App\Models\Debt;
use Filament\Actions;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Filament\Resources\Pages\ViewRecord;

class ViewDebt extends ViewRecord
{
    protected static string $resource = DebtResource::class;

    public function infolist(Schema $infolist): Schema
    {
        return $infolist
            ->schema([
                Section::make('Detail Nota')
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('invoice_number')->label('Nomor Nota')->copyable(),
                            TextEntry::make('customer.name')->label('Supplier')
                                ->url(fn (Debt $record) => $record->customer ? CustomerResource::getUrl('view', ['record' => $record->customer]) : null),
                            TextEntry::make('amount')->label('Nominal Hutang')->state(fn (Debt $record) => rupiah($record->amount)),
                            TextEntry::make('paid_amount')->label('Total Dibayar')->state(fn (Debt $record) => rupiah($record->paid_amount)),
                            TextEntry::make('remaining_amount')->label('Sisa Hutang')->state(fn (Debt $record) => rupiah($record->remaining_amount))
                                ->color(fn (Debt $record) => $record->remaining_amount > 0 ? 'warning' : 'success'),
                            TextEntry::make('status')->label('Status')
                                ->badge()
                                ->color(fn (Debt $record) => $record->status === Debt::STATUS_PAID ? 'success' : 'danger')
                                ->state(fn (Debt $record) => $record->status_label),
                            TextEntry::make('progress')->label('Progres Pembayaran')
                                ->state(fn (Debt $record) => $record->progress.'%')
                                ->badge()
                                ->color(fn (Debt $record) => $record->progress >= 100 ? 'success' : 'info'),
                            TextEntry::make('debt_date')->label('Tanggal Hutang')->date('d M Y'),
                            TextEntry::make('due_date')->label('Jatuh Tempo')->date('d M Y')->placeholder('-'),
                            TextEntry::make('created_at')->label('Dibuat')->dateTime('d M Y H:i'),
                            TextEntry::make('creator.name')->label('Dibuat Oleh')->placeholder('-'),
                            TextEntry::make('description')->label('Keterangan')->placeholder('-')->columnSpanFull(),
                        ]),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('lunasi')
                ->label('Lunasi Nota')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->button()
                ->visible(fn () => $this->record->status === Debt::STATUS_UNPAID && auth()->user()?->can('create', \App\Models\Installment::class))
                ->requiresConfirmation()
                ->modalHeading('Pelunasan Nota Hutang')
                ->modalDescription(fn () => "Lunasi seluruh sisa hutang nota {$this->record->invoice_number} sebesar ".rupiah($this->record->remaining_amount).'?')
                ->form([
                    \Filament\Forms\Components\Placeholder::make('info')
                        ->label('Sisa Hutang yang Akan Dilunasi')
                        ->content(fn () => rupiah($this->record->remaining_amount)),
                    \Filament\Forms\Components\DatePicker::make('installment_date')
                        ->label('Tanggal Pelunasan')
                        ->default(today())
                        ->required(),
                    \Filament\Forms\Components\Textarea::make('description')
                        ->label('Keterangan')
                        ->default('Pelunasan nota hutang')
                        ->rows(2),
                ])
                ->action(function (array $data) {
                    try {
                        app(\App\Services\PaymentService::class)->recordInstallment([
                            'debt_id' => $this->record->id,
                            'amount' => $this->record->remaining_amount,
                            'installment_date' => $data['installment_date'] ?? today(),
                            'description' => $data['description'] ?? 'Pelunasan nota hutang',
                        ], auth()->user());

                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('Nota hutang berhasil dilunasi')
                            ->body('Status nota kini telah Lunas.')
                            ->send();
                    } catch (\Illuminate\Validation\ValidationException $e) {
                        \Filament\Notifications\Notification::make()
                            ->danger()
                            ->title('Gagal melunasi nota')
                            ->body(collect($e->errors())->flatten()->first())
                            ->send();

                        throw new \Filament\Support\Exceptions\Halt;
                    }
                }),
            Actions\EditAction::make()
                ->visible(fn () => auth()->user()?->can('update', $this->record)),
            Actions\DeleteAction::make()
                ->visible(fn () => auth()->user()?->can('delete', $this->record)),
        ];
    }
}

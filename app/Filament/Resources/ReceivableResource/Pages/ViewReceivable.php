<?php

namespace App\Filament\Resources\ReceivableResource\Pages;

use App\Filament\Resources\ReceivablePartyResource;
use App\Filament\Resources\ReceivableResource;
use App\Models\Receivable;
use Filament\Actions;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Filament\Resources\Pages\ViewRecord;

class ViewReceivable extends ViewRecord
{
    protected static string $resource = ReceivableResource::class;

    public function infolist(Schema $infolist): Schema
    {
        return $infolist
            ->schema([
                Section::make('Detail Nota')
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('invoice_number')->label('Nomor Nota')->copyable(),
                            TextEntry::make('party.name')->label('Debitur')
                                ->url(fn (Receivable $record) => $record->party ? ReceivablePartyResource::getUrl('view', ['record' => $record->party]) : null),
                            TextEntry::make('amount')->label('Nominal Piutang')->state(fn (Receivable $record) => rupiah($record->amount)),
                            TextEntry::make('paid_amount')->label('Total Diterima')->state(fn (Receivable $record) => rupiah($record->paid_amount)),
                            TextEntry::make('remaining_amount')->label('Sisa Piutang')->state(fn (Receivable $record) => rupiah($record->remaining_amount))
                                ->color(fn (Receivable $record) => $record->remaining_amount > 0 ? 'warning' : 'success'),
                            TextEntry::make('status')->label('Status')
                                ->badge()
                                ->color(fn (Receivable $record) => $record->status === Receivable::STATUS_PAID ? 'success' : 'danger')
                                ->state(fn (Receivable $record) => $record->status_label),
                            TextEntry::make('progress')->label('Progres Penerimaan')
                                ->state(fn (Receivable $record) => $record->progress.'%')
                                ->badge()
                                ->color(fn (Receivable $record) => $record->progress >= 100 ? 'success' : 'info'),
                            TextEntry::make('receivable_date')->label('Tanggal Piutang')->date('d M Y'),
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
                ->visible(fn () => $this->record->status === Receivable::STATUS_UNPAID && auth()->user()?->can('create', \App\Models\ReceivableInstallment::class))
                ->requiresConfirmation()
                ->modalHeading('Pelunasan Nota Piutang')
                ->modalDescription(fn () => "Lunasi seluruh sisa piutang nota {$this->record->invoice_number} sebesar ".rupiah($this->record->remaining_amount).'?')
                ->form([
                    \Filament\Forms\Components\Placeholder::make('info')
                        ->label('Sisa Piutang yang Akan Dilunasi')
                        ->content(fn () => rupiah($this->record->remaining_amount)),
                    \Filament\Forms\Components\DatePicker::make('installment_date')
                        ->label('Tanggal Pelunasan')
                        ->default(today())
                        ->required(),
                    \Filament\Forms\Components\Textarea::make('description')
                        ->label('Keterangan')
                        ->default('Pelunasan nota piutang')
                        ->rows(2),
                ])
                ->action(function (array $data) {
                    try {
                        app(\App\Services\ReceivablePaymentService::class)->recordInstallment([
                            'receivable_id' => $this->record->id,
                            'amount' => $this->record->remaining_amount,
                            'installment_date' => $data['installment_date'] ?? today(),
                            'description' => $data['description'] ?? 'Pelunasan nota piutang',
                        ], auth()->user());

                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('Nota piutang berhasil dilunasi')
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

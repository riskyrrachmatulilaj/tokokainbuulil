<?php

namespace App\Filament\Resources\ReceivablePartyResource\RelationManagers;

use App\Models\Receivable;
use App\Services\ReceivableService;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Support\Exceptions\Halt;
use Illuminate\Validation\ValidationException;

class ReceivablesRelationManager extends RelationManager
{
    protected static string $relationship = 'receivables';

    protected static ?string $title = 'Nota Piutang';

    protected static string | \BackedEnum | null $icon = 'heroicon-o-banknotes';

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('invoice_number')
                    ->label('No. Nota')
                    ->disabled()
                    ->dehydrated(false)
                    ->visible(fn (?Receivable $record) => $record !== null),
                Forms\Components\TextInput::make('amount')
                    ->label('Nominal Piutang')
                    ->numeric()
                    ->required()
                    ->minValue(1)
                    ->prefix('Rp'),
                Forms\Components\DatePicker::make('receivable_date')
                    ->label('Tanggal Piutang')
                    ->default(today())
                    ->required(),
                Forms\Components\DatePicker::make('due_date')
                    ->label('Jatuh Tempo (opsional)')
                    ->afterOrEqual('receivable_date'),
                Forms\Components\Textarea::make('description')
                    ->label('Keterangan')
                    ->rows(2)
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('No. Nota')
                    ->searchable()
                    ->copyable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Total Piutang')
                    ->formatStateUsing(fn ($state) => rupiah($state))
                    ->sortable()
                    ->summarize(
                        Tables\Columns\Summarizers\Sum::make()
                            ->label('Total Piutang')
                            ->formatStateUsing(fn ($state) => rupiah($state))
                    ),
                Tables\Columns\TextColumn::make('paid_amount')
                    ->label('Sudah Dibayar')
                    ->formatStateUsing(fn ($state) => rupiah($state))
                    ->color('success')
                    ->sortable()
                    ->summarize(
                        Tables\Columns\Summarizers\Sum::make()
                            ->label('Total Masuk')
                            ->formatStateUsing(fn ($state) => rupiah($state))
                    ),
                Tables\Columns\TextColumn::make('remaining_amount')
                    ->label('Sisa Tagihan')
                    ->formatStateUsing(fn ($state) => rupiah($state))
                    ->badge()
                    ->color(fn ($state, Receivable $record) => (float) $state <= 0 ? 'success' : ((float) $record->paid_amount > 0 ? 'warning' : 'danger'))
                    ->sortable()
                    ->summarize(
                        Tables\Columns\Summarizers\Sum::make()
                            ->label('Total Sisa')
                            ->formatStateUsing(fn ($state) => rupiah($state))
                    ),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(function (Receivable $record) {
                        if ($record->status === Receivable::STATUS_PAID) {
                            return 'success';
                        }
                        return (float) $record->paid_amount > 0 ? 'warning' : 'danger';
                    })
                    ->formatStateUsing(function (Receivable $record) {
                        if ($record->status === Receivable::STATUS_PAID) {
                            return 'Lunas';
                        }
                        if ((float) $record->paid_amount > 0) {
                            return 'Dicicil (' . $record->progress . '%)';
                        }
                        return 'Belum Bayar';
                    }),
                Tables\Columns\TextColumn::make('receivable_date')
                    ->label('Tgl Nota')
                    ->date('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('due_date')
                    ->label('Jatuh Tempo')
                    ->formatStateUsing(fn (Receivable $record) => $record->due_date ? ($record->is_overdue ? $record->due_date->format('d M Y') . ' (Lewat)' : $record->due_date->format('d M Y')) : '-')
                    ->badge()
                    ->color(fn (Receivable $record) => $record->is_overdue ? 'danger' : 'gray')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status Pembayaran')
                    ->options([
                        Receivable::STATUS_UNPAID => 'Belum Lunas',
                        Receivable::STATUS_PAID => 'Lunas',
                    ]),
            ])
            ->headerActions([
                Actions\CreateAction::make()
                    ->label('Tambah Nota Piutang')
                    ->visible(fn () => auth()->user()?->can('create', Receivable::class))
                    ->action(function (array $data) {
                        try {
                            app(ReceivableService::class)->createReceivable(array_merge($data, [
                                'receivable_party_id' => $this->ownerRecord->id,
                            ]), auth()->user());

                            Notification::make()
                                ->success()
                                ->title('Nota piutang berhasil dibuat')
                                ->send();

                            throw new Halt;
                        } catch (ValidationException $e) {
                            Notification::make()
                                ->danger()
                                ->title('Gagal membuat nota')
                                ->body(collect($e->errors())->flatten()->first())
                                ->send();

                            throw new Halt;
                        }
                    }),
            ])
            ->actions([
                Actions\ViewAction::make()
                    ->label('Lihat')
                    ->url(fn (Receivable $record) => \App\Filament\Resources\ReceivableResource::getUrl('view', ['record' => $record])),
                Actions\Action::make('payInstallment')
                    ->label('Bayar Cicilan')
                    ->icon('heroicon-o-banknotes')
                    ->color('warning')
                    ->visible(fn (Receivable $record) => $record->status === Receivable::STATUS_UNPAID && auth()->user()?->can('create', \App\Models\ReceivableInstallment::class))
                    ->modalHeading(fn (Receivable $record) => "Bayar Cicilan Nota {$record->invoice_number}")
                    ->form(fn (Receivable $record) => [
                        Forms\Components\Placeholder::make('info_total')
                            ->label('Total Piutang Awal')
                            ->content(rupiah($record->amount)),
                        Forms\Components\Placeholder::make('info_paid')
                            ->label('Sudah Dibayar')
                            ->content(rupiah($record->paid_amount)),
                        Forms\Components\Placeholder::make('info_remaining')
                            ->label('Sisa Tagihan Saat Ini')
                            ->content(rupiah($record->remaining_amount)),
                        Forms\Components\TextInput::make('amount')
                            ->label('Jumlah Pembayaran Cicilan (Rp)')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->maxValue($record->remaining_amount)
                            ->prefix('Rp')
                            ->default($record->remaining_amount),
                        Forms\Components\DatePicker::make('installment_date')
                            ->label('Tanggal Pembayaran')
                            ->default(today())
                            ->required(),
                        Forms\Components\Textarea::make('description')
                            ->label('Catatan (opsional)')
                            ->default('Pembayaran cicilan')
                            ->rows(2),
                    ])
                    ->action(function (Receivable $record, array $data) {
                        try {
                            app(\App\Services\ReceivablePaymentService::class)->recordInstallment([
                                'receivable_id' => $record->id,
                                'amount' => $data['amount'],
                                'installment_date' => $data['installment_date'] ?? today(),
                                'description' => $data['description'] ?? 'Pembayaran cicilan',
                            ], auth()->user());

                            Notification::make()
                                ->success()
                                ->title("Cicilan nota {$record->invoice_number} berhasil dicatat")
                                ->send();
                        } catch (ValidationException $e) {
                            Notification::make()
                                ->danger()
                                ->title('Gagal mencatat cicilan')
                                ->body(collect($e->errors())->flatten()->first())
                                ->send();

                            throw new Halt;
                        }
                    }),
                Actions\EditAction::make()
                    ->label('Ubah')
                    ->visible(fn (Receivable $record) => auth()->user()?->can('update', $record))
                    ->using(function (Receivable $record, array $data): Receivable {
                        try {
                            return app(ReceivableService::class)->updateReceivable($record, $data);
                        } catch (ValidationException $e) {
                            Notification::make()
                                ->danger()
                                ->title('Gagal memperbarui nota')
                                ->body(collect($e->errors())->flatten()->first())
                                ->send();

                            throw new Halt;
                        }
                    }),
                Actions\DeleteAction::make()
                    ->label('Hapus')
                    ->visible(fn (Receivable $record) => auth()->user()?->can('delete', $record))
                    ->successNotificationTitle('Nota dihapus')
                    ->action(function (Receivable $record) {
                        try {
                            app(ReceivableService::class)->deleteReceivable($record);
                        } catch (ValidationException $e) {
                            Notification::make()
                                ->danger()
                                ->title('Gagal menghapus nota')
                                ->body(collect($e->errors())->flatten()->first())
                                ->send();

                            throw new Halt;
                        }
                    }),
            ])
            ->defaultSort('receivable_date', 'desc');
    }
}

<?php

namespace App\Filament\Resources\ReceivableResource\RelationManagers;

use App\Models\Receivable;
use App\Models\ReceivableInstallment;
use App\Services\ReceivablePaymentService;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Support\Exceptions\Halt;
use Illuminate\Validation\ValidationException;

class ReceivableInstallmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'installments';

    protected static ?string $title = 'Cicilan Piutang';

    protected static string | \BackedEnum | null $icon = 'heroicon-o-receipt-percent';

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('amount')
                    ->label('Nominal Cicilan')
                    ->numeric()
                    ->required()
                    ->minValue(1)
                    ->prefix('Rp')
                    ->default(fn () => $this->ownerRecord->remaining_amount)
                    ->suffixAction(
                        \Filament\Actions\Action::make('lunasi')
                            ->label('Lunasi')
                            ->icon('heroicon-m-check-badge')
                            ->color('success')
                            ->tooltip('Isi sisa piutang penuh')
                            ->action(fn ($set) => $set('amount', $this->ownerRecord->remaining_amount))
                    )
                    ->helperText(fn () => 'Sisa piutang: '.rupiah($this->ownerRecord->remaining_amount)),
                Forms\Components\DatePicker::make('installment_date')
                    ->label('Tanggal Cicilan')
                    ->default(today())
                    ->required(),
                Forms\Components\Textarea::make('description')
                    ->label('Keterangan')
                    ->rows(2),
            ])
            ->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('installment_date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Nominal')
                    ->formatStateUsing(fn ($state) => rupiah($state))
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('description')
                    ->label('Keterangan')
                    ->limit(40),
                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Oleh')
                    ->placeholder('-'),
            ])
            ->headerActions([
                Actions\Action::make('payFull')
                    ->label('Lunasi Nota')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->button()
                    ->visible(fn () => $this->ownerRecord->status === Receivable::STATUS_UNPAID && auth()->user()?->can('create', ReceivableInstallment::class))
                    ->requiresConfirmation()
                    ->modalHeading('Pelunasan Nota Piutang')
                    ->modalDescription(fn () => "Apakah Anda yakin ingin melunasi nota {$this->ownerRecord->invoice_number} sebesar ".rupiah($this->ownerRecord->remaining_amount).'?')
                    ->form([
                        Forms\Components\Placeholder::make('info')
                            ->label('Sisa Piutang yang Akan Dilunasi')
                            ->content(fn () => rupiah($this->ownerRecord->remaining_amount)),
                        Forms\Components\DatePicker::make('installment_date')
                            ->label('Tanggal Pelunasan')
                            ->default(today())
                            ->required(),
                        Forms\Components\Textarea::make('description')
                            ->label('Keterangan')
                            ->default('Pelunasan nota piutang')
                            ->rows(2),
                    ])
                    ->action(function (array $data) {
                        try {
                            app(ReceivablePaymentService::class)->recordInstallment([
                                'receivable_id' => $this->ownerRecord->id,
                                'amount' => $this->ownerRecord->remaining_amount,
                                'installment_date' => $data['installment_date'] ?? today(),
                                'description' => $data['description'] ?? 'Pelunasan nota piutang',
                            ], auth()->user());

                            Notification::make()
                                ->success()
                                ->title('Nota piutang berhasil dilunasi')
                                ->body('Status nota piutang kini telah Lunas.')
                                ->send();
                        } catch (ValidationException $e) {
                            Notification::make()
                                ->danger()
                                ->title('Gagal melunasi nota')
                                ->body(collect($e->errors())->flatten()->first())
                                ->send();

                            throw new Halt;
                        }
                    }),
                Actions\CreateAction::make()
                    ->label('Tambah Cicilan')
                    ->visible(fn () => $this->ownerRecord->status === Receivable::STATUS_UNPAID && auth()->user()?->can('create', ReceivableInstallment::class))
                    ->action(function (array $data) {
                        try {
                            app(ReceivablePaymentService::class)->recordInstallment(array_merge($data, [
                                'receivable_id' => $this->ownerRecord->id,
                            ]), auth()->user());

                            Notification::make()
                                ->success()
                                ->title('Cicilan berhasil dicatat')
                                ->send();

                            throw new Halt;
                        } catch (ValidationException $e) {
                            Notification::make()
                                ->danger()
                                ->title('Gagal mencatat cicilan')
                                ->body(collect($e->errors())->flatten()->first())
                                ->send();

                            throw new Halt;
                        }
                    }),
            ])
            ->actions([
                Actions\DeleteAction::make()
                    ->label('Batalkan')
                    ->requiresConfirmation()
                    ->modalHeading('Batalkan Cicilan')
                    ->modalDescription('Cicilan akan dibatalkan, saldo nota dikembalikan, dan riwayat pembayaran terkait dihapus.')
                    ->visible(fn (ReceivableInstallment $record) => auth()->user()?->can('delete', $record))
                    ->action(function (ReceivableInstallment $record) {
                        try {
                            app(ReceivablePaymentService::class)->cancelInstallment($record);

                            Notification::make()
                                ->success()
                                ->title('Cicilan dibatalkan')
                                ->send();
                        } catch (ValidationException $e) {
                            Notification::make()
                                ->danger()
                                ->title('Gagal membatalkan')
                                ->body(collect($e->errors())->flatten()->first())
                                ->send();

                            throw new Halt;
                        }
                    }),
            ])
            ->defaultSort('installment_date', 'desc');
    }
}

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
                    ->rows(2),
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
                    ->label('Nominal')
                    ->formatStateUsing(fn ($state) => rupiah($state))
                    ->sortable(),
                Tables\Columns\TextColumn::make('paid_amount')
                    ->label('Diterima')
                    ->formatStateUsing(fn ($state) => rupiah($state))
                    ->sortable(),
                Tables\Columns\TextColumn::make('remaining_amount')
                    ->label('Sisa')
                    ->formatStateUsing(fn ($state) => rupiah($state))
                    ->badge()
                    ->color(fn ($state) => (float) $state > 0 ? 'warning' : 'success')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (Receivable $record) => $record->status === Receivable::STATUS_PAID ? 'success' : 'danger')
                    ->formatStateUsing(fn (Receivable $record) => $record->status_label),
                Tables\Columns\TextColumn::make('receivable_date')
                    ->label('Tanggal Piutang')
                    ->date('d M Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        Receivable::STATUS_UNPAID => 'Belum Lunas',
                        Receivable::STATUS_PAID => 'Lunas',
                    ]),
            ])
            ->headerActions([
                Actions\CreateAction::make()
                    ->label('Tambah Nota')
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
                    ->url(fn (Receivable $record) => \App\Filament\Resources\ReceivableResource::getUrl('view', ['record' => $record])),
                Actions\DeleteAction::make()
                    ->visible(fn (Receivable $record) => auth()->user()?->can('delete', $record))
                    ->action(function (Receivable $record) {
                        try {
                            app(ReceivableService::class)->deleteReceivable($record);

                            Notification::make()
                                ->success()
                                ->title('Nota dihapus')
                                ->send();
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

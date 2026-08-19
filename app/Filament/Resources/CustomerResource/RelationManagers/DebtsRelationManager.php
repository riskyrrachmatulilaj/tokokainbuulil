<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use App\Models\Debt;
use App\Services\DebtService;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Support\Exceptions\Halt;
use Illuminate\Validation\ValidationException;

class DebtsRelationManager extends RelationManager
{
    protected static string $relationship = 'debts';

    protected static ?string $title = 'Nota Hutang';

    protected static string | \BackedEnum | null $icon = 'heroicon-o-banknotes';

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('invoice_number')
                    ->label('No. Nota')
                    ->disabled()
                    ->dehydrated(false)
                    ->visible(fn (?Debt $record) => $record !== null),
                Forms\Components\TextInput::make('amount')
                    ->label('Nominal Hutang')
                    ->numeric()
                    ->required()
                    ->minValue(1)
                    ->prefix('Rp'),
                Forms\Components\DatePicker::make('debt_date')
                    ->label('Tanggal Hutang')
                    ->default(today())
                    ->required(),
                Forms\Components\DatePicker::make('due_date')
                    ->label('Jatuh Tempo (opsional)')
                    ->afterOrEqual('debt_date'),
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
                    ->label('Nominal')
                    ->formatStateUsing(fn ($state) => rupiah($state))
                    ->sortable(),
                Tables\Columns\TextColumn::make('paid_amount')
                    ->label('Dibayar')
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
                    ->color(fn (Debt $record) => $record->status === Debt::STATUS_PAID ? 'success' : 'danger')
                    ->formatStateUsing(fn (Debt $record) => $record->status_label),
                Tables\Columns\TextColumn::make('debt_date')
                    ->label('Tanggal Hutang')
                    ->date('d M Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        Debt::STATUS_UNPAID => 'Belum Lunas',
                        Debt::STATUS_PAID => 'Lunas',
                    ]),
            ])
            ->headerActions([
                Actions\CreateAction::make()
                    ->label('Tambah Nota')
                    ->visible(fn () => auth()->user()?->can('create', Debt::class))
                    ->action(function (array $data) {
                        try {
                            app(DebtService::class)->createDebt(array_merge($data, [
                                'customer_id' => $this->ownerRecord->id,
                            ]), auth()->user());

                            Notification::make()
                                ->success()
                                ->title('Nota hutang berhasil dibuat')
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
                    ->url(fn (Debt $record) => \App\Filament\Resources\DebtResource::getUrl('view', ['record' => $record])),
                Actions\EditAction::make()
                    ->visible(fn (Debt $record) => auth()->user()?->can('update', $record))
                    ->using(function (Debt $record, array $data): Debt {
                        try {
                            return app(DebtService::class)->updateDebt($record, $data);
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
                    ->visible(fn (Debt $record) => auth()->user()?->can('delete', $record))
                    ->successNotificationTitle('Nota dihapus')
                    ->action(function (Debt $record) {
                        try {
                            app(DebtService::class)->deleteDebt($record);
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
            ->defaultSort('debt_date', 'desc');
    }
}

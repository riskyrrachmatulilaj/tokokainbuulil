<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReceivableResource\Pages;
use App\Filament\Resources\ReceivableResource\RelationManagers\ReceivableInstallmentsRelationManager;
use App\Models\ReceivableParty;
use App\Models\Receivable;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;

class ReceivableResource extends Resource
{
    protected static ?string $model = Receivable::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-arrow-trending-up';

    protected static string | \UnitEnum | null $navigationGroup = 'Manajemen Piutang';

    protected static ?string $navigationLabel = 'Piutang (Nota)';

    protected static ?string $modelLabel = 'Nota Piutang';

    protected static ?string $pluralModelLabel = 'Nota Piutang';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema([
                \Filament\Schemas\Components\Section::make('Detail Nota')
                    ->schema([
                        Forms\Components\TextInput::make('invoice_number')
                            ->label('Nomor Nota')
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpan(1),
                        Forms\Components\Select::make('receivable_party_id')
                            ->label('Debitur')
                            ->relationship('party', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')->label('Nama')->required()->maxLength(255),
                                Forms\Components\TextInput::make('phone')->label('Nomor Telepon')->tel()->maxLength(30),
                                Forms\Components\Textarea::make('address')->label('Alamat')->rows(2),
                            ])
                            ->columnSpan(1),
                        Forms\Components\TextInput::make('amount')
                            ->label('Nominal Piutang')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->prefix('Rp')
                            ->columnSpan(1),
                        Forms\Components\DatePicker::make('receivable_date')
                            ->label('Tanggal Piutang')
                            ->default(today())
                            ->required()
                            ->columnSpan(1),
                        Forms\Components\DatePicker::make('due_date')
                            ->label('Jatuh Tempo (opsional)')
                            ->afterOrEqual('receivable_date')
                            ->columnSpan(1),
                        Forms\Components\Textarea::make('description')
                            ->label('Keterangan')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('No. Nota')
                    ->searchable()
                    ->copyable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('party.name')
                    ->label('Debitur')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Receivable $record) => $record->party?->phone ?? ''),
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
                Tables\Columns\TextColumn::make('progress')
                    ->label('Progres')
                    ->formatStateUsing(fn (Receivable $record) => $record->progress.'%')
                    ->description(fn (Receivable $record) => rupiah($record->paid_amount).' dari '.rupiah($record->amount))
                    ->sortable()
                    ->numeric(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (Receivable $record) => $record->status === Receivable::STATUS_PAID ? 'success' : 'danger')
                    ->formatStateUsing(fn (Receivable $record) => $record->status_label),
                Tables\Columns\TextColumn::make('receivable_date')
                    ->label('Tanggal Piutang')
                    ->date('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('due_date')
                    ->label('Jatuh Tempo')
                    ->date('d M Y')
                    ->badge()
                    ->color(fn (Receivable $record) => $record->is_overdue ? 'danger' : 'gray')
                    ->placeholder('-')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('receivable_party_id')
                    ->label('Debitur')
                    ->options(fn () => ReceivableParty::query()->pluck('name', 'id')),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        Receivable::STATUS_UNPAID => 'Belum Lunas',
                        Receivable::STATUS_PAID => 'Lunas',
                    ]),
                Tables\Filters\TernaryFilter::make('overdue')
                    ->label('Jatuh Tempo')
                    ->queries(
                        true: fn ($query) => $query->overdue(),
                        false: fn ($query) => $query->whereNull('due_date')->orWhere('due_date', '>=', today()),
                    ),
                Tables\Filters\Filter::make('receivable_date')
                    ->form([
                        Forms\Components\DatePicker::make('receivable_from')
                            ->label('Dari Tanggal'),
                        Forms\Components\DatePicker::make('receivable_until')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['receivable_from'], fn ($q) => $q->where('receivable_date', '>=', $data['receivable_from']))
                            ->when($data['receivable_until'], fn ($q) => $q->where('receivable_date', '<=', $data['receivable_until']));
                    }),
            ])
            ->actions([
                Actions\ViewAction::make(),
                Actions\Action::make('lunasi')
                    ->label('Lunasi')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn (Receivable $record) => $record->status === Receivable::STATUS_UNPAID && auth()->user()?->can('create', \App\Models\ReceivableInstallment::class))
                    ->requiresConfirmation()
                    ->modalHeading(fn (Receivable $record) => "Pelunasan Nota {$record->invoice_number}")
                    ->form(fn (Receivable $record) => [
                        Forms\Components\Placeholder::make('info')
                            ->label('Pelanggan')
                            ->content($record->party?->name ?? '-'),
                        Forms\Components\Placeholder::make('remaining')
                            ->label('Sisa Piutang yang Akan Dilunasi')
                            ->content(rupiah($record->remaining_amount)),
                        Forms\Components\DatePicker::make('installment_date')
                            ->label('Tanggal Pelunasan')
                            ->default(today())
                            ->required(),
                        Forms\Components\Textarea::make('description')
                            ->label('Keterangan')
                            ->default('Pelunasan nota piutang')
                            ->rows(2),
                    ])
                    ->action(function (Receivable $record, array $data) {
                        try {
                            app(\App\Services\ReceivablePaymentService::class)->recordInstallment([
                                'receivable_id' => $record->id,
                                'amount' => $record->remaining_amount,
                                'installment_date' => $data['installment_date'] ?? today(),
                                'description' => $data['description'] ?? 'Pelunasan nota piutang',
                            ], auth()->user());

                            \Filament\Notifications\Notification::make()
                                ->success()
                                ->title("Nota {$record->invoice_number} berhasil dilunasi")
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
                    ->visible(fn (Receivable $record) => auth()->user()?->can('update', $record)),
                Actions\DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('Hapus Nota')
                    ->modalDescription('Nota yang sudah memiliki pembayaran tidak dapat dihapus.')
                    ->visible(fn (Receivable $record) => auth()->user()?->can('delete', $record)),
            ])
            ->bulkActions([
                Actions\DeleteBulkAction::make()
                    ->visible(fn () => auth()->user()?->isAdmin()),
            ])
            ->defaultSort('receivable_date', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            ReceivableInstallmentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReceivables::route('/'),
            'create' => Pages\CreateReceivable::route('/create'),
            'view' => Pages\ViewReceivable::route('/{record}'),
            'edit' => Pages\EditReceivable::route('/{record}/edit'),
        ];
    }
}

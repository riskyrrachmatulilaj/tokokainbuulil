<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DebtResource\Pages;
use App\Filament\Resources\DebtResource\RelationManagers\InstallmentsRelationManager;
use App\Models\Customer;
use App\Models\Debt;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;

class DebtResource extends Resource
{
    protected static ?string $model = Debt::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-banknotes';

    protected static string | \UnitEnum | null $navigationGroup = 'Manajemen Hutang';

    protected static ?string $navigationLabel = 'Hutang (Nota)';

    protected static ?string $modelLabel = 'Nota Hutang';

    protected static ?string $pluralModelLabel = 'Nota Hutang';

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
                        Forms\Components\Select::make('customer_id')
                            ->label('Supplier')
                            ->relationship('customer', 'name')
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
                            ->label('Nominal Hutang')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->prefix('Rp')
                            ->columnSpan(1),
                        Forms\Components\DatePicker::make('debt_date')
                            ->label('Tanggal Hutang')
                            ->default(today())
                            ->required()
                            ->columnSpan(1),
                        Forms\Components\DatePicker::make('due_date')
                            ->label('Jatuh Tempo (opsional)')
                            ->afterOrEqual('debt_date')
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
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Supplier')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Debt $record) => $record->customer?->phone ?? ''),
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
                Tables\Columns\TextColumn::make('progress')
                    ->label('Progres')
                    ->formatStateUsing(fn (Debt $record) => $record->progress.'%')
                    ->description(fn (Debt $record) => rupiah($record->paid_amount).' dari '.rupiah($record->amount))
                    ->sortable()
                    ->numeric(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (Debt $record) => $record->status === Debt::STATUS_PAID ? 'success' : 'danger')
                    ->formatStateUsing(fn (Debt $record) => $record->status_label),
                Tables\Columns\TextColumn::make('debt_date')
                    ->label('Tanggal Hutang')
                    ->date('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('due_date')
                    ->label('Jatuh Tempo')
                    ->date('d M Y')
                    ->badge()
                    ->color(fn (Debt $record) => $record->is_overdue ? 'danger' : 'gray')
                    ->placeholder('-')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('customer_id')
                    ->label('Supplier')
                    ->options(fn () => Customer::query()->pluck('name', 'id')),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        Debt::STATUS_UNPAID => 'Belum Lunas',
                        Debt::STATUS_PAID => 'Lunas',
                    ]),
                Tables\Filters\TernaryFilter::make('overdue')
                    ->label('Jatuh Tempo')
                    ->queries(
                        true: fn ($query) => $query->overdue(),
                        false: fn ($query) => $query->whereNull('due_date')->orWhere('due_date', '>=', today()),
                    ),
                Tables\Filters\Filter::make('debt_date')
                    ->form([
                        Forms\Components\DatePicker::make('debt_from')
                            ->label('Dari Tanggal'),
                        Forms\Components\DatePicker::make('debt_until')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['debt_from'], fn ($q) => $q->where('debt_date', '>=', $data['debt_from']))
                            ->when($data['debt_until'], fn ($q) => $q->where('debt_date', '<=', $data['debt_until']));
                    }),
            ])
            ->actions([
                Actions\ViewAction::make(),
                Actions\EditAction::make()
                    ->visible(fn (Debt $record) => auth()->user()?->can('update', $record)),
                Actions\DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('Hapus Nota')
                    ->modalDescription('Nota yang sudah memiliki pembayaran tidak dapat dihapus.')
                    ->visible(fn (Debt $record) => auth()->user()?->can('delete', $record)),
            ])
            ->bulkActions([
                Actions\DeleteBulkAction::make()
                    ->visible(fn () => auth()->user()?->isAdmin()),
            ])
            ->defaultSort('debt_date', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            InstallmentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDebts::route('/'),
            'create' => Pages\CreateDebt::route('/create'),
            'view' => Pages\ViewDebt::route('/{record}'),
            'edit' => Pages\EditDebt::route('/{record}/edit'),
        ];
    }
}


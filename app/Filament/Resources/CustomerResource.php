<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerResource\Pages;
use App\Filament\Resources\CustomerResource\RelationManagers\DebtsRelationManager;
use App\Models\Customer;
use App\Services\CustomerService;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-users';

    protected static string | \UnitEnum | null $navigationGroup = 'Manajemen Hutang';

    protected static ?string $navigationLabel = 'Supplier';

    protected static ?string $modelLabel = 'Supplier';

    protected static ?string $pluralModelLabel = 'Supplier';

    protected static ?int $navigationSort = 1;

    public static function canDelete(Model $record): bool
    {
        return parent::canDelete($record) && ! $record->has_unpaid_debts;
    }

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema([
                \Filament\Schemas\Components\Section::make('Data Supplier')
                    ->description('Informasi dasar supplier.')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('phone')
                            ->label('Nomor Telepon')
                            ->tel()
                            ->maxLength(30)
                            ->placeholder('08xxxxxxxxxx'),
                        Forms\Components\Textarea::make('address')
                            ->label('Alamat')
                            ->rows(3)
                            ->maxLength(1000)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable(['name', 'phone', 'address'])
                    ->sortable()
                    ->description(fn (Customer $record) => $record->phone ?? 'Tanpa nomor telepon')
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Telepon')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('address')
                    ->label('Alamat')
                    ->limit(30)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('debts_count')
                    ->label('Jumlah Nota')
                    ->counts('debts')
                    ->badge()
                    ->color('info')
                    ->sortable(),
                Tables\Columns\TextColumn::make('debts_sum_remaining_amount')
                    ->label('Total Sisa Hutang')
                    ->sum('debts', 'remaining_amount')
                    ->formatStateUsing(fn ($state) => rupiah($state))
                    ->badge()
                    ->color(fn ($state) => (float) $state > 0 ? 'warning' : 'success')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('has_unpaid_debts')
                    ->label('Status Hutang')
                    ->placeholder('Semua')
                    ->trueLabel('Memiliki hutang belum lunas')
                    ->falseLabel('Tidak memiliki hutang'),
            ])
            ->actions([
                Actions\ViewAction::make(),
                Actions\Action::make('share_pdf')
                    ->label('PDF Rincian Hutang')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->action(fn (Customer $record) => \App\Services\DebtStatementPdfService::generate($record)),
                Actions\EditAction::make()
                    ->visible(fn (Customer $record) => auth()->user()?->can('update', $record)),
                Actions\DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('Hapus Supplier')
                    ->modalDescription('Supplier hanya dapat dihapus apabila tidak memiliki nota yang belum lunas.')
                    ->visible(fn (Customer $record) => auth()->user()?->can('delete', $record))
                    ->action(function (Customer $record) {
                        try {
                            app(CustomerService::class)->deleteCustomer($record);

                            Notification::make()
                                ->success()
                                ->title('Supplier dihapus')
                                ->send();
                        } catch (ValidationException $e) {
                            Notification::make()
                                ->danger()
                                ->title('Gagal menghapus')
                                ->body(collect($e->errors())->flatten()->first())
                                ->send();

                            throw new Halt;
                        }
                    }),
            ])
            ->bulkActions([
                Actions\DeleteBulkAction::make()
                    ->visible(fn () => auth()->user()?->isAdmin()),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            DebtsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'view' => Pages\ViewCustomer::route('/{record}'),
            'edit' => Pages\EditCustomer::route('/{record}/edit'),
        ];
    }
}


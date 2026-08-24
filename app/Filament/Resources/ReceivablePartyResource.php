<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReceivablePartyResource\Pages;
use App\Filament\Resources\ReceivablePartyResource\RelationManagers\ReceivablesRelationManager;
use App\Models\ReceivableParty;
use App\Services\ReceivableStatementPdfService;
use App\Services\ReceivableService;
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

class ReceivablePartyResource extends Resource
{
    protected static ?string $model = ReceivableParty::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-identification';

    protected static string | \UnitEnum | null $navigationGroup = 'Manajemen Piutang';

    protected static ?string $navigationLabel = 'Pelanggan';

    protected static ?string $modelLabel = 'Pelanggan';

    protected static ?string $pluralModelLabel = 'Pelanggan';

    protected static ?int $navigationSort = 1;

    public static function canDelete(Model $record): bool
    {
        return parent::canDelete($record) && ! $record->has_unpaid_receivables;
    }

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema([
                \Filament\Schemas\Components\Section::make('Data Pelanggan')
                    ->description('Pihak yang memiliki piutang kepada usaha Anda.')
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
                    ->label('Nama Pelanggan')
                    ->searchable(['name', 'phone', 'address'])
                    ->sortable()
                    ->description(fn (ReceivableParty $record) => $record->phone ?: 'Tanpa nomor telepon')
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('phone')
                    ->label('No. Telepon / WA')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('address')
                    ->label('Alamat')
                    ->limit(35)
                    ->placeholder('-')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('receivables_count')
                    ->label('Jumlah Nota')
                    ->counts('receivables')
                    ->badge()
                    ->color('info')
                    ->sortable(),
                Tables\Columns\TextColumn::make('receivables_sum_remaining_amount')
                    ->label('Total Sisa Piutang')
                    ->sum('receivables', 'remaining_amount')
                    ->formatStateUsing(fn ($state) => rupiah($state))
                    ->badge()
                    ->color(fn ($state) => (float) $state > 0 ? 'danger' : 'success')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Terdaftar Sejak')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('has_unpaid_receivables')
                    ->label('Filter Status Piutang')
                    ->placeholder('Semua Pelanggan')
                    ->trueLabel('Hanya yang punya sisa tagihan belum lunas')
                    ->falseLabel('Pelanggan lunas / tanpa tagihan'),
            ])
            ->actions([
                Actions\ViewAction::make()
                    ->label('Lihat'),
                Actions\Action::make('share_pdf')
                    ->label('Cetak PDF / Rincian')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->action(fn (ReceivableParty $record) => ReceivableStatementPdfService::generate($record)),
                Actions\EditAction::make()
                    ->label('Ubah')
                    ->visible(fn (ReceivableParty $record) => auth()->user()?->can('update', $record)),
                Actions\DeleteAction::make()
                    ->label('Hapus')
                    ->requiresConfirmation()
                    ->modalHeading('Hapus Pelanggan')
                    ->modalDescription('Pelanggan hanya dapat dihapus apabila tidak memiliki nota piutang yang belum lunas.')
                    ->visible(fn (ReceivableParty $record) => auth()->user()?->can('delete', $record))
                    ->successNotificationTitle('Pelanggan dihapus')
                    ->action(function (ReceivableParty $record) {
                        try {
                            app(ReceivableService::class)->deleteParty($record);
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
            ReceivablesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReceivableParties::route('/'),
            'create' => Pages\CreateReceivableParty::route('/create'),
            'view' => Pages\ViewReceivableParty::route('/{record}'),
            'edit' => Pages\EditReceivableParty::route('/{record}/edit'),
        ];
    }
}

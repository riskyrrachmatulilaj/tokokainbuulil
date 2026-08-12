<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityLogResource\Pages;
use App\Models\ActivityLog;
use App\Models\User;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ActivityLogResource extends Resource
{
    protected static ?string $model = ActivityLog::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static string | \UnitEnum | null $navigationGroup = 'Manajemen System';

    protected static ?string $navigationLabel = 'Log Aktivitas';

    protected static ?string $modelLabel = 'Log Aktivitas';

    protected static ?string $pluralModelLabel = 'Log Aktivitas';

    protected static ?int $navigationSort = 10;

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('created_at')
                    ->label('Waktu Aktivitas')
                    ->disabled(),
                Forms\Components\TextInput::make('user_name')
                    ->label('Pengguna')
                    ->disabled(),
                Forms\Components\TextInput::make('module')
                    ->label('Modul')
                    ->disabled(),
                Forms\Components\TextInput::make('action')
                    ->label('Tindakan')
                    ->disabled(),
                Forms\Components\Textarea::make('description')
                    ->label('Deskripsi')
                    ->rows(3)
                    ->columnSpanFull()
                    ->disabled(),
                Forms\Components\KeyValue::make('properties')
                    ->label('Rincian Data')
                    ->columnSpanFull()
                    ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime('d M Y H:i:s')
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('user_name')
                    ->label('Pengguna')
                    ->searchable()
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('module')
                    ->label('Modul')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'Penjualan' => 'success',
                        'Piutang' => 'info',
                        'Hutang' => 'warning',
                        'Produk' => 'primary',
                        'Pengguna' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('action')
                    ->label('Tindakan')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'create' => 'Tambah',
                        'update' => 'Ubah',
                        'delete' => 'Hapus',
                        'pay' => 'Pembayaran',
                        'cancel' => 'Batal',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state) => match ($state) {
                        'create' => 'success',
                        'update' => 'warning',
                        'delete' => 'danger',
                        'pay' => 'info',
                        'cancel' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('description')
                    ->label('Deskripsi')
                    ->searchable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP Address')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('module')
                    ->label('Modul')
                    ->options(ActivityLog::moduleOptions()),
                Tables\Filters\SelectFilter::make('user_id')
                    ->label('Pengguna')
                    ->options(fn () => User::pluck('name', 'id')->toArray()),
            ])
            ->actions([
                Actions\ViewAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivityLogs::route('/'),
        ];
    }
}

<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-shopping-bag';

    protected static string | \UnitEnum | null $navigationGroup = 'Kasir';

    protected static ?string $navigationLabel = 'Produk';

    protected static ?string $modelLabel = 'Produk';

    protected static ?string $pluralModelLabel = 'Produk';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema([
                \Filament\Schemas\Components\Section::make('Data Produk')
                    ->description('Daftar barang yang dijual beserta harga jualnya.')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Produk')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('contoh: Kain Batik Sekar Jagad (2 m)'),
                        Forms\Components\TextInput::make('price')
                            ->label('Harga Jual')
                            ->numeric()
                            ->required()
                            ->minValue(0.01)
                            ->step('0.01')
                            ->prefix('Rp')
                            ->columnSpan(1),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktif dijual')
                            ->default(true)
                            ->helperText('Produk nonaktif tidak muncul di layar kasir.')
                            ->columnSpan(1),
                        Forms\Components\Toggle::make('track_stock')
                            ->label('Lacak Stok Produk')
                            ->default(false)
                            ->live()
                            ->helperText('Aktifkan jika produk ini memiliki stok terbatas yang ingin dipantau.')
                            ->columnSpan(1),
                        Forms\Components\TextInput::make('stock')
                            ->label('Jumlah Stok Tersedia')
                            ->numeric()
                            ->visible(fn ($get) => (bool) $get('track_stock'))
                            ->required(fn ($get) => (bool) $get('track_stock'))
                            ->minValue(0)
                            ->step('0.01')
                            ->placeholder('contoh: 25.5')
                            ->columnSpan(1),
                        Forms\Components\Textarea::make('description')
                            ->label('Keterangan')
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
                    ->label('Nama Produk')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->description(fn (Product $record) => $record->description ?? 'Tanpa keterangan')
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('price')
                    ->label('Harga Jual')
                    ->formatStateUsing(fn ($state) => rupiah($state))
                    ->sortable()
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('stock_status')
                    ->label('Stok')
                    ->badge()
                    ->state(fn (Product $record) => $record->stock_label)
                    ->color(function (Product $record) {
                        if (! $record->track_stock) {
                            return 'gray';
                        }
                        if ((float) $record->stock <= 0) {
                            return 'danger';
                        }
                        if ((float) $record->stock <= 5) {
                            return 'warning';
                        }
                        return 'success';
                    }),
                Tables\Columns\TextColumn::make('sale_items_sum_quantity')
                    ->label('Terjual')
                    ->sum('saleItems', 'quantity')
                    ->formatStateUsing(fn ($state) => formatQuantity($state))
                    ->badge()
                    ->color('gray')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status')
                    ->placeholder('Semua')
                    ->trueLabel('Aktif')
                    ->falseLabel('Nonaktif'),
                Tables\Filters\SelectFilter::make('track_stock')
                    ->label('Pelacakan Stok')
                    ->options([
                        '1' => 'Stok Dilacak',
                        '0' => 'Stok Tanpa Batas',
                    ]),
            ])
            ->actions([
                Actions\ViewAction::make(),
                Actions\EditAction::make()
                    ->visible(fn (Product $record) => auth()->user()?->can('update', $record)),
                Actions\DeleteAction::make()
                    ->visible(fn (Product $record) => auth()->user()?->can('delete', $record)),
            ])
            ->bulkActions([
                Actions\BulkAction::make('batch_edit')
                    ->label('Edit Massal')
                    ->icon('heroicon-o-pencil-square')
                    ->color('warning')
                    ->visible(fn () => auth()->user()?->isAdmin())
                    ->form([
                        Forms\Components\Select::make('price_mode')
                            ->label('Ubah Harga')
                            ->options([
                                'no_change' => 'Tidak Diubah',
                                'fixed' => 'Harga Tetap (Rp)',
                                'percentage_increase' => 'Naik (%)',
                                'percentage_decrease' => 'Turun (%)',
                            ])
                            ->default('no_change')
                            ->live()
                            ->required(),
                        Forms\Components\TextInput::make('price_value')
                            ->label('Nilai')
                            ->numeric()
                            ->minValue(0)
                            ->step('0.01')
                            ->visible(fn ($get) => $get('price_mode') !== 'no_change')
                            ->required(fn ($get) => $get('price_mode') !== 'no_change')
                            ->prefix(fn ($get) => $get('price_mode') === 'fixed' ? 'Rp' : null)
                            ->suffix(fn ($get) => str_contains($get('price_mode') ?? '', 'percentage') ? '%' : null),
                        Forms\Components\Select::make('status_mode')
                            ->label('Ubah Status')
                            ->options([
                                'no_change' => 'Tidak Diubah',
                                'activate' => 'Aktifkan Semua',
                                'deactivate' => 'Nonaktifkan Semua',
                            ])
                            ->default('no_change')
                            ->required(),
                        Forms\Components\Select::make('description_mode')
                            ->label('Ubah Keterangan')
                            ->options([
                                'no_change' => 'Tidak Diubah',
                                'overwrite' => 'Timpa Keterangan',
                                'clear' => 'Hapus Keterangan',
                            ])
                            ->default('no_change')
                            ->live()
                            ->required(),
                        Forms\Components\Textarea::make('description_value')
                            ->label('Keterangan Baru')
                            ->rows(2)
                            ->visible(fn ($get) => $get('description_mode') === 'overwrite')
                            ->required(fn ($get) => $get('description_mode') === 'overwrite'),
                    ])
                    ->action(function (\Illuminate\Support\Collection $records, array $data): void {
                        foreach ($records as $product) {
                            $updates = [];

                            // Harga
                            if ($data['price_mode'] === 'fixed') {
                                $updates['price'] = $data['price_value'];
                            } elseif ($data['price_mode'] === 'percentage_increase') {
                                $updates['price'] = round((float) $product->price * (1 + $data['price_value'] / 100), 2);
                            } elseif ($data['price_mode'] === 'percentage_decrease') {
                                $updates['price'] = round((float) $product->price * (1 - $data['price_value'] / 100), 2);
                            }

                            // Status
                            if ($data['status_mode'] === 'activate') {
                                $updates['is_active'] = true;
                            } elseif ($data['status_mode'] === 'deactivate') {
                                $updates['is_active'] = false;
                            }

                            // Keterangan
                            if ($data['description_mode'] === 'overwrite') {
                                $updates['description'] = $data['description_value'];
                            } elseif ($data['description_mode'] === 'clear') {
                                $updates['description'] = null;
                            }

                            if (! empty($updates)) {
                                $product->update($updates);
                            }
                        }

                        \Filament\Notifications\Notification::make()
                            ->title('Berhasil')
                            ->body($records->count() . ' produk berhasil diperbarui.')
                            ->success()
                            ->send();
                    })
                    ->deselectRecordsAfterCompletion()
                    ->requiresConfirmation()
                    ->modalHeading('Edit Produk Massal')
                    ->modalDescription('Pilih perubahan yang ingin diterapkan ke produk terpilih.')
                    ->modalSubmitActionLabel('Terapkan Perubahan'),

                Actions\BulkAction::make('bulk_activate')
                    ->label('Aktifkan')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn () => auth()->user()?->isAdmin())
                    ->action(function (\Illuminate\Support\Collection $records): void {
                        $records->each(fn (Product $product) => $product->update(['is_active' => true]));

                        \Filament\Notifications\Notification::make()
                            ->title($records->count() . ' produk diaktifkan')
                            ->success()
                            ->send();
                    })
                    ->deselectRecordsAfterCompletion()
                    ->requiresConfirmation(),

                Actions\BulkAction::make('bulk_deactivate')
                    ->label('Nonaktifkan')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn () => auth()->user()?->isAdmin())
                    ->action(function (\Illuminate\Support\Collection $records): void {
                        $records->each(fn (Product $product) => $product->update(['is_active' => false]));

                        \Filament\Notifications\Notification::make()
                            ->title($records->count() . ' produk dinonaktifkan')
                            ->warning()
                            ->send();
                    })
                    ->deselectRecordsAfterCompletion()
                    ->requiresConfirmation(),

                Actions\DeleteBulkAction::make()
                    ->visible(fn () => auth()->user()?->isAdmin()),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'view' => Pages\ViewProduct::route('/{record}'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}

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
                \Filament\Schemas\Components\Section::make('Detail Nota Piutang')
                    ->schema([
                        Forms\Components\TextInput::make('invoice_number')
                            ->label('Nomor Nota')
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpan(1),
                        Forms\Components\Select::make('receivable_party_id')
                            ->label('Pelanggan')
                            ->relationship('party', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')->label('Nama Pelanggan')->required()->maxLength(255),
                                Forms\Components\TextInput::make('phone')->label('Nomor Telepon / WhatsApp')->tel()->maxLength(30),
                                Forms\Components\Textarea::make('address')->label('Alamat')->rows(2),
                            ])
                            ->columnSpan(1),
                        Forms\Components\TextInput::make('amount')
                            ->label('Total Piutang (Nominal Bon)')
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
                            ->label('Catatan / Keterangan')
                            ->rows(3)
                            ->placeholder('Contoh: Pengambilan kain katun 2 roll')
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
                    ->label('Pelanggan')
                    ->searchable(['name', 'phone'])
                    ->sortable()
                    ->description(fn (Receivable $record) => $record->party?->phone ?: 'Tanpa no. telepon'),
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
                    ->formatStateUsing(function (Receivable $record) {
                        if (! $record->due_date) {
                            return '-';
                        }
                        if ($record->is_overdue) {
                            return $record->due_date->format('d M Y') . ' (Lewat)';
                        }
                        return $record->due_date->format('d M Y');
                    })
                    ->badge()
                    ->color(fn (Receivable $record) => $record->is_overdue ? 'danger' : 'gray')
                    ->sortable(),
                Tables\Columns\TextColumn::make('progress')
                    ->label('Rincian Progres')
                    ->formatStateUsing(fn (Receivable $record) => $record->progress.'% ('.rupiah($record->paid_amount).' / '.rupiah($record->amount).')')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('receivable_party_id')
                    ->label('Pilih Pelanggan')
                    ->options(fn () => ReceivableParty::query()->orderBy('name')->pluck('name', 'id'))
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status Lunas')
                    ->options([
                        Receivable::STATUS_UNPAID => 'Belum Lunas',
                        Receivable::STATUS_PAID => 'Lunas',
                    ]),
                Tables\Filters\TernaryFilter::make('overdue')
                    ->label('Status Jatuh Tempo')
                    ->placeholder('Semua')
                    ->trueLabel('Hanya yang lewat jatuh tempo')
                    ->falseLabel('Belum lewat jatuh tempo')
                    ->queries(
                        true: fn ($query) => $query->overdue(),
                        false: fn ($query) => $query->where(fn ($q) => $q->whereNull('due_date')->orWhere('due_date', '>=', today())),
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
                Actions\ViewAction::make()
                    ->label('Lihat'),
                Actions\Action::make('payInstallment')
                    ->label('Bayar Cicilan')
                    ->icon('heroicon-o-banknotes')
                    ->color('warning')
                    ->visible(fn (Receivable $record) => $record->status === Receivable::STATUS_UNPAID && auth()->user()?->can('create', \App\Models\ReceivableInstallment::class))
                    ->modalHeading(fn (Receivable $record) => "Bayar Cicilan Nota {$record->invoice_number}")
                    ->form(fn (Receivable $record) => [
                        Forms\Components\Placeholder::make('info_party')
                            ->label('Pelanggan')
                            ->content($record->party?->name ?? '-'),
                        Forms\Components\Placeholder::make('info_total')
                            ->label('Total Piutang Awal')
                            ->content(rupiah($record->amount)),
                        Forms\Components\Placeholder::make('info_paid')
                            ->label('Sudah Dibayar Sebelumnya')
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
                            ->default($record->remaining_amount)
                            ->helperText('Masukkan nominal uang yang dibayarkan pelanggan.'),
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

                            \Filament\Notifications\Notification::make()
                                ->success()
                                ->title("Pembayaran cicilan nota {$record->invoice_number} berhasil dicatat")
                                ->send();
                        } catch (\Illuminate\Validation\ValidationException $e) {
                            \Filament\Notifications\Notification::make()
                                ->danger()
                                ->title('Gagal mencatat cicilan')
                                ->body(collect($e->errors())->flatten()->first())
                                ->send();

                            throw new \Filament\Support\Exceptions\Halt;
                        }
                    }),
                Actions\Action::make('lunasi')
                    ->label('Lunasi')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn (Receivable $record) => $record->status === Receivable::STATUS_UNPAID && auth()->user()?->can('create', \App\Models\ReceivableInstallment::class))
                    ->requiresConfirmation()
                    ->modalHeading(fn (Receivable $record) => "Pelunasan Nota {$record->invoice_number}")
                    ->modalDescription(fn (Receivable $record) => "Apakah Anda yakin ingin melunasi seluruh sisa piutang nota {$record->invoice_number} sebesar ".rupiah($record->remaining_amount).'?')
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
                            ->default('Pelunasan penuh nota piutang')
                            ->rows(2),
                    ])
                    ->action(function (Receivable $record, array $data) {
                        try {
                            app(\App\Services\ReceivablePaymentService::class)->recordInstallment([
                                'receivable_id' => $record->id,
                                'amount' => $record->remaining_amount,
                                'installment_date' => $data['installment_date'] ?? today(),
                                'description' => $data['description'] ?? 'Pelunasan penuh nota piutang',
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
                    ->label('Ubah')
                    ->visible(fn (Receivable $record) => auth()->user()?->can('update', $record)),
                Actions\DeleteAction::make()
                    ->label('Hapus')
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

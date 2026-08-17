<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReceivableInstallmentResource\Pages;
use App\Models\Receivable;
use App\Models\ReceivableInstallment;
use App\Models\ReceivableParty;
use App\Services\ReceivablePaymentService;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;

class ReceivableInstallmentResource extends Resource
{
    protected static ?string $model = ReceivableInstallment::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-receipt-percent';

    protected static string | \UnitEnum | null $navigationGroup = 'Transaksi Piutang';

    protected static ?string $navigationLabel = 'Cicilan Piutang';

    protected static ?string $modelLabel = 'Cicilan';

    protected static ?string $pluralModelLabel = 'Cicilan Piutang';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema([
                \Filament\Schemas\Components\Section::make('Tambah Cicilan')
                    ->description('Pembayaran cicilan untuk satu nota piutang.')
                    ->schema([
                        Forms\Components\Select::make('party_id')
                            ->label('Pelanggan')
                            ->options(fn () => ReceivableParty::query()
                                ->whereHas('receivables', fn ($q) => $q->unpaid())
                                ->orderBy('name')
                                ->pluck('name', 'id'))
                            ->required()
                            ->searchable()
                            ->preload()
                            ->reactive()
                            ->afterStateUpdated(fn ($set) => $set('receivable_id', null))
                            ->helperText('Pilih pelanggan terlebih dahulu')
                            ->columnSpanFull(),
                        Forms\Components\Select::make('receivable_id')
                            ->label('Nota')
                            ->options(function ($get) {
                                $partyId = $get('party_id');
                                if (! $partyId) {
                                    return [];
                                }

                                return Receivable::query()
                                    ->unpaid()
                                    ->where('receivable_party_id', $partyId)
                                    ->get()
                                    ->mapWithKeys(fn (Receivable $receivable) => [
                                        $receivable->id => "{$receivable->invoice_number} (Sisa: ".rupiah($receivable->remaining_amount).')',
                                    ]);
                            })
                            ->required()
                            ->searchable()
                            ->preload()
                            ->reactive()
                            ->disabled(fn ($get) => ! $get('party_id'))
                            ->afterStateUpdated(fn ($set, ?string $state) => $set('remaining_hint', Receivable::find($state)?->remaining_amount))
                            ->helperText(fn ($get) => ! $get('party_id')
                                ? 'Pilih pelanggan terlebih dahulu'
                                : (($remaining = $get('remaining_hint'))
                                    ? 'Sisa piutang nota ini: '.rupiah($remaining)
                                    : 'Pilih nota'))
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('amount')
                            ->label('Nominal Cicilan')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->prefix('Rp')
                            ->suffixAction(
                                \Filament\Actions\Action::make('lunasi')
                                    ->label('Lunasi Nota')
                                    ->icon('heroicon-m-check-badge')
                                    ->color('success')
                                    ->tooltip('Isi otomatis dengan sisa tagihan nota ini (Lunasi Penuh)')
                                    ->action(function ($set, $get) {
                                        $receivableId = $get('receivable_id');
                                        if ($receivableId && ($receivable = Receivable::find($receivableId))) {
                                            $set('amount', $receivable->remaining_amount);
                                        }
                                    })
                                    ->visible(fn ($get) => (bool) $get('receivable_id'))
                            )
                            ->helperText(fn ($get) => $get('receivable_id') ? 'Klik tombol ikon di kanan kolom untuk mengisi sisa piutang nota secara otomatis' : null)
                            ->columnSpan(1),
                        Forms\Components\DatePicker::make('installment_date')
                            ->label('Tanggal Cicilan')
                            ->default(today())
                            ->required()
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
                Tables\Columns\TextColumn::make('receivable.invoice_number')
                    ->label('No. Nota')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->url(fn (ReceivableInstallment $record) => $record->receivable ? ReceivableResource::getUrl('view', ['record' => $record->receivable]) : null),
                Tables\Columns\TextColumn::make('receivable.party.name')
                    ->label('Pelanggan')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('installment_date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Nominal')
                    ->formatStateUsing(fn ($state) => rupiah($state))
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Keterangan')
                    ->limit(40),
                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Oleh')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dicatat')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('receivable.receivable_party_id')
                    ->label('Pelanggan')
                    ->relationship('receivable.party', 'name')
                    ->searchable(),
                Tables\Filters\Filter::make('installment_date')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('Dari'),
                        Forms\Components\DatePicker::make('until')->label('Sampai'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn ($q) => $q->where('installment_date', '>=', $data['from']))
                            ->when($data['until'], fn ($q) => $q->where('installment_date', '<=', $data['until']));
                    }),
            ])
            ->actions([
                Actions\ViewAction::make(),
                Actions\DeleteAction::make()
                    ->label('Batalkan')
                    ->requiresConfirmation()
                    ->visible(fn (ReceivableInstallment $record) => auth()->user()?->can('delete', $record)),
            ])
            ->defaultSort('installment_date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReceivableInstallments::route('/'),
            'create' => Pages\CreateReceivableInstallment::route('/create'),
            'view' => Pages\ViewReceivableInstallment::route('/{record}'),
        ];
    }
}

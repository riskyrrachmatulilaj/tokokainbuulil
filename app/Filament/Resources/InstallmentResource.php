<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InstallmentResource\Pages;
use App\Models\Customer;
use App\Models\Debt;
use App\Models\Installment;
use App\Services\PaymentService;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;

class InstallmentResource extends Resource
{
    protected static ?string $model = Installment::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-receipt-percent';

    protected static string | \UnitEnum | null $navigationGroup = 'Transaksi Hutang';

    protected static ?string $navigationLabel = 'Cicilan Nota Hutang';

    protected static ?string $modelLabel = 'Cicilan';

    protected static ?string $pluralModelLabel = 'Cicilan Nota Hutang';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema([
                \Filament\Schemas\Components\Section::make('Tambah Cicilan')
                    ->description('Pembayaran cicilan untuk satu nota hutang.')
                    ->schema([
                        Forms\Components\Select::make('customer_id')
                            ->label('Supplier')
                            ->options(fn () => Customer::query()
                                ->whereHas('debts', fn ($q) => $q->unpaid())
                                ->orderBy('name')
                                ->pluck('name', 'id'))
                            ->required()
                            ->searchable()
                            ->preload()
                            ->reactive()
                            ->afterStateUpdated(fn ($set) => $set('debt_id', null))
                            ->helperText('Pilih supplier terlebih dahulu')
                            ->columnSpanFull(),
                        Forms\Components\Select::make('debt_id')
                            ->label('Nota')
                            ->options(function ($get) {
                                $customerId = $get('customer_id');
                                if (! $customerId) {
                                    return [];
                                }

                                return Debt::query()
                                    ->unpaid()
                                    ->where('customer_id', $customerId)
                                    ->get()
                                    ->mapWithKeys(fn (Debt $debt) => [
                                        $debt->id => "{$debt->invoice_number} (Sisa: ".rupiah($debt->remaining_amount).')',
                                    ]);
                            })
                            ->required()
                            ->searchable()
                            ->preload()
                            ->reactive()
                            ->disabled(fn ($get) => ! $get('customer_id'))
                            ->afterStateUpdated(fn ($set, ?string $state) => $set('remaining_hint', Debt::find($state)?->remaining_amount))
                            ->helperText(fn ($get) => ! $get('customer_id')
                                ? 'Pilih supplier terlebih dahulu'
                                : (($remaining = $get('remaining_hint'))
                                    ? 'Sisa hutang nota ini: '.rupiah($remaining)
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
                                        $debtId = $get('debt_id');
                                        if ($debtId && ($debt = Debt::find($debtId))) {
                                            $set('amount', $debt->remaining_amount);
                                        }
                                    })
                                    ->visible(fn ($get) => (bool) $get('debt_id'))
                            )
                            ->helperText(fn ($get) => $get('debt_id') ? 'Klik tombol ikon di kanan kolom untuk mengisi sisa hutang nota secara otomatis' : null)
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
                Tables\Columns\TextColumn::make('debt.invoice_number')
                    ->label('No. Nota')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->url(fn (Installment $record) => $record->debt ? DebtResource::getUrl('view', ['record' => $record->debt]) : null),
                Tables\Columns\TextColumn::make('debt.customer.name')
                    ->label('Supplier')
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
                Tables\Filters\SelectFilter::make('debt.customer_id')
                    ->label('Supplier')
                    ->relationship('debt.customer', 'name')
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
                    ->visible(fn (Installment $record) => auth()->user()?->can('delete', $record)),
            ])
            ->defaultSort('installment_date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInstallments::route('/'),
            'create' => Pages\CreateInstallment::route('/create'),
            'view' => Pages\ViewInstallment::route('/{record}'),
        ];
    }
}


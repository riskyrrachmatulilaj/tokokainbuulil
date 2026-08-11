<?php

namespace App\Filament\Pages;

use App\Models\Customer;
use App\Models\Debt;
use App\Services\CollectivePaymentService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Validation\ValidationException;

class CollectivePaymentPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-arrow-right-circle';

    protected static string | \UnitEnum | null $navigationGroup = 'Transaksi Hutang';

    protected static ?string $navigationLabel = 'Pembayaran Kolektif Hutang';

    protected static ?string $title = 'Pembayaran Kolektif Hutang';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.pages.collective-payment-page';

    public ?array $data = [];

    public ?array $result = null;

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Select::make('customer_id')
                    ->label('Supplier')
                    ->options(fn () => Customer::query()
                        ->withCount('debts')
                        ->orderBy('name')
                        ->get()
                        ->mapWithKeys(fn (Customer $customer) => [$customer->id => $customer->name])
                        ->toArray())
                    ->searchable()
                    ->preload()
                    ->required()
                    ->reactive()
                    ->helperText(fn ($state) => $state
                        ? 'Total sisa hutang: '.rupiah(Customer::find($state)?->debts()->sum('remaining_amount') ?? 0)
                        : 'Pilih supplier terlebih dahulu')
                    ->columnSpanFull(),
                DatePicker::make('payment_date')
                    ->label('Tanggal Pembayaran')
                    ->default(today())
                    ->required(),
                TextInput::make('amount')
                    ->label('Nominal Pembayaran')
                    ->numeric()
                    ->required()
                    ->minValue(1)
                    ->prefix('Rp')
                    ->helperText('Pembayaran akan dibagi ke nota tertua (FIFO) hingga lunas.'),
                Textarea::make('description')
                    ->label('Keterangan')
                    ->rows(3),
            ])
            ->statePath('data')
            ->columns(2);
    }

    public function process(): void
    {
        $data = $this->form->getState();

        try {
            $result = app(CollectivePaymentService::class)->process($data, auth()->user());

            $this->result = $result;
            $this->form->fill();

            Notification::make()
                ->success()
                ->title('Pembayaran kolektif berhasil')
                ->body('Transaksi '.$result['collectivePayment']['transaction_number'].' â€” '.count($result['allocations']).' nota menerima pembayaran.')
                ->send();
        } catch (ValidationException $e) {
            Notification::make()
                ->danger()
                ->title('Pembayaran gagal')
                ->body(collect($e->errors())->flatten()->first())
                ->send();
        }
    }
}


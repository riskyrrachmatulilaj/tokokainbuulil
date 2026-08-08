<?php

namespace App\Filament\Pages;

use App\Models\ReceivableParty;
use App\Services\ReceivableCollectivePaymentService;
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

class ReceivableCollectivePaymentPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-arrow-right-circle';

    protected static string | \UnitEnum | null $navigationGroup = 'Transaksi Piutang';

    protected static ?string $navigationLabel = 'Pembayaran Kolektif Piutang';

    protected static ?string $title = 'Pembayaran Kolektif Piutang';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.pages.receivable-collective-payment-page';

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
                Select::make('receivable_party_id')
                    ->label('Debitur')
                    ->options(fn () => ReceivableParty::query()
                        ->withCount('receivables')
                        ->orderBy('name')
                        ->get()
                        ->mapWithKeys(fn (ReceivableParty $party) => [$party->id => $party->name])
                        ->toArray())
                    ->searchable()
                    ->preload()
                    ->required()
                    ->reactive()
                    ->helperText(fn ($state) => $state
                        ? 'Total sisa piutang: '.rupiah(ReceivableParty::find($state)?->receivables()->sum('remaining_amount') ?? 0)
                        : 'Pilih debitur terlebih dahulu')
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
            $result = app(ReceivableCollectivePaymentService::class)->process($data, auth()->user());

            $collectivePayment = $result['collectivePayment'];

            $this->result = [
                'collectivePayment' => [
                    'transaction_number' => $collectivePayment->transaction_number,
                    'party' => ['name' => $collectivePayment->party?->name ?? '-'],
                    'amount' => (float) $collectivePayment->amount,
                    'payment_date' => $collectivePayment->payment_date?->format('d-m-Y'),
                ],
                'allocations' => $result['allocations'],
            ];
            $this->form->fill();

            Notification::make()
                ->success()
                ->title('Pembayaran kolektif berhasil')
                ->body('Transaksi '.$this->result['collectivePayment']['transaction_number'].' - '.count($this->result['allocations']).' nota menerima pembayaran.')
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

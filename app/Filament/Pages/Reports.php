<?php

namespace App\Filament\Pages;

use App\Exports\ReportExport;
use App\Services\ReportPdfService;
use App\Services\ReportService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class Reports extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static string | \UnitEnum | null $navigationGroup = 'Laporan';

    protected static ?string $navigationLabel = 'Laporan';

    protected static ?string $title = 'Laporan';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.reports';

    public ?array $data = [];

    public ?Collection $results = null;

    public function mount(): void
    {
        $this->form->fill([
            'type' => ReportService::TYPE_DEBT_LIST,
        ]);
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Select::make('type')
                    ->label('Jenis Laporan')
                    ->options(ReportService::TYPES)
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(fn ($state) => $this->results = null),
                DatePicker::make('from')
                    ->label('Dari Tanggal'),
                DatePicker::make('until')
                    ->label('Sampai Tanggal')
                    ->afterOrEqual('from'),
                Select::make('customer_id')
                    ->label('Pelanggan')
                    ->options(fn () => \App\Models\Customer::query()->orderBy('name')->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->nullable(),
                Select::make('status')
                    ->label('Status')
                    ->options([
                        \App\Models\Debt::STATUS_UNPAID => 'Belum Lunas',
                        \App\Models\Debt::STATUS_PAID => 'Lunas',
                    ])
                    ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('type') === ReportService::TYPE_DEBT_LIST)
                    ->nullable(),
                Select::make('payment_type')
                    ->label('Jenis Pembayaran')
                    ->options([
                        \App\Models\PaymentHistory::TYPE_INSTALLMENT => 'Cicilan Nota',
                        \App\Models\PaymentHistory::TYPE_COLLECTIVE => 'Pembayaran Kolektif',
                    ])
                    ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('type') === ReportService::TYPE_PAYMENT_HISTORY)
                    ->nullable(),
            ])
            ->statePath('data')
            ->columns(3);
    }

    public function show(): void
    {
        $filters = $this->form->getState();
        $this->results = app(ReportService::class)->data($filters);
    }

    public function getResultTitle(): string
    {
        return app(ReportService::class)->title($this->data['type'] ?? ReportService::TYPE_DEBT_LIST);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export_pdf')
                ->label('Export PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('danger')
                ->action(fn () => ReportPdfService::generate($this->data['type'], $this->data)),
            Action::make('export_excel')
                ->label('Export Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(fn () => ReportExport::xlsx($this->data['type'], $this->data)),
        ];
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('show')
                ->label('Tampilkan Laporan')
                ->icon('heroicon-o-magnifying-glass')
                ->color('primary')
                ->submit('show'),
        ];
    }
}


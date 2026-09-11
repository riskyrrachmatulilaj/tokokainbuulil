<?php

namespace App\Filament\Pages;

use App\Exports\TopCustomerReportExport;
use App\Services\TopCustomerReportPdfService;
use App\Services\TopCustomerReportService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Filament\Pages\Page;

class TopCustomerReport extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-trophy';

    protected static string | \UnitEnum | null $navigationGroup = 'Laporan';

    protected static ?string $navigationLabel = 'Pelanggan Terbanyak';

    protected static ?string $title = 'Laporan Pelanggan dengan Transaksi Terbanyak';

    protected static ?int $navigationSort = 4;

    protected string $view = 'filament.pages.top-customer-report';

    public ?array $data = [];

    public ?array $report = null;

    public function mount(): void
    {
        $this->form->fill([
            'preset' => TopCustomerReportService::PRESET_THIS_MONTH,
            'from' => null,
            'until' => null,
            'sort_by' => TopCustomerReportService::SORT_TRANSACTIONS,
            'limit' => 25,
            'search' => '',
        ]);

        $this->show();
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Select::make('preset')
                    ->label('Periode Waktu')
                    ->options(TopCustomerReportService::PRESETS)
                    ->default(TopCustomerReportService::PRESET_THIS_MONTH)
                    ->required()
                    ->live(),
                DatePicker::make('from')
                    ->label('Dari Tanggal')
                    ->visible(fn ($get) => $get('preset') === TopCustomerReportService::PRESET_CUSTOM),
                DatePicker::make('until')
                    ->label('Sampai Tanggal')
                    ->visible(fn ($get) => $get('preset') === TopCustomerReportService::PRESET_CUSTOM)
                    ->afterOrEqual('from'),
                Select::make('sort_by')
                    ->label('Urutkan Berdasarkan')
                    ->options(TopCustomerReportService::SORTS)
                    ->default(TopCustomerReportService::SORT_TRANSACTIONS)
                    ->required(),
                Select::make('limit')
                    ->label('Tampilkan')
                    ->options([
                        10 => 'Top 10 Pelanggan',
                        25 => 'Top 25 Pelanggan',
                        50 => 'Top 50 Pelanggan',
                        'all' => 'Semua Pelanggan',
                    ])
                    ->default(25)
                    ->required(),
                TextInput::make('search')
                    ->label('Cari Nama / No. HP')
                    ->placeholder('Ketik nama atau no. telepon...')
                    ->maxLength(100),
            ])
            ->statePath('data')
            ->columns([
                'default' => 1,
                'sm' => 2,
                'md' => 3,
                'lg' => 4,
            ]);
    }

    public function show(): void
    {
        $filters = $this->form->getState();
        $this->report = app(TopCustomerReportService::class)->data($filters);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export_pdf')
                ->label('Export PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('danger')
                ->action(fn () => TopCustomerReportPdfService::generate($this->data ?: [])),
            Action::make('export_excel')
                ->label('Export Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(fn () => TopCustomerReportExport::xlsx($this->data ?: [])),
        ];
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('show')
                ->label('Tampilkan Peringkat')
                ->icon('heroicon-o-magnifying-glass')
                ->color('primary')
                ->submit('show'),
        ];
    }
}

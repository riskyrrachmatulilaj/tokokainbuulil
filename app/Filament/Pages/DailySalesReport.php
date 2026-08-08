<?php

namespace App\Filament\Pages;

use App\Exports\SaleReportExport;
use App\Services\SaleReportPdfService;
use App\Services\SaleReportService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Filament\Pages\Page;

class DailySalesReport extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-chart-bar';

    protected static string | \UnitEnum | null $navigationGroup = 'Laporan';

    protected static ?string $navigationLabel = 'Penjualan Harian';

    protected static ?string $title = 'Laporan Penjualan Harian';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.pages.daily-sales-report';

    public ?array $data = [];

    public ?array $report = null;

    public function mount(): void
    {
        $this->form->fill([
            'date' => today()->format('Y-m-d'),
        ]);
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                DatePicker::make('date')
                    ->label('Tanggal Penjualan')
                    ->default(today())
                    ->required(),
            ])
            ->statePath('data')
            ->columns(2);
    }

    public function show(): void
    {
        $this->report = app(SaleReportService::class)->data($this->data['date']);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export_pdf')
                ->label('Export PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('danger')
                ->action(fn () => SaleReportPdfService::generate($this->data['date'])),
            Action::make('export_excel')
                ->label('Export Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(fn () => SaleReportExport::xlsx($this->data['date'])),
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

<?php

namespace App\Filament\Pages;

use App\Exports\ReceivableReportExport;
use App\Models\Receivable;
use App\Models\ReceivableParty;
use App\Models\ReceivablePaymentHistory;
use App\Services\ReceivableReportPdfService;
use App\Services\ReceivableReportService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class ReceivableReports extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static string | \UnitEnum | null $navigationGroup = 'Laporan';

    protected static ?string $navigationLabel = 'Laporan Piutang';

    protected static ?string $title = 'Laporan Piutang';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.receivable-reports';

    public ?array $data = [];

    public ?Collection $results = null;

    public function mount(): void
    {
        $this->form->fill([
            'type' => ReceivableReportService::TYPE_RECEIVABLE_LIST,
        ]);
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Select::make('type')
                    ->label('Jenis Laporan')
                    ->options(ReceivableReportService::TYPES)
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(fn ($state) => $this->results = null),
                DatePicker::make('from')
                    ->label('Dari Tanggal'),
                DatePicker::make('until')
                    ->label('Sampai Tanggal')
                    ->afterOrEqual('from'),
                Select::make('party_id')
                    ->label('Debitur')
                    ->options(fn () => ReceivableParty::query()->orderBy('name')->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->nullable(),
                Select::make('status')
                    ->label('Status')
                    ->options([
                        Receivable::STATUS_UNPAID => 'Belum Lunas',
                        Receivable::STATUS_PAID => 'Lunas',
                    ])
                    ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('type') === ReceivableReportService::TYPE_RECEIVABLE_LIST)
                    ->nullable(),
                Select::make('payment_type')
                    ->label('Jenis Pembayaran')
                    ->options([
                        ReceivablePaymentHistory::TYPE_INSTALLMENT => 'Cicilan Nota',
                        ReceivablePaymentHistory::TYPE_COLLECTIVE => 'Pembayaran Kolektif',
                    ])
                    ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('type') === ReceivableReportService::TYPE_PAYMENT_HISTORY)
                    ->nullable(),
            ])
            ->statePath('data')
            ->columns(3);
    }

    public function show(): void
    {
        $filters = $this->form->getState();
        $this->results = app(ReceivableReportService::class)->data($filters);
    }

    public function getResultTitle(): string
    {
        return app(ReceivableReportService::class)->title($this->data['type'] ?? ReceivableReportService::TYPE_RECEIVABLE_LIST);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export_pdf')
                ->label('Export PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('danger')
                ->action(fn () => ReceivableReportPdfService::generate($this->data['type'], $this->data)),
            Action::make('export_excel')
                ->label('Export Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(fn () => ReceivableReportExport::xlsx($this->data['type'], $this->data)),
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

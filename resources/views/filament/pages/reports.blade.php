<x-filament-panels::page>
    <link rel="stylesheet" href="{{ asset('css/reports.css') }}?v=3" data-navigate-track />

    <style>
        /* Fallback Critical Styles for Immediate Render */
        .report-summary-grid { display: grid; grid-template-columns: repeat(1, minmax(0, 1fr)); gap: 1rem; margin-bottom: 1rem; width: 100%; }
        @media (min-width: 640px) { .report-summary-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        @media (min-width: 1024px) { .report-summary-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); } }
        .report-metric-card { display: flex; align-items: center; gap: 1rem; padding: 1.15rem 1.25rem; border-radius: 1rem; background: #fff; border: 1px solid #e2e8f0; }
        .dark .report-metric-card, html.dark .report-metric-card { background: #1e293b; border-color: #334155; }
        .report-metric-icon { display: flex; align-items: center; justify-content: center; width: 3rem; height: 3rem; border-radius: 0.75rem; flex-shrink: 0; }
        .report-table-wrapper { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; border-radius: 0.875rem; border: 1px solid #e2e8f0; background: #fff; }
        .dark .report-table-wrapper, html.dark .report-table-wrapper { border-color: #334155; background: #0f172a; }
        .report-table { width: 100%; border-collapse: separate; border-spacing: 0; text-align: left; font-size: 0.875rem; line-height: 1.5; }
        .report-table th { padding: 0.875rem 1.25rem; font-size: 0.725rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; background: #f8fafc; color: #475569; border-bottom: 2px solid #e2e8f0; white-space: nowrap; }
        .dark .report-table th, html.dark .report-table th { background: #1e293b; color: #cbd5e1; border-bottom: 2px solid #334155; }
        .report-table td { padding: 0.875rem 1.25rem; white-space: nowrap; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        .dark .report-table td, html.dark .report-table td { border-bottom: 1px solid #1e293b; color: #f8fafc; }
        .report-table tbody tr:nth-child(even) td { background-color: #fcfdfe; }
        .dark .report-table tbody tr:nth-child(even) td, html.dark .report-table tbody tr:nth-child(even) td { background-color: rgba(30, 41, 59, 0.4); }
        .report-table tbody tr:hover td { background-color: rgba(99, 102, 241, 0.05); }
        .dark .report-table tbody tr:hover td, html.dark .report-table tbody tr:hover td { background-color: rgba(99, 102, 241, 0.15); }
        .report-table tfoot td { padding: 1rem 1.25rem; font-weight: 700; background: #f1f5f9; border-top: 2px solid #cbd5e1; color: #0f172a; }
        .dark .report-table tfoot td, html.dark .report-table tfoot td { background: #1e293b; border-top: 2px solid #475569; color: #f8fafc; }
    </style>

    <x-filament::section icon="heroicon-o-funnel">
        <x-slot name="heading">
            Filter Laporan Hutang Toko
        </x-slot>
        <x-slot name="description">
            Pilih jenis laporan, rentang tanggal, dan supplier untuk menyaring data.
        </x-slot>

        <form wire:submit="show">
            {{ $this->form }}

            <div class="mt-4 flex items-center gap-3">
                <x-filament::button type="submit" icon="heroicon-o-magnifying-glass">
                    Tampilkan Laporan
                </x-filament::button>
            </div>
        </form>
    </x-filament::section>

    @if ($this->results)
        @php
            $type = $this->data['type'] ?? 'debt_list';
            $columns = \App\Exports\ReportExport::columnsFor($type);
            $results = $this->results;

            $totalNominal = $results->sum(fn($r) => (float)($r['amount'] ?? $r['total'] ?? 0));
            $totalPaid = $results->sum(fn($r) => (float)($r['paid_amount'] ?? 0));
            $totalRemaining = $results->sum(fn($r) => (float)($r['remaining_amount'] ?? 0));
        @endphp

        <div class="report-container">
            {{-- Metric Summary Cards --}}
            <div class="report-summary-grid">
                <div class="report-metric-card">
                    <div class="report-metric-icon is-primary">
                        <x-filament::icon icon="heroicon-o-document-text" style="width: 24px; height: 24px;" />
                    </div>
                    <div>
                        <div class="report-metric-label">Total Baris Data</div>
                        <div class="report-metric-value">{{ number_format($results->count()) }} Baris</div>
                    </div>
                </div>

                @if ($totalNominal > 0)
                    <div class="report-metric-card">
                        <div class="report-metric-icon is-blue">
                            <x-filament::icon icon="heroicon-o-banknotes" style="width: 24px; height: 24px;" />
                        </div>
                        <div>
                            <div class="report-metric-label">Total Nominal Hutang</div>
                            <div class="report-metric-value text-blue-600 dark:text-blue-400">{{ rupiah($totalNominal) }}</div>
                        </div>
                    </div>
                @endif

                @if ($totalPaid > 0)
                    <div class="report-metric-card">
                        <div class="report-metric-icon is-success">
                            <x-filament::icon icon="heroicon-o-check-circle" style="width: 24px; height: 24px;" />
                        </div>
                        <div>
                            <div class="report-metric-label">Sudah Dibayar</div>
                            <div class="report-metric-value text-emerald-600 dark:text-emerald-400">{{ rupiah($totalPaid) }}</div>
                        </div>
                    </div>
                @endif

                @if ($totalRemaining > 0)
                    <div class="report-metric-card">
                        <div class="report-metric-icon is-warning">
                            <x-filament::icon icon="heroicon-o-clock" style="width: 24px; height: 24px;" />
                        </div>
                        <div>
                            <div class="report-metric-label">Sisa Hutang Toko</div>
                            <div class="report-metric-value text-amber-600 dark:text-amber-400">{{ rupiah($totalRemaining) }}</div>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Main Table Card --}}
            <x-filament::section icon="heroicon-o-table-cells">
                <x-slot name="heading">
                    {{ $this->getResultTitle() }}
                </x-slot>
                <x-slot name="description">
                    Menampilkan {{ number_format($results->count()) }} baris data laporan.
                </x-slot>

                <div class="report-table-wrapper">
                    <table class="report-table">
                        <thead>
                            <tr>
                                @foreach ($columns as $index => $column)
                                    @php
                                        $isMoney = in_array($column, ['Nominal', 'Sudah Dibayar', 'Sudah Diterima', 'Sisa Piutang', 'Sisa Hutang', 'Total', 'Total Sisa Piutang', 'Total Sisa Hutang'], true);
                                        $isCenter = str_contains($column, 'Status') || in_array($column, ['Jumlah Transaksi', 'Jumlah Nota Belum Lunas', 'Jumlah Jatuh Tempo', 'Jenis Pembayaran'], true);
                                    @endphp
                                    <th class="{{ $isMoney ? 'text-right' : ($isCenter ? 'text-center' : 'text-left') }}">
                                        {{ $column }}
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($results as $row)
                                <tr>
                                    @foreach (\App\Exports\ReportExport::valuesFor($type, $row) as $index => $value)
                                        @php
                                            $colName = $columns[$index] ?? null;
                                            $isMoney = in_array($colName, ['Nominal', 'Sudah Dibayar', 'Sudah Diterima', 'Sisa Piutang', 'Sisa Hutang', 'Total', 'Total Sisa Piutang', 'Total Sisa Hutang'], true);
                                            $isCenter = str_contains($colName ?? '', 'Status') || in_array($colName, ['Jumlah Transaksi', 'Jumlah Nota Belum Lunas', 'Jumlah Jatuh Tempo', 'Jenis Pembayaran'], true);
                                        @endphp
                                        <td class="{{ $isMoney ? 'text-right font-mono' : ($isCenter ? 'text-center' : 'text-left') }}">
                                            @if ($isMoney)
                                                @php
                                                    $numVal = (float)$value;
                                                    $isPaidCol = in_array($colName, ['Sudah Dibayar', 'Sudah Diterima'], true);
                                                    $isRemCol = in_array($colName, ['Sisa Hutang', 'Sisa Piutang', 'Total Sisa Hutang', 'Total Sisa Piutang'], true);
                                                @endphp
                                                @if ($numVal <= 0)
                                                    <span class="text-slate-400 dark:text-slate-500 font-medium">Rp 0</span>
                                                @elseif ($isPaidCol)
                                                    <span class="font-bold text-emerald-600 dark:text-emerald-400">{{ rupiah($value) }}</span>
                                                @elseif ($isRemCol)
                                                    <span class="font-bold text-amber-600 dark:text-amber-400">{{ rupiah($value) }}</span>
                                                @else
                                                    <span class="font-bold text-slate-900 dark:text-slate-100">{{ rupiah($value) }}</span>
                                                @endif
                                            @elseif ($colName === 'No. Nota' || $colName === 'No. Transaksi')
                                                @if ($value && $value !== '-')
                                                    <span class="report-badge-code">{{ $value }}</span>
                                                @else
                                                    <span class="text-slate-400">-</span>
                                                @endif
                                            @elseif ($colName === 'Supplier' || $colName === 'Debitur')
                                                <span class="report-party-name">{{ $value ?: '-' }}</span>
                                            @elseif ($colName === 'Jenis Pembayaran')
                                                @if (str_contains($value, 'Kolektif'))
                                                    <span class="report-badge-payment is-kolektif">{{ $value }}</span>
                                                @else
                                                    <span class="report-badge-payment is-cicilan">{{ $value }}</span>
                                                @endif
                                            @elseif (str_contains($colName ?? '', 'Status'))
                                                @if (str_contains($value, 'Lunas') && !str_contains($value, 'Belum'))
                                                    <span class="report-badge-status is-lunas">
                                                        <span class="report-status-dot"></span>
                                                        {{ $value }}
                                                    </span>
                                                @else
                                                    <span class="report-badge-status is-belum">
                                                        <span class="report-status-dot"></span>
                                                        {{ $value }}
                                                    </span>
                                                @endif
                                            @elseif (in_array($colName, ['Jumlah Transaksi', 'Jumlah Nota Belum Lunas', 'Jumlah Jatuh Tempo'], true))
                                                <span class="report-badge-count">{{ $value }}</span>
                                            @elseif (in_array($colName, ['Tanggal Hutang', 'Tanggal Piutang', 'Tanggal', 'Jatuh Tempo', 'Hutang Tertua', 'Piutang Tertua', 'Periode'], true))
                                                <span class="report-date-text">{{ $value ?: '-' }}</span>
                                            @else
                                                <span class="font-medium text-slate-800 dark:text-slate-200">{{ $value ?: '-' }}</span>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ count($columns) }}" class="report-empty-state">
                                        <div class="flex flex-col items-center justify-center gap-2">
                                            <x-filament::icon icon="heroicon-o-inbox" style="width: 32px; height: 32px; color: #94a3b8;" />
                                            <span>Tidak ada data untuk filter yang dipilih.</span>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if ($results->isNotEmpty() && ($totalNominal > 0 || $totalPaid > 0 || $totalRemaining > 0))
                            <tfoot>
                                <tr>
                                    @foreach ($columns as $index => $column)
                                        @php
                                            $isMoney = in_array($column, ['Nominal', 'Sudah Dibayar', 'Sudah Diterima', 'Sisa Piutang', 'Sisa Hutang', 'Total', 'Total Sisa Piutang', 'Total Sisa Hutang'], true);
                                        @endphp
                                        <td class="{{ $isMoney ? 'text-right font-mono' : 'text-left' }}">
                                            @if ($index === 0)
                                                <span class="report-total-summary-label">
                                                    <x-filament::icon icon="heroicon-m-calculator" style="width: 16px; height: 16px;" />
                                                    TOTAL
                                                </span>
                                            @elseif ($column === 'Nominal' || $column === 'Total')
                                                <span class="text-blue-600 dark:text-blue-400 font-bold">{{ rupiah($totalNominal) }}</span>
                                            @elseif ($column === 'Sudah Dibayar' || $column === 'Sudah Diterima')
                                                <span class="text-emerald-600 dark:text-emerald-400 font-bold">{{ rupiah($totalPaid) }}</span>
                                            @elseif ($column === 'Sisa Hutang' || $column === 'Sisa Piutang' || $column === 'Total Sisa Hutang' || $column === 'Total Sisa Piutang')
                                                <span class="text-amber-600 dark:text-amber-400 font-bold">{{ rupiah($totalRemaining) }}</span>
                                            @else
                                                <span class="text-slate-400 font-normal">-</span>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </x-filament::section>
        </div>
    @endif
</x-filament-panels::page>

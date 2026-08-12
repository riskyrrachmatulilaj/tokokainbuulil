<x-filament-panels::page>
    <link rel="stylesheet" href="{{ asset('css/reports.css') }}?v=1" data-navigate-track />

    <x-filament::section icon="heroicon-o-funnel">
        <x-slot name="heading">
            Filter Laporan Hutang Toko
        </x-slot>

        <form wire:submit="show">
            {{ $this->form }}

            <div class="mt-4">
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

        <div class="mt-6 space-y-6">
            {{-- Metric Summary Cards --}}
            <div class="report-summary-grid">
                <div class="report-metric-card">
                    <div class="report-metric-icon is-primary">
                        <x-filament::icon icon="heroicon-o-document-text" style="width: 24px; height: 24px;" />
                    </div>
                    <div>
                        <div class="report-metric-label">Total Baris Data</div>
                        <div class="report-metric-value">{{ number_format($results->count()) }} Data</div>
                    </div>
                </div>

                @if ($totalNominal > 0)
                    <div class="report-metric-card">
                        <div class="report-metric-icon is-blue">
                            <x-filament::icon icon="heroicon-o-banknotes" style="width: 24px; height: 24px;" />
                        </div>
                        <div>
                            <div class="report-metric-label">Total Nominal Hutang</div>
                            <div class="report-metric-value">{{ rupiah($totalNominal) }}</div>
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

                <div class="report-table-wrapper">
                    <table class="report-table">
                        <thead>
                            <tr>
                                @foreach ($columns as $index => $column)
                                    @php
                                        $isMoney = in_array($column, ['Nominal', 'Sudah Dibayar', 'Sudah Diterima', 'Sisa Piutang', 'Sisa Hutang', 'Total', 'Total Sisa Piutang', 'Total Sisa Hutang'], true);
                                        $isCenter = str_contains($column, 'Status') || in_array($column, ['Jumlah Transaksi', 'Jumlah Nota Belum Lunas', 'Jumlah Jatuh Tempo'], true);
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
                                            $isCenter = str_contains($colName ?? '', 'Status') || in_array($colName, ['Jumlah Transaksi', 'Jumlah Nota Belum Lunas', 'Jumlah Jatuh Tempo'], true);
                                        @endphp
                                        <td class="{{ $isMoney ? 'text-right font-mono' : ($isCenter ? 'text-center' : 'text-left') }}">
                                            @if ($isMoney)
                                                <span class="font-semibold text-gray-900 dark:text-white">{{ rupiah($value) }}</span>
                                            @elseif ($colName === 'No. Nota' || $colName === 'No. Transaksi')
                                                <span class="report-badge-code">
                                                    {{ $value }}
                                                </span>
                                            @elseif (str_contains($colName ?? '', 'Status'))
                                                @if (str_contains($value, 'Lunas'))
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
                                                <span class="report-badge-code">{{ $value }}</span>
                                            @else
                                                <span class="font-medium text-gray-900 dark:text-gray-100">{{ $value }}</span>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ count($columns) }}" class="text-center py-12">
                                        Tidak ada data untuk filter yang dipilih.
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
                                                <span>TOTAL SUMMARY</span>
                                            @elseif ($column === 'Nominal' || $column === 'Total')
                                                <span class="text-blue-600 dark:text-blue-400">{{ rupiah($totalNominal) }}</span>
                                            @elseif ($column === 'Sudah Dibayar' || $column === 'Sudah Diterima')
                                                <span class="text-emerald-600 dark:text-emerald-400">{{ rupiah($totalPaid) }}</span>
                                            @elseif ($column === 'Sisa Hutang' || $column === 'Sisa Piutang' || $column === 'Total Sisa Hutang' || $column === 'Total Sisa Piutang')
                                                <span class="text-amber-600 dark:text-amber-400">{{ rupiah($totalRemaining) }}</span>
                                            @else
                                                -
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

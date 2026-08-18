<x-filament-panels::page>
    <style>
        /* Fallback Critical Styles for Immediate Render */
        .dsr-report { display: flex; flex-direction: column; gap: 1.5rem; margin-top: 1.5rem; width: 100%; }
        .dsr-metrics { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1rem; width: 100%; }
        @media (min-width: 640px) { .dsr-metrics { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
        @media (min-width: 1024px) { .dsr-metrics { grid-template-columns: repeat(6, minmax(0, 1fr)); } }
        .dsr-metric { display: flex; flex-direction: column; gap: 0.35rem; padding: 1.15rem 1.25rem; border: 1px solid #e2e8f0; border-radius: 1rem; background-color: #ffffff; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05); }
        .dark .dsr-metric, html.dark .dsr-metric { background-color: #1e293b; border-color: #334155; }
        .dsr-columns { display: grid; grid-template-columns: 1fr; gap: 1.5rem; width: 100%; }
        @media (min-width: 1024px) { .dsr-columns { grid-template-columns: 1.5fr 1fr; align-items: start; } }
        .dsr-table-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; border-radius: 0.875rem; border: 1px solid #e2e8f0; background-color: #ffffff; }
        .dark .dsr-table-wrap, html.dark .dsr-table-wrap { border-color: #334155; background-color: #0f172a; }
        .dsr-table { width: 100%; border-collapse: separate; border-spacing: 0; text-align: left; font-size: 0.875rem; line-height: 1.5; }
        .dsr-table th { padding: 0.875rem 1.15rem; font-size: 0.725rem; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase; background-color: #f8fafc; color: #475569; border-bottom: 2px solid #e2e8f0; white-space: nowrap; }
        .dark .dsr-table th, html.dark .dsr-table th { background-color: #1e293b; color: #cbd5e1; border-bottom: 2px solid #334155; }
        .dsr-table td { padding: 0.875rem 1.15rem; vertical-align: middle; border-bottom: 1px solid #f1f5f9; color: #1e293b; white-space: nowrap; }
        .dark .dsr-table td, html.dark .dsr-table td { border-bottom: 1px solid #1e293b; color: #f8fafc; }
        .dsr-table tbody tr:nth-child(even) td { background-color: #fcfdfe; }
        .dark .dsr-table tbody tr:nth-child(even) td, html.dark .dsr-table tbody tr:nth-child(even) td { background-color: rgba(30, 41, 59, 0.4); }
        .dsr-table tbody tr:hover td { background-color: rgba(99, 102, 241, 0.05); }
        .dark .dsr-table tbody tr:hover td, html.dark .dsr-table tbody tr:hover td { background-color: rgba(99, 102, 241, 0.15); }
    </style>

    <x-filament::section icon="heroicon-o-calendar-days">
        <x-slot name="heading">
            Pilih Tanggal Penjualan
        </x-slot>
        <x-slot name="description">
            Lihat rekap omset dan rincian transaksi harian secara lengkap.
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

    @if ($this->report)
        @php
            $summary = $this->report['summary'];
        @endphp

        <div class="dsr-report">
            {{-- Metric Summary --}}
            <x-filament::section icon="heroicon-o-chart-bar">
                <x-slot name="heading">
                    Rekap Penjualan: {{ $this->report['date'] }}
                </x-slot>
                <x-slot name="description">
                    Total {{ $summary['transactions'] }} transaksi tercatat &middot; {{ number_format($summary['items_count']) }} item produk terjual
                </x-slot>

                <div class="dsr-metrics">
                    <div class="dsr-metric">
                        <div class="dsr-metric-label">Jumlah Transaksi</div>
                        <div class="dsr-metric-value">{{ $summary['transactions'] }}</div>
                        <div class="dsr-metric-hint">{{ number_format($summary['items_count']) }} total item</div>
                    </div>

                    <div class="dsr-metric">
                        <div class="dsr-metric-label">Total Omset</div>
                        <div class="dsr-metric-value text-blue-600 dark:text-blue-400">{{ rupiah($summary['total_revenue']) }}</div>
                        <div class="dsr-metric-hint">Semua metode bayar</div>
                    </div>

                    <div class="dsr-metric is-success">
                        <div class="dsr-metric-label">Tunai (Cash)</div>
                        <div class="dsr-metric-value">{{ rupiah($summary['cash_revenue']) }}</div>
                        <div class="dsr-metric-hint">{{ $summary['cash_count'] }} transaksi</div>
                    </div>

                    <div class="dsr-metric is-info">
                        <div class="dsr-metric-label">Transfer Bank</div>
                        <div class="dsr-metric-value">{{ rupiah($summary['transfer_revenue']) }}</div>
                        <div class="dsr-metric-hint">{{ $summary['transfer_count'] }} transaksi</div>
                    </div>

                    @if (isset($summary['split_count']) && $summary['split_count'] > 0)
                        <div class="dsr-metric is-purple">
                            <div class="dsr-metric-label">Tunai + Transfer</div>
                            <div class="dsr-metric-value">{{ rupiah($summary['split_revenue']) }}</div>
                            <div class="dsr-metric-hint">{{ $summary['split_count'] }} transaksi</div>
                        </div>
                    @endif

                    <div class="dsr-metric is-warning">
                        <div class="dsr-metric-label">Kredit (Piutang)</div>
                        <div class="dsr-metric-value">{{ rupiah($summary['receivable_revenue']) }}</div>
                        <div class="dsr-metric-hint">{{ $summary['receivable_count'] }} transaksi</div>
                    </div>
                </div>
            </x-filament::section>

            {{-- 2 Tables Side-by-Side on Desktop --}}
            <div class="dsr-columns">
                {{-- Table 1: Transaction List --}}
                <x-filament::section icon="heroicon-o-document-text">
                    <x-slot name="heading">
                        Daftar Transaksi
                    </x-slot>
                    <x-slot name="description">
                        {{ count($this->report['sales']) }} transaksi pada {{ $this->report['date'] }}
                    </x-slot>

                    <div class="dsr-table-wrap">
                        <table class="dsr-table">
                            <thead>
                                <tr>
                                    <th>No. Transaksi</th>
                                    <th class="dsr-center">Jam</th>
                                    <th>Metode</th>
                                    <th>Pelanggan</th>
                                    <th class="dsr-center">Item</th>
                                    <th class="dsr-money">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($this->report['sales'] as $sale)
                                    <tr>
                                        <td>
                                            <span class="dsr-txn">{{ $sale['transaction_number'] }}</span>
                                        </td>
                                        <td class="dsr-center text-slate-500 font-mono text-xs">
                                            {{ $sale['time'] }}
                                        </td>
                                        <td>
                                            @if ($sale['payment_method_label'] === 'Tunai')
                                                <span class="dsr-badge is-cash">Tunai</span>
                                            @elseif ($sale['payment_method_label'] === 'Transfer')
                                                <span class="dsr-badge is-transfer">Transfer</span>
                                            @elseif ($sale['payment_method_label'] === 'Tunai + Transfer')
                                                <span class="dsr-badge is-split">Tunai + Transfer</span>
                                            @else
                                                <span class="dsr-badge is-credit">Kredit</span>
                                            @endif
                                        </td>
                                        <td class="dsr-col-party">{{ $sale['party'] ?: '-' }}</td>
                                        <td class="dsr-center">
                                            <span class="dsr-badge is-qty">{{ $sale['items_count'] }}</span>
                                        </td>
                                        <td class="dsr-money">{{ rupiah($sale['total_amount']) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="dsr-empty">
                                            <div class="flex flex-col items-center justify-center gap-2">
                                                <x-filament::icon icon="heroicon-o-inbox" style="width: 32px; height: 32px; color: #94a3b8;" />
                                                <span>Belum ada transaksi pada tanggal ini.</span>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </x-filament::section>

                {{-- Table 2: Sold Products Summary --}}
                <x-filament::section icon="heroicon-o-archive-box">
                    <x-slot name="heading">
                        Produk Terjual
                    </x-slot>
                    <x-slot name="description">
                        {{ count($this->report['items']) }} jenis produk terjual
                    </x-slot>

                    <div class="dsr-table-wrap">
                        <table class="dsr-table">
                            <thead>
                                <tr>
                                    <th>Produk</th>
                                    <th class="dsr-center">Terjual</th>
                                    <th class="dsr-money">Pendapatan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($this->report['items'] as $idx => $item)
                                    <tr>
                                        <td class="dsr-col-product">
                                            <span class="dsr-rank-badge {{ $idx === 0 ? 'top-1' : ($idx === 1 ? 'top-2' : ($idx === 2 ? 'top-3' : '')) }}">
                                                {{ $idx + 1 }}
                                            </span>
                                            {{ $item['product_name'] }}
                                        </td>
                                        <td class="dsr-center">
                                            <span class="dsr-badge is-qty">{{ formatQuantity($item['quantity']) }}</span>
                                        </td>
                                        <td class="dsr-money">{{ rupiah($item['revenue']) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="dsr-empty">
                                            <div class="flex flex-col items-center justify-center gap-2">
                                                <x-filament::icon icon="heroicon-o-inbox" style="width: 32px; height: 32px; color: #94a3b8;" />
                                                <span>Tidak ada produk terjual pada tanggal ini.</span>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </x-filament::section>
            </div>
        </div>
    @endif
</x-filament-panels::page>

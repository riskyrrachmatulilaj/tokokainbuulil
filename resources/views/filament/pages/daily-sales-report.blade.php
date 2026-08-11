<x-filament-panels::page>
    <link rel="stylesheet" href="{{ asset('css/daily-sales-report.css') }}?v=1" data-navigate-track />

    <x-filament::section icon="heroicon-o-calendar-days">
        <x-slot name="heading">
            Pilih Tanggal
        </x-slot>
        <x-slot name="description">
            Lihat rekap penjualan harian beserta rincian transaksinya.
        </x-slot>

        <form wire:submit="show">
            {{ $this->form }}

            <x-filament::button type="submit" icon="heroicon-o-magnifying-glass">
                Tampilkan Laporan
            </x-filament::button>
        </form>
    </x-filament::section>

    @if ($this->report)
        @php
            $summary = $this->report['summary'];
        @endphp

        <div class="dsr-report">
            <x-filament::section icon="heroicon-o-chart-bar">
                <x-slot name="heading">
                    Rekap Penjualan
                </x-slot>
                <x-slot name="description">
                    {{ $this->report['date'] }}
                    &middot; {{ $summary['transactions'] }} transaksi
                    &middot; {{ $summary['items_count'] }} item terjual
                </x-slot>

                <div class="dsr-metrics">
                    <div class="dsr-metric">
                        <div class="dsr-metric-label">Jumlah Transaksi</div>
                        <div class="dsr-metric-value">{{ $summary['transactions'] }}</div>
                    </div>
                    <div class="dsr-metric">
                        <div class="dsr-metric-label">Total Penjualan</div>
                        <div class="dsr-metric-value">{{ rupiah($summary['total_revenue']) }}</div>
                    </div>
                    <div class="dsr-metric is-success">
                        <div class="dsr-metric-label">Tunai</div>
                        <div class="dsr-metric-value">{{ rupiah($summary['cash_revenue']) }}</div>
                        <div class="dsr-metric-hint">{{ $summary['cash_count'] }} transaksi</div>
                    </div>
                    <div class="dsr-metric is-info">
                        <div class="dsr-metric-label">Transfer</div>
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

            <div class="dsr-columns">
                <x-filament::section icon="heroicon-o-document-text">
                    <x-slot name="heading">
                        Daftar Transaksi
                    </x-slot>

                    <div class="dsr-table-wrap">
                        <table class="dsr-table">
                            <thead>
                                <tr>
                                    <th>No. Transaksi</th>
                                    <th>Jam</th>
                                    <th>Metode</th>
                                    <th>Pelanggan</th>
                                    <th class="dsr-center">Item</th>
                                    <th class="dsr-money">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($this->report['sales'] as $sale)
                                    <tr>
                                        <td class="dsr-txn">{{ $sale['transaction_number'] }}</td>
                                        <td>{{ $sale['time'] }}</td>
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
                                        <td class="dsr-center">{{ $sale['items_count'] }}</td>
                                        <td class="dsr-money">{{ rupiah($sale['total_amount']) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="dsr-empty">
                                            Belum ada transaksi pada tanggal ini.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </x-filament::section>

                <x-filament::section icon="heroicon-o-archive-box">
                    <x-slot name="heading">
                        Produk Terjual
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
                                @forelse ($this->report['items'] as $item)
                                    <tr>
                                        <td class="dsr-col-product">{{ $item['product_name'] }}</td>
                                        <td class="dsr-center">
                                            <span class="dsr-badge is-qty">{{ $item['quantity'] }}</span>
                                        </td>
                                        <td class="dsr-money">{{ rupiah($item['revenue']) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="dsr-empty">
                                            Tidak ada produk terjual pada tanggal ini.
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

<x-filament-panels::page>
    <style>
        .tcr-wrap { display: flex; flex-direction: column; gap: 1.5rem; width: 100%; margin-top: 1rem; }
        .tcr-metrics { display: grid; grid-template-columns: repeat(1, minmax(0, 1fr)); gap: 1rem; width: 100%; }
        @media (min-width: 640px) { .tcr-metrics { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        @media (min-width: 1024px) { .tcr-metrics { grid-template-columns: repeat(4, minmax(0, 1fr)); } }

        .tcr-metric-card {
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
            padding: 1.15rem 1.25rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.875rem;
            background-color: #ffffff;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        }
        .dark .tcr-metric-card, html.dark .tcr-metric-card {
            background-color: #1e293b;
            border-color: #334155;
        }

        .tcr-metric-title { font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em; color: #64748b; }
        .dark .tcr-metric-title, html.dark .tcr-metric-title { color: #94a3b8; }
        .tcr-metric-number { font-size: 1.5rem; font-weight: 700; line-height: 1.2; color: #0f172a; }
        .dark .tcr-metric-number, html.dark .tcr-metric-number { color: #f8fafc; }
        .tcr-metric-subtitle { font-size: 0.8125rem; color: #64748b; }
        .dark .tcr-metric-subtitle, html.dark .tcr-metric-subtitle { color: #94a3b8; }

        .tcr-table-wrap {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            border-radius: 0.875rem;
            border: 1px solid #e2e8f0;
            background-color: #ffffff;
        }
        .dark .tcr-table-wrap, html.dark .tcr-table-wrap {
            border-color: #334155;
            background-color: #0f172a;
        }

        .tcr-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            text-align: left;
            font-size: 0.875rem;
            line-height: 1.5;
        }
        .tcr-table th {
            padding: 0.875rem 1rem;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            background-color: #f8fafc;
            color: #334155;
            border-bottom: 2px solid #e2e8f0;
            white-space: nowrap;
        }
        .dark .tcr-table th, html.dark .tcr-table th {
            background-color: #1e293b;
            color: #cbd5e1;
            border-bottom: 2px solid #334155;
        }
        .tcr-table td {
            padding: 0.875rem 1rem;
            vertical-align: middle;
            border-bottom: 1px solid #f1f5f9;
            color: #1e293b;
            white-space: nowrap;
        }
        .dark .tcr-table td, html.dark .tcr-table td {
            border-bottom: 1px solid #1e293b;
            color: #f8fafc;
        }
        .tcr-table tbody tr:nth-child(even) td { background-color: #fcfdfe; }
        .dark .tcr-table tbody tr:nth-child(even) td, html.dark .tcr-table tbody tr:nth-child(even) td { background-color: rgba(30, 41, 59, 0.4); }
        .tcr-table tbody tr:hover td { background-color: rgba(13, 148, 136, 0.05); }
        .dark .tcr-table tbody tr:hover td, html.dark .tcr-table tbody tr:hover td { background-color: rgba(13, 148, 136, 0.15); }

        .tcr-center { text-align: center; }
        .tcr-right { text-align: right; }
        .tcr-rank {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2rem;
            height: 2rem;
            border-radius: 9999px;
            font-size: 0.8125rem;
            font-weight: 700;
            background-color: #f1f5f9;
            color: #475569;
        }
        .dark .tcr-rank, html.dark .tcr-rank {
            background-color: #334155;
            color: #cbd5e1;
        }
        .tcr-rank.rank-1 { background-color: #fef08a; color: #854d0e; }
        .tcr-rank.rank-2 { background-color: #e2e8f0; color: #334155; }
        .tcr-rank.rank-3 { background-color: #fed7aa; color: #9a3412; }

        .tcr-badge {
            display: inline-block;
            padding: 0.2rem 0.6rem;
            border-radius: 0.375rem;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .tcr-badge-txns { background-color: #e0f2fe; color: #0369a1; }
        .dark .tcr-badge-txns, html.dark .tcr-badge-txns { background-color: #0c4a6e; color: #7dd3fc; }
        .tcr-badge-debt-danger { background-color: #fee2e2; color: #b91c1c; }
        .dark .tcr-badge-debt-danger, html.dark .tcr-badge-debt-danger { background-color: #7f1d1d; color: #fca5a5; }
        .tcr-badge-debt-success { background-color: #dcfce7; color: #15803d; }
        .dark .tcr-badge-debt-success, html.dark .tcr-badge-debt-success { background-color: #14532d; color: #86efac; }
    </style>

    <x-filament::section icon="heroicon-o-adjustments-horizontal">
        <x-slot name="heading">
            Filter & Pengurutan Peringkat Pelanggan
        </x-slot>
        <x-slot name="description">
            Pilih periode tanggal dan kriteria pengurutan untuk melihat pelanggan paling loyal dan aktif.
        </x-slot>

        <form wire:submit="show">
            {{ $this->form }}

            <div class="mt-4 flex items-center gap-3">
                <x-filament::button type="submit" icon="heroicon-o-magnifying-glass">
                    Tampilkan Peringkat
                </x-filament::button>
            </div>
        </form>
    </x-filament::section>

    @if ($this->report)
        @php
            $summary = $this->report['summary'];
            $filters = $this->report['filters'];
        @endphp

        <div class="tcr-wrap">
            {{-- Summary Metric Cards --}}
            <div class="tcr-metrics">
                <div class="tcr-metric-card">
                    <div class="tcr-metric-title">Pelanggan Terdaftar Aktif</div>
                    <div class="tcr-metric-number text-teal-600 dark:text-teal-400">
                        {{ number_format($summary['active_customers_count']) }}
                    </div>
                    <div class="tcr-metric-subtitle">
                        {{ number_format($summary['customer_transactions']) }} total nota transaksi
                    </div>
                </div>

                <div class="tcr-metric-card">
                    <div class="tcr-metric-title">Omset Pelanggan Member</div>
                    <div class="tcr-metric-number text-blue-600 dark:text-blue-400">
                        {{ rupiah($summary['customer_revenue']) }}
                    </div>
                    <div class="tcr-metric-subtitle">
                        {{ formatQuantity($summary['customer_quantity']) }} total kuantitas produk
                    </div>
                </div>

                <div class="tcr-metric-card">
                    <div class="tcr-metric-title">Pelanggan Umum (Walk-in)</div>
                    <div class="tcr-metric-number">
                        {{ rupiah($summary['walk_in_revenue']) }}
                    </div>
                    <div class="tcr-metric-subtitle">
                        {{ number_format($summary['walk_in_transactions']) }} nota tanpa nama pelanggan
                    </div>
                </div>

                <div class="tcr-metric-card">
                    <div class="tcr-metric-title">Total Keseluruhan Toko</div>
                    <div class="tcr-metric-number text-slate-800 dark:text-slate-100">
                        {{ rupiah($summary['grand_total_revenue']) }}
                    </div>
                    <div class="tcr-metric-subtitle">
                        {{ number_format($summary['grand_total_transactions']) }} transaksi total toko
                    </div>
                </div>
            </div>

            {{-- Ranked Table Section --}}
            <x-filament::section icon="heroicon-o-trophy">
                <x-slot name="heading">
                    Peringkat Pelanggan: {{ $filters['period_label'] }}
                </x-slot>
                <x-slot name="description">
                    Diurutkan berdasarkan: {{ $filters['sort_label'] }} &middot; Menampilkan {{ count($this->report['customers']) }} dari {{ $summary['active_customers_count'] }} pelanggan
                </x-slot>

                <div class="tcr-table-wrap">
                    <table class="tcr-table">
                        <thead>
                            <tr>
                                <th class="tcr-center" style="width: 60px;">Peringkat</th>
                                <th>Nama Pelanggan</th>
                                <th>No. HP / WhatsApp</th>
                                <th class="tcr-center">Jumlah Transaksi</th>
                                <th class="tcr-right">Total Belanja</th>
                                <th class="tcr-center">Total Kuantitas</th>
                                <th class="tcr-right">Rata-rata / Nota</th>
                                <th class="tcr-center">Transaksi Terakhir</th>
                                <th class="tcr-right">Sisa Piutang Saat Ini</th>
                                <th class="tcr-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($this->report['customers'] as $cust)
                                <tr>
                                    <td class="tcr-center">
                                        <span class="tcr-rank {{ $cust['rank'] === 1 ? 'rank-1' : ($cust['rank'] === 2 ? 'rank-2' : ($cust['rank'] === 3 ? 'rank-3' : '')) }}">
                                            {{ $cust['rank'] }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="font-semibold text-slate-900 dark:text-slate-100">
                                            {{ $cust['name'] }}
                                            @if ($cust['is_deleted'])
                                                <span class="text-xs text-rose-500 font-normal">(Nonaktif)</span>
                                            @endif
                                        </div>
                                        @if ($cust['address'])
                                            <div class="text-xs text-slate-500 dark:text-slate-400">
                                                {{ Str::limit($cust['address'], 35) }}
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($cust['phone'])
                                            <span class="font-mono text-xs">{{ $cust['phone'] }}</span>
                                        @else
                                            <span class="text-xs text-slate-400">Tanpa nomor</span>
                                        @endif
                                    </td>
                                    <td class="tcr-center">
                                        <span class="tcr-badge tcr-badge-txns">
                                            {{ number_format($cust['transaction_count']) }} nota
                                        </span>
                                    </td>
                                    <td class="tcr-right font-semibold text-slate-900 dark:text-slate-100">
                                        {{ rupiah($cust['total_spent']) }}
                                    </td>
                                    <td class="tcr-center text-slate-600 dark:text-slate-300">
                                        {{ formatQuantity($cust['total_quantity']) }}
                                    </td>
                                    <td class="tcr-right text-slate-600 dark:text-slate-300">
                                        {{ rupiah($cust['average_spent']) }}
                                    </td>
                                    <td class="tcr-center text-xs text-slate-500 dark:text-slate-400">
                                        {{ $cust['last_sale_date_formatted'] }}
                                    </td>
                                    <td class="tcr-right">
                                        @if ($cust['unpaid_receivable'] > 0)
                                            <span class="tcr-badge tcr-badge-debt-danger" title="Pelanggan memiliki tagihan piutang belum lunas">
                                                {{ rupiah($cust['unpaid_receivable']) }}
                                            </span>
                                        @else
                                            <span class="tcr-badge tcr-badge-debt-success">
                                                Lunas
                                            </span>
                                        @endif
                                    </td>
                                    <td class="tcr-center">
                                        <a
                                            href="{{ \App\Filament\Resources\ReceivablePartyResource::getUrl('view', ['record' => $cust['party_id']]) }}"
                                            class="text-xs font-semibold text-teal-600 dark:text-teal-400 hover:underline inline-flex items-center gap-1"
                                            title="Buka profil pelanggan dan riwayat piutang"
                                        >
                                            Detail Pelanggan
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="py-8 text-center text-slate-500 dark:text-slate-400">
                                        <div class="flex flex-col items-center justify-center gap-2">
                                            <x-filament::icon icon="heroicon-o-user-minus" style="width: 36px; height: 36px; color: #94a3b8;" />
                                            <p class="font-medium">Tidak ada transaksi pelanggan yang sesuai dengan filter yang dipilih.</p>
                                            <p class="text-xs text-slate-400">Cobalah memperluas periode tanggal atau mengganti kata kunci pencarian.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-filament::section>
        </div>
    @endif
</x-filament-panels::page>

<x-filament-panels::page>
    <x-filament::section icon="heroicon-o-banknotes">
        <x-slot name="heading">
            Form Pembayaran Kolektif Piutang
        </x-slot>
        <x-slot name="description">
            Penerimaan pembayaran dari pelanggan akan dialokasikan otomatis ke nota piutang tertua secara FIFO (First In, First Out).
        </x-slot>

        <form wire:submit="process" class="space-y-4">
            {{ $this->form }}

            <div class="mt-4 flex items-center gap-3">
                <x-filament::button type="submit" icon="heroicon-o-check-badge" size="lg">
                    Proses Penerimaan Pembayaran
                </x-filament::button>
            </div>
        </form>
    </x-filament::section>

    @if ($this->result)
        <div class="mt-6 space-y-6">
            <x-filament::section icon="heroicon-o-receipt-percent">
                <x-slot name="heading">
                    Hasil Penerimaan Pembayaran Kolektif
                </x-slot>
                <x-slot name="description">
                    Transaksi {{ $this->result['collectivePayment']['transaction_number'] }} berhasil diproses.
                </x-slot>

                {{-- Metric Summary --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    <div class="flex items-center gap-3.5 p-4 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50">
                        <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-indigo-500/10 text-indigo-600 dark:text-indigo-400">
                            <x-filament::icon icon="heroicon-o-user" class="w-5 h-5" />
                        </div>
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Pelanggan / Debitur</div>
                            <div class="text-sm font-bold text-slate-900 dark:text-white">{{ $this->result['collectivePayment']['party']['name'] }}</div>
                        </div>
                    </div>

                    <div class="flex items-center gap-3.5 p-4 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50">
                        <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
                            <x-filament::icon icon="heroicon-o-banknotes" class="w-5 h-5" />
                        </div>
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Total Diterima</div>
                            <div class="text-sm font-bold text-emerald-600 dark:text-emerald-400 font-mono">{{ rupiah($this->result['collectivePayment']['amount']) }}</div>
                        </div>
                    </div>

                    <div class="flex items-center gap-3.5 p-4 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50">
                        <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-blue-500/10 text-blue-600 dark:text-blue-400">
                            <x-filament::icon icon="heroicon-o-calendar" class="w-5 h-5" />
                        </div>
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Tanggal</div>
                            <div class="text-sm font-bold text-slate-900 dark:text-white">{{ $this->result['collectivePayment']['payment_date'] }}</div>
                        </div>
                    </div>

                    <div class="flex items-center gap-3.5 p-4 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50">
                        <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-amber-500/10 text-amber-600 dark:text-amber-400">
                            <x-filament::icon icon="heroicon-o-document-duplicate" class="w-5 h-5" />
                        </div>
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Nota Teralokasi</div>
                            <div class="text-sm font-bold text-slate-900 dark:text-white">{{ count($this->result['allocations']) }} Nota</div>
                        </div>
                    </div>
                </div>

                {{-- Table --}}
                <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-800">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-50 dark:bg-slate-800/80 border-b border-slate-200 dark:border-slate-700">
                            <tr>
                                <th class="px-4 py-3 text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300">No. Nota</th>
                                <th class="px-4 py-3 text-xs font-bold uppercase tracking-wider text-right text-slate-600 dark:text-slate-300">Nominal Dialokasikan</th>
                                <th class="px-4 py-3 text-xs font-bold uppercase tracking-wider text-right text-slate-600 dark:text-slate-300">Sisa Piutang Nota</th>
                                <th class="px-4 py-3 text-xs font-bold uppercase tracking-wider text-center text-slate-600 dark:text-slate-300">Status Nota</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach ($this->result['allocations'] as $allocation)
                                <tr class="hover:bg-indigo-50/30 dark:hover:bg-indigo-950/20 transition-colors">
                                    <td class="px-4 py-3 font-medium">
                                        <span class="inline-flex items-center font-mono text-xs font-semibold px-2.5 py-1 rounded-md bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                                            {{ $allocation['invoice_number'] }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right font-mono font-semibold text-emerald-600 dark:text-emerald-400">
                                        {{ rupiah($allocation['amount']) }}
                                    </td>
                                    <td class="px-4 py-3 text-right font-mono font-semibold text-slate-900 dark:text-slate-100">
                                        {{ rupiah($allocation['remaining']) }}
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        @if ($allocation['status'] === 'paid')
                                            <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-500/10 px-3 py-1 text-xs font-semibold text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                                Lunas
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-500/10 px-3 py-1 text-xs font-semibold text-amber-600 dark:text-amber-400 border border-amber-500/20">
                                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                                Belum Lunas
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-filament::section>
        </div>
    @endif
</x-filament-panels::page>

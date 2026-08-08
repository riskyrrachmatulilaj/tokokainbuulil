<x-filament-panels::page>
    <form wire:submit="process">
        {{ $this->form }}

        <x-filament::button type="submit" icon="heroicon-o-banknotes">
            Proses Pembayaran
        </x-filament::button>
    </form>

    @if ($this->result)
        <div class="mt-6">
            <x-filament::section>
                <x-slot name="heading">
                    Hasil Pembayaran Kolektif
                </x-slot>
                <x-slot name="description">
                    Transaksi {{ $this->result['collectivePayment']['transaction_number'] }}
                </x-slot>

                <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                    <div class="rounded-xl bg-gray-50 p-4 dark:bg-white/5">
                        <div class="text-sm text-gray-500 dark:text-gray-400">Pelanggan</div>
                        <div class="mt-1 font-semibold">{{ $this->result['collectivePayment']['customer']['name'] }}</div>
                    </div>
                    <div class="rounded-xl bg-gray-50 p-4 dark:bg-white/5">
                        <div class="text-sm text-gray-500 dark:text-gray-400">Total Dibayar</div>
                        <div class="mt-1 font-semibold">{{ rupiah($this->result['collectivePayment']['amount']) }}</div>
                    </div>
                    <div class="rounded-xl bg-gray-50 p-4 dark:bg-white/5">
                        <div class="text-sm text-gray-500 dark:text-gray-400">Tanggal</div>
                        <div class="mt-1 font-semibold">{{ $this->result['collectivePayment']['payment_date'] }}</div>
                    </div>
                    <div class="rounded-xl bg-gray-50 p-4 dark:bg-white/5">
                        <div class="text-sm text-gray-500 dark:text-gray-400">Nota Terkena</div>
                        <div class="mt-1 font-semibold">{{ count($this->result['allocations']) }}</div>
                    </div>
                </div>

                <div class="mt-4 overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="border-b dark:border-white/10">
                                <th class="px-3 py-2 font-semibold">No. Nota</th>
                                <th class="px-3 py-2 font-semibold">Nominal Dibayar</th>
                                <th class="px-3 py-2 font-semibold">Sisa Hutang</th>
                                <th class="px-3 py-2 font-semibold">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($this->result['allocations'] as $allocation)
                                <tr class="border-b dark:border-white/5">
                                    <td class="px-3 py-2 font-medium">{{ $allocation['invoice_number'] }}</td>
                                    <td class="px-3 py-2">{{ rupiah($allocation['amount']) }}</td>
                                    <td class="px-3 py-2">{{ rupiah($allocation['remaining']) }}</td>
                                    <td class="px-3 py-2">
                                        @if ($allocation['status'] === 'paid')
                                            <span class="inline-flex items-center gap-1 rounded-md bg-success-500/10 px-2 py-1 text-xs font-medium text-success-700 dark:text-success-300">
                                                <x-filament::icon icon="heroicon-m-check-circle" class="h-3 w-3" />
                                                Lunas
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 rounded-md bg-warning-500/10 px-2 py-1 text-xs font-medium text-warning-700 dark:text-warning-300">
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

<x-filament-panels::page>
    <x-filament::section icon="heroicon-o-funnel">
        <x-slot name="heading">
            Filter Laporan
        </x-slot>

        <form wire:submit="show">
            {{ $this->form }}

            <x-filament::button type="submit" icon="heroicon-o-magnifying-glass">
                Tampilkan Laporan
            </x-filament::button>
        </form>
    </x-filament::section>

    @if ($this->results)
        @php
            $type = $this->data['type'] ?? 'debt_list';
            $columns = \App\Exports\ReportExport::columnsFor($type);
        @endphp

        <div class="mt-6">
            <x-filament::section icon="heroicon-o-document-chart-bar">
                <x-slot name="heading">
                    {{ $this->getResultTitle() }}
                </x-slot>
                <x-slot name="description">
                    {{ $this->results->count() }} baris data
                </x-slot>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="border-b dark:border-white/10">
                                @foreach ($columns as $index => $column)
                                    <th class="px-3 py-2 font-semibold text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                        {{ $column }}
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($this->results as $row)
                                <tr class="border-b dark:border-white/5">
                                    @foreach (\App\Exports\ReportExport::valuesFor($type, $row) as $index => $value)
                                        <td class="px-3 py-2">
                                            @if (in_array($columns[$index] ?? null, ['Nominal', 'Sudah Dibayar', 'Sisa Piutang', 'Total', 'Total Sisa Piutang'], true))
                                                <span class="font-medium">{{ rupiah($value) }}</span>
                                            @elseif (str_contains($columns[$index] ?? '', 'Status'))
                                                @if (str_contains($value, 'Lunas'))
                                                    <span class="inline-flex rounded-md bg-success-500/10 px-2 py-1 text-xs font-medium text-success-700 dark:text-success-300">{{ $value }}</span>
                                                @else
                                                    <span class="inline-flex rounded-md bg-danger-500/10 px-2 py-1 text-xs font-medium text-danger-700 dark:text-danger-300">{{ $value }}</span>
                                                @endif
                                            @elseif (in_array($columns[$index] ?? null, ['Jumlah Transaksi', 'Jumlah Nota Belum Lunas', 'Jumlah Jatuh Tempo'], true))
                                                <span class="inline-flex rounded-md bg-gray-500/10 px-2 py-1 text-xs font-medium">{{ $value }}</span>
                                            @else
                                                {{ $value }}
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ count($columns) }}" class="px-3 py-8 text-center text-gray-500">
                                        Tidak ada data untuk filter yang dipilih.
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

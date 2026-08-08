<x-filament-panels::page>
    <div class="space-y-6">

        {{-- Section 1: Information Overview --}}
        <x-filament::section icon="heroicon-o-circle-stack">
            <x-slot name="heading">
                Informasi Database Aktif
            </x-slot>
            <x-slot name="description">
                Seluruh data toko (penjualan, produk, pelanggan, dan piutang) tersimpan dalam satu file SQLite.
            </x-slot>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                    <div class="text-xs font-medium text-gray-500 dark:text-gray-400">Ukuran File Database</div>
                    <div class="mt-1 text-xl font-bold text-primary-600 dark:text-primary-400">{{ $info['file_size'] ?? '-' }}</div>
                </div>

                <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                    <div class="text-xs font-medium text-gray-500 dark:text-gray-400">Terakhir Diperbarui</div>
                    <div class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ $info['last_modified'] ?? '-' }}</div>
                </div>

                <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                    <div class="text-xs font-medium text-gray-500 dark:text-gray-400">Total Transaksi Penjualan</div>
                    <div class="mt-1 text-xl font-bold text-gray-900 dark:text-white">{{ number_format($info['total_sales'] ?? 0) }}</div>
                </div>

                <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                    <div class="text-xs font-medium text-gray-500 dark:text-gray-400">Total Master Produk</div>
                    <div class="mt-1 text-xl font-bold text-gray-900 dark:text-white">{{ number_format($info['total_products'] ?? 0) }}</div>
                </div>
            </div>
        </x-filament::section>

        {{-- Section 2: Quick Actions --}}
        <x-filament::section icon="heroicon-o-arrow-down-tray">
            <x-slot name="heading">
                Unduh / Backup Cadangan
            </x-slot>
            <x-slot name="description">
                Klik tombol di bawah ini untuk mengunduh file cadangan ke komputer Anda secara langsung.
            </x-slot>

            <div class="flex flex-wrap items-center gap-4">
                <x-filament::button
                    type="button"
                    color="success"
                    size="lg"
                    icon="heroicon-o-arrow-down-tray"
                    wire:click="downloadLiveBackup"
                >
                    Download Database Sekarang (.sqlite)
                </x-filament::button>

                <x-filament::button
                    type="button"
                    color="primary"
                    size="lg"
                    icon="heroicon-o-document-duplicate"
                    wire:click="saveStoredBackup"
                >
                    Simpan Cadangan ke Storage Lokal
                </x-filament::button>
            </div>

            <div class="mt-4 rounded-lg bg-gray-50 p-4 text-xs text-gray-600 dark:bg-gray-800/50 dark:text-gray-300">
                <div class="font-semibold text-gray-900 dark:text-white">💡 Tips Keamanan Data:</div>
                <ul class="mt-1 list-disc space-y-1 pl-4">
                    <li>Disarankan untuk men-download backup secara berkala (misal: setiap akhir pekan atau akhir bulan).</li>
                    <li>Simpan file hasil download di tempat aman seperti Flashdisk atau Google Drive.</li>
                    <li>Jika terjadi masalah komputer, Anda dapat memulihkan seluruh data hanya dengan meletakkan kembali file ini.</li>
                </ul>
            </div>
        </x-filament::section>

        {{-- Section 3: Stored Backups List --}}
        @if (! empty($storedBackups))
            <x-filament::section icon="heroicon-o-archive-box">
                <x-slot name="heading">
                    Riwayat Cadangan Lokal
                </x-slot>

                <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-800">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-50 text-xs font-semibold uppercase text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                            <tr>
                                <th class="px-4 py-3">Nama File</th>
                                <th class="px-4 py-3">Ukuran</th>
                                <th class="px-4 py-3">Waktu Pembuatan</th>
                                <th class="px-4 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                            @foreach ($storedBackups as $backup)
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/50">
                                    <td class="px-4 py-3 font-mono font-medium text-gray-900 dark:text-white">
                                        {{ $backup['filename'] }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
                                        {{ $backup['size'] }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
                                        {{ $backup['created_at'] }}
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="flex justify-end gap-2">
                                            <x-filament::button
                                                type="button"
                                                color="success"
                                                size="xs"
                                                icon="heroicon-o-arrow-down-tray"
                                                wire:click="downloadStoredFile('{{ addslashes($backup['filepath']) }}')"
                                            >
                                                Download
                                            </x-filament::button>

                                            <x-filament::button
                                                type="button"
                                                color="danger"
                                                size="xs"
                                                outlined
                                                icon="heroicon-o-trash"
                                                wire:click="deleteStoredFile('{{ addslashes($backup['filepath']) }}')"
                                                wire:confirm="Yakin ingin menghapus file backup ini?"
                                            >
                                                Hapus
                                            </x-filament::button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-filament::section>
        @endif

    </div>
</x-filament-panels::page>

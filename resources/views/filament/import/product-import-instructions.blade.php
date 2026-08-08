{{-- Petunjuk import batch produk --}}
<div class="space-y-3 text-sm text-gray-700 dark:text-gray-200">
    <p>
        Gunakan template Excel agar kolom sesuai. Duplikat (nama produk sama)
        akan <strong>diperbarui</strong> (upsert) harganya, keterangan, dan statusnya.
    </p>

    <div>
        <p class="font-semibold mb-1">Kolom yang didukung</p>
        <ul class="list-disc list-inside space-y-0.5">
            <li><strong>Nama</strong> — wajib, maksimal 255 karakter</li>
            <li><strong>Harga</strong> — wajib, harus berupa angka positif lebih dari 0 (contoh: 150000)</li>
            <li><strong>Keterangan</strong> — opsional, maksimal 1000 karakter</li>
            <li><strong>Status</strong> — opsional, berisi 'Aktif' atau 'Nonaktif' (default jika kosong: 'Aktif')</li>
        </ul>
    </div>

    <div>
        <p class="font-semibold mb-1">Tips</p>
        <ul class="list-disc list-inside space-y-0.5">
            <li>Simpan berkas sebagai <strong>.xlsx</strong> atau <strong>.csv</strong></li>
            <li>Baris pertama harus berisi header kolom</li>
            <li>Kolom Harga tidak boleh diisi karakter selain angka (jangan sertakan simbol mata uang Rp atau titik pemisah ribuan)</li>
            <li>Baris kosong akan dilewati</li>
            <li>Data dengan nama produk yang sama dalam berkas hanya baris pertama yang diproses</li>
        </ul>
    </div>
</div>

{{-- Petunjuk import batch pelanggan / debitur --}}
<div class="space-y-3 text-sm text-gray-700 dark:text-gray-200">
    <p>
        Gunakan template Excel agar kolom sesuai. Duplikat (nama + telepon sama)
        akan <strong>diperbarui</strong> (upsert), bukan digandakan.
    </p>

    <div>
        <p class="font-semibold mb-1">Kolom yang didukung</p>
        <ul class="list-disc list-inside space-y-0.5">
            <li><strong>Nama</strong> — wajib, maksimal 255 karakter</li>
            <li><strong>Telepon</strong> — opsional, maksimal 30 karakter (contoh: 081234567890)</li>
            <li><strong>Alamat</strong> — opsional, maksimal 1000 karakter</li>
        </ul>
    </div>

    <div>
        <p class="font-semibold mb-1">Tips</p>
        <ul class="list-disc list-inside space-y-0.5">
            <li>Simpan berkas sebagai <strong>.xlsx</strong> atau <strong>.csv</strong></li>
            <li>Baris pertama harus berisi header kolom</li>
            <li>Format nomor telepon sebagai teks agar angka 0 di depan tidak hilang</li>
            <li>Baris kosong akan dilewati</li>
            <li>Data yang sama dalam berkas (nama + telepon) hanya baris pertama yang diproses</li>
        </ul>
    </div>
</div>

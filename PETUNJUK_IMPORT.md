# Petunjuk Impor Batch Excel - Pelanggan & Debitur

Dokumen ini berisi panduan lengkap untuk menggunakan modul **Batch Impor** data Pelanggan dan Debitur menggunakan berkas Excel (`.xlsx`) atau CSV (`.csv`) pada aplikasi **Manajemen Hutang Pelanggan**.

---

## 📌 Deskripsi Modul Impor
Aplikasi menyediakan fitur impor batch untuk mempercepat pengisian data Pelanggan dan Debitur secara massal. Modul ini dirancang dengan prinsip **minimalis & aman**:
1. **Akses Khusus Admin**: Tombol impor hanya terlihat dan dapat diakses oleh pengguna dengan peran/role `admin`. Pengguna dengan peran `kasir` tidak dapat melakukan impor.
2. **Mekanisme Upsert (Tambah & Perbarui)**:
   - Jika kombinasi **Nama** dan **Nomor Telepon** sudah ada di database, sistem akan **memperbarui** alamat data tersebut, alih-alih membuat data ganda.
   - Jika data sebelumnya telah dihapus (soft-deleted), sistem secara otomatis akan **memulihkan** (restore) data tersebut.
3. **Pemberangkatan & Validasi Baris**:
   - Jika terdapat baris data yang tidak valid (misalnya Nama kosong), sistem akan melewati baris tersebut, mencatat kesalahannya, dan **tetap memproses baris lain yang valid**.
   - Di akhir proses, sistem menampilkan notifikasi ringkasan: jumlah data baru yang berhasil dibuat, jumlah data yang diperbarui, serta daftar nomor baris yang gagal beserta alasannya.

---

## 🛠️ Langkah-Langkah Melakukan Impor

Berikut langkah-langkah untuk melakukan impor data Pelanggan atau Debitur:

### 1. Masuk Sebagai Admin
Pastikan Anda masuk ke Panel Admin Filament menggunakan akun ber-role `admin` (contoh default seeder: `admin@hutang.test` / `password`).

### 2. Buka Halaman Target
- Untuk pelanggan: Pilih menu **Pelanggan** di sidebar kiri.
- Untuk debitur: Pilih menu **Debitur** di bawah grup **Manajemen Piutang**.

### 3. Unduh Template Excel
Di bagian pojok kanan atas tabel, Anda akan melihat tombol **"Petunjuk Import"** (ikon tanda tanya abu-abu) atau tombol **"Impor Excel"** (ikon unggah hijau).
- Klik tombol **"Petunjuk Import"** atau **"Impor Excel"**.
- Di dalam jendela popup (modal) yang muncul, klik tombol **"Unduh Template Excel"**.
- Simpan berkas template tersebut di komputer Anda.

### 4. Isi Data di Template
Buka berkas template yang telah diunduh menggunakan aplikasi spreadsheet (seperti Microsoft Excel, Google Sheets, atau LibreOffice).
Isi baris data mulai dari baris kedua (di bawah header kolom).

> [!IMPORTANT]  
> Jangan mengubah nama atau urutan header kolom pada baris pertama (`Nama`, `Telepon`, `Alamat`), karena sistem menggunakannya sebagai acuan pembacaan data.

---

## 📋 Spesifikasi Kolom Data

| Nama Kolom | Status | Ketentuan & Validasi | Tips Pengisian |
| :--- | :--- | :--- | :--- |
| **Nama** | **Wajib** | Maksimal 255 karakter. Tidak boleh kosong. | Nama pelanggan atau nama instansi debitur (contoh: `CV Maju Jaya` or `Budi Santoso`). |
| **Telepon** | Opsional | Maksimal 30 karakter. | **Format kolom sebagai Teks** di Excel sebelum mengetik angka `0` di awal nomor agar tidak hilang (contoh: `081234567890`). |
| **Alamat** | Opsional | Maksimal 1000 karakter. | Alamat lengkap pelanggan atau debitur. |

---

## 💡 Tips & Aturan Impor

- **Format File**: Sistem mendukung format `.xlsx` (Excel modern) dan `.csv`.
- **Nomor Telepon**: 
  - Selalu format kolom nomor telepon sebagai **Teks / String** di Microsoft Excel agar angka nol di depan (misal: `08...`) tidak dihapus oleh Excel dan berubah menjadi angka biasa.
  - Spasi, tanda hubung (`-`), titik (`.`), atau tanda kurung dalam nomor telepon akan otomatis dibersihkan oleh sistem (misalnya `0812-3456-7890` akan disimpan sebagai `081234567890`).
- **Data Duplikat**:
  - **Duplikat dalam file**: Jika terdapat dua baris atau lebih yang memiliki Nama dan Telepon yang persis sama dalam satu file yang diunggah, sistem hanya akan memproses **baris pertama**. Baris selanjutnya akan ditandai sebagai gagal karena duplikat dalam berkas.
  - **Duplikat terhadap database**: Jika Nama dan Telepon cocok dengan data yang sudah ada di database, data tersebut akan diperbarui alamatnya.
- **Baris Kosong**: Baris kosong di bagian bawah atau di tengah-tengah baris data Excel akan otomatis dilewati secara aman.

---

## ⚠️ Penanganan Kesalahan (Error Handling)

Jika terjadi kegagalan selama proses impor, Anda akan melihat notifikasi berwarna kuning atau merah dengan detail informasi:
- **Validasi Berkas Gagal**: Terjadi jika file tidak terbaca, kolom wajib `Nama` tidak ditemukan di baris header, atau ukuran file melebihi batas (maksimal 5 MB).
- **Laporan Per-Baris**: Jika terdapat baris yang bermasalah (misalnya Nama kosong atau melebihi batas panjang karakter), notifikasi akhir akan menampilkan detail seperti:
  - `Baris 5: Nama wajib diisi`
  - `Baris 8: Duplikat dalam berkas (sama dengan baris 3)`
  - Dengan demikian, Anda tahu persis baris mana yang harus diperbaiki tanpa perlu menebak-nebak.

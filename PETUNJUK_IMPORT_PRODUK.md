# Petunjuk Impor Batch Excel - Barang/Produk

Dokumen ini berisi panduan lengkap untuk menggunakan modul **Batch Impor** data Barang/Produk menggunakan berkas Excel (`.xlsx`) atau CSV (`.csv`) pada aplikasi **Manajemen Hutang Pelanggan**.

---

## 📌 Deskripsi Modul Impor
Aplikasi menyediakan fitur impor batch untuk mempercepat pengisian data Barang/Produk secara massal. Modul ini dirancang dengan prinsip **minimalis & aman**:
1. **Akses Khusus Admin**: Tombol impor hanya terlihat dan dapat diakses oleh pengguna dengan peran/role `admin`. Pengguna dengan peran `kasir` tidak dapat melakukan impor.
2. **Mekanisme Upsert (Tambah & Perbarui)**:
   - Jika **Nama Produk** sudah ada di database (pencocokan bersifat *case-insensitive* / tidak sensitif huruf besar-kecil), sistem akan **memperbarui** harga, keterangan, dan status keaktifan produk tersebut, alih-alih membuat data ganda.
3. **Pemberangkatan & Validasi Baris**:
   - Jika terdapat baris data yang tidak valid (misalnya Nama kosong atau Harga kurang dari atau sama dengan 0), sistem akan melewati baris tersebut, mencatat kesalahannya, dan **tetap memproses baris lain yang valid**.
   - Di akhir proses, sistem menampilkan notifikasi ringkasan: jumlah produk baru yang berhasil dibuat, jumlah produk yang diperbarui, serta daftar nomor baris yang gagal beserta alasannya.

---

## 🛠️ Langkah-Langkah Melakukan Impor

Berikut langkah-langkah untuk melakukan impor data Barang/Produk:

### 1. Masuk Sebagai Admin
Pastikan Anda masuk ke Panel Admin Filament menggunakan akun ber-role `admin` (contoh default seeder: `admin@hutang.test` / `password`).

### 2. Buka Halaman Produk
Pilih menu **Produk** di bawah grup **Kasir** pada sidebar kiri.

### 3. Unduh Template Excel
Di bagian pojok kanan atas tabel, Anda akan melihat tombol **"Petunjuk Import"** (ikon tanda tanya abu-abu) atau tombol **"Impor Excel"** (ikon unggah hijau).
- Klik tombol **"Petunjuk Import"** atau **"Impor Excel"**.
- Di dalam jendela popup (modal) yang muncul, klik tombol **"Unduh Template Excel"**.
- Simpan berkas template tersebut di komputer Anda.

### 4. Isi Data di Template
Buka berkas template yang telah diunduh menggunakan aplikasi spreadsheet (seperti Microsoft Excel, Google Sheets, atau LibreOffice).
Isi baris data mulai dari baris kedua (di bawah header kolom).

> [!IMPORTANT]  
> Jangan mengubah nama atau urutan header kolom pada baris pertama (`Nama`, `Harga`, `Keterangan`, `Status`), karena sistem menggunakannya sebagai acuan pembacaan data.

---

## 📋 Spesifikasi Kolom Data

| Nama Kolom | Status | Ketentuan & Validasi | Tips Pengisian |
| :--- | :--- | :--- | :--- |
| **Nama** | **Wajib** | Maksimal 255 karakter. Tidak boleh kosong. | Nama produk/barang yang dijual (contoh: `Kain Batik Parang (2 m)`). |
| **Harga** | **Wajib** | Angka bulat positif (lebih dari 0). | Masukkan angka murni **tanpa simbol Rp atau pemisah ribuan** (contoh: tulis `150000` bukan `Rp 150.000`). |
| **Keterangan** | Opsional | Maksimal 1000 karakter. | Keterangan atau spesifikasi tambahan produk. |
| **Status** | Opsional | Nilai yang didukung: `Aktif` atau `Nonaktif`. | Jika dikosongkan atau diisi selain `Nonaktif` (seperti `0`, `false`, `tidak aktif`), sistem akan menyetelnya menjadi `Aktif`. |

---

## 💡 Tips & Aturan Impor

- **Format File**: Sistem mendukung format `.xlsx` (Excel modern) dan `.csv`.
- **Format Harga**: Pastikan kolom Harga tidak berisi format teks non-angka.
- **Data Duplikat**:
  - **Duplikat dalam file**: Jika terdapat dua baris atau lebih yang memiliki Nama Produk yang sama dalam satu file yang diunggah, sistem hanya akan memproses **baris pertama**. Baris selanjutnya akan ditandai sebagai gagal.
  - **Duplikat terhadap database**: Jika Nama Produk cocok dengan data yang sudah ada di database, data tersebut akan diperbarui harganya, keterangan, dan statusnya.
- **Baris Kosong**: Baris kosong di bagian bawah atau di tengah-tengah baris data Excel akan otomatis dilewati secara aman.

---

## ⚠️ Penanganan Kesalahan (Error Handling)

Jika terjadi kegagalan selama proses impor, Anda akan melihat notifikasi berwarna kuning atau merah dengan detail informasi:
- **Validasi Berkas Gagal**: Terjadi jika file tidak terbaca, kolom wajib `Nama` atau `Harga` tidak ditemukan di baris header, atau ukuran file melebihi batas (maksimal 5 MB).
- **Laporan Per-Baris**: Jika terdapat baris yang bermasalah, notifikasi akhir akan menampilkan detail seperti:
  - `Baris 5: Nama Produk wajib diisi`
  - `Baris 8: Harga Jual wajib berupa angka lebih dari 0`
  - `Baris 11: Duplikat dalam berkas (sama dengan baris 3)`
  - Dengan demikian, Anda tahu persis baris mana yang harus diperbaiki tanpa perlu menebak-nebak.

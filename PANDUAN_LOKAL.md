# Panduan Menjalankan Aplikasi Secara Lokal (Offline)

Dokumen ini menjelaskan konfigurasi dan cara mudah menjalankan aplikasi **Manajemen Hutang Pelanggan** di laptop atau komputer lokal secara mandiri.

---

## 🛠️ Konfigurasi yang Digunakan

Untuk mempermudah penggunaan lokal tanpa perlu menginstal XAMPP, Laragon, atau database server MySQL, aplikasi ini telah dikonfigurasi menggunakan **SQLite**:
* **File Database**: Tersimpan langsung di dalam folder project pada path `database/database.sqlite`.
* **Prasyarat**: Hanya membutuhkan PHP (minimal versi 8.2) yang terpasang di komputer Anda. *(PHP 8.5 sudah terdeteksi aktif di komputer Anda)*.
* **Aset Frontend**: Aset CSS dan JS sudah dicompile sehingga tampilan web akan berjalan dengan normal dan cepat.

---

## 🚀 Cara Cepat Membuka Website

Kami telah menyediakan file pembuka otomatis bernama **[buka_website.bat](file:///c:/Users/ADMIN/Documents/Default%20Project/hutang-app/buka_website.bat)** di folder utama aplikasi.

### Langkah-langkah:
1. Buka folder aplikasi ini (`hutang-app`).
2. Klik 2x pada file **`buka_website.bat`**.
3. Sebuah jendela Command Prompt (CMD) hitam akan terbuka dan secara otomatis:
   * Menyalakan server lokal PHP di background.
   * Membuka web browser bawaan komputer Anda ke alamat admin panel: **`http://127.0.0.1:8000/admin`**.
4. **Penting**: Biarkan jendela CMD hitam tetap terbuka selama Anda menggunakan website. Untuk mematikan server aplikasi setelah selesai digunakan, Anda cukup menutup jendela CMD hitam tersebut.

---

## 🔑 Akun Login Default (Uji Coba)

Database lokal Anda sudah diisi dengan data contoh/dummy (pelanggan, transaksi, riwayat pembayaran). Anda bisa masuk menggunakan salah satu akun berikut:

* **Akun Admin** (Akses Penuh):
  * **Email**: `admin@hutang.test`
  * **Password**: `password`
* **Akun Kasir** (Transaksi & Pelanggan, tanpa kelola user):
  * **Email**: `kasir@hutang.test`
  * **Password**: `password`

---

## 🔄 Cara Reset / Kosongkan Data Baru

Jika Anda ingin menghapus semua data dummy dan memulai pencatatan riwayat hutang riil dari awal, jalankan perintah berikut di Command Prompt / Terminal dalam folder project:

```bash
php artisan migrate:fresh
```

*Catatan: Perintah di atas akan menghapus semua data transaksi lama. Setelah melakukan reset, Anda harus membuat user admin baru dengan perintah:*

```bash
php artisan make:filament-user
```
*(Lalu ikuti instruksi pengisian nama, email, dan password di terminal).*

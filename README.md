# Manajemen Hutang Pelanggan

Aplikasi web untuk mengelola piutang / hutang pelanggan secara profesional. Dibangun dengan **Laravel 12**, **Filament v4**, dan **MySQL**.

## Fitur Utama

- **Pelanggan** — kelola data pelanggan (nama, telepon, alamat) beserta total sisa hutang dan jumlah nota. Hanya admin yang dapat menghapus pelanggan.
- **Hutang / Nota** — pencatatan nota hutang dengan nomor invoice otomatis (`INV-YYYYMMDD-0001`), tanggal jatuh tempo, status lunas/belum lunas, dan indikator keterlambatan.
- **Cicilan** — pembayaran cicilan per nota (transaksi, otomatis mengupdate saldo & status nota). Pembatalan cicilan hanya untuk admin.
- **Pembayaran Kolektif (FIFO)** — pembayaran satu nominal yang dialokasikan ke nota paling tua terlebih dahulu (*first-in, first-out*), berjalan dalam satu transaksi database.
- **Riwayat Pembayaran** — riwayat lengkap semua transaksi (cicilan & kolektif) dengan nomor transaksi otomatis (`TRX-YYYYMMDD-0001`).
- **Dashboard** — statistik ringkas (total piutang, nota jatuh tempo, pembayaran terbaru).
- **Laporan** — 5 jenis laporan dengan export **PDF** dan **Excel (XLSX)**:
  - Daftar Hutang
  - Riwayat Pembayaran
  - Ringkasan Pembayaran per Periode
  - Daftar Hutang Belum Lunas
  - Rekap Pelanggan Jatuh Tempo
- **Role Pengguna** — `admin` (akses penuh termasuk manajemen pengguna) dan `kasir` (transaksi & data pelanggan, tanpa manajemen pengguna).

## Teknologi

| Komponen | Versi / Paket |
| --- | --- |
| PHP | ^8.3 (dikembangkan di 8.5) |
| Laravel | 12.x |
| Filament | v4 (panels) |
| Database | MySQL 8.x |
| PDF | barryvdh/laravel-dompdf |
| Excel | openspout/openspout |

## Instalasi

### 1. Prasyarat

- PHP 8.3+ dengan ekstensi `pdo_mysql`, `gd`, `mbstring`, `dom`, dan `zip`
- Composer 2
- MySQL 8.x (server berjalan)

### 2. Konfigurasi Database

Salin file `.env.example` menjadi `.env` lalu sesuaikan kredensial database:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=hutang_db
DB_USERNAME=root
DB_PASSWORD=secret
```

Contoh jika memakai MySQL mandiri di port alternatif (mis. `3307`):

```env
DB_PORT=3307
DB_DATABASE=hutang_db
DB_USERNAME=root
DB_PASSWORD=
```

### 3. Install Dependency

```bash
composer install
```

### 4. Buat Tabel & Data Contoh

```bash
php artisan migrate:fresh --seed
```

Seeder membuat:

- Akun **Admin**: `admin@hutang.test` / `password`
- Akun **Kasir**: `kasir@hutang.test` / `password`
- 8 pelanggan, 13 nota hutang, beberapa cicilan, dan 1 contoh pembayaran kolektif FIFO (Rp900.000)

### 5. Jalankan Server

```bash
php artisan serve
```

Buka **http://127.0.0.1:8000/admin** lalu masuk dengan akun di atas.

## Struktur Proyek

```
app/
├── Exports/                    # Export Excel (OpenSpout)
├── Filament/
│   ├── Pages/                  # Halaman kustom (Pembayaran Kolektif, Laporan)
│   ├── Resources/              # Resource CRUD
│   │   ├── CustomerResource/           # Pelanggan
│   │   ├── DebtResource/               # Hutang / Nota
│   │   ├── InstallmentResource/        # Cicilan
│   │   ├── PaymentHistoryResource/     # Riwayat pembayaran
│   │   ├── CollectivePaymentResource/  # Pembayaran kolektif
│   │   ├── UserResource/               # Manajemen pengguna
│   │   └── (masing-masing punya subfolder Pages/ dan RelationManagers/)
│   └── Widgets/                # Widget dashboard
├── Models/                     # Eloquent models + soft deletes
├── Policies/                   # Authorization berbasis role
├── Providers/                  # AdminPanelProvider (konfigurasi panel Filament)
└── Services/                   # Logika bisnis (FIFO, nomor transaksi, laporan, PDF)
resources/views/
├── filament/pages/             # Blade halaman kustom
└── reports/pdf.blade.php       # Template laporan PDF
```

## Alur Pembayaran Kolektif (FIFO)

1. Pilih pelanggan dan nominal pembayaran.
2. Sistem mengambil nota belum lunas, diurutkan dari tanggal hutang **paling tua**.
3. Nominal dialokasikan berurutan hingga habis.
4. Nota yang teralokasi penuh otomatis berstatus **Lunas**; seluruh proses dicatat dalam satu `DB::transaction` dengan `lockForUpdate` untuk mencegah konflik data.

## Testing

```bash
php artisan test
```

Test mencakup: render semua halaman Filament, pembatasan akses kasir, logika FIFO, penolakan pembayaran melebihi sisa hutang, pembatalan cicilan, layanan laporan, serta export PDF dan Excel.

## Catatan Lingkungan

- Aplikasi memakai `APP_LOCALE=id` dan `APP_TIMEZONE=Asia/Jakarta`.
- Nomor invoice dan transaksi di-generate otomatis per hari (`INV/TRX-YYYYMMDD-XXXX`).
- Alokasi pembayaran kolektif berbagi satu nomor transaksi yang sama dengan transaksi induknya (lihat `payment_histories.transaction_number`).

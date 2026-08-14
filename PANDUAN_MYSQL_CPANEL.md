# Panduan Migrasi SQLite ke MySQL (cPanel)

Panduan ini menjelaskan cara memindahkan aplikasi **Toko Kain Bu Ulil** dari database SQLite (lokal) ke MySQL di cPanel hosting.

---

## Langkah 1: Export Data dari SQLite

Jalankan perintah berikut di komputer lokal (pastikan `.env` masih menggunakan SQLite):

```bash
php artisan db:export-mysql
```

File SQL akan dibuat di: `database/export_mysql.sql`

> **Catatan:** File ini hanya berisi **data** (INSERT), bukan struktur tabel.
> Struktur tabel akan dibuat oleh `php artisan migrate` di server.

---

## Langkah 2: Buat Database MySQL di cPanel

1. Login ke **cPanel** → **MySQL® Databases**
2. **Create New Database**: buat database baru (misal: `hutang_db`)
   - Nama lengkap akan menjadi: `cpanelusername_hutang_db`
3. **MySQL Users** → buat user baru (misal: `hutang_user`)
   - Nama lengkap: `cpanelusername_hutang_user`
   - Buat password yang kuat
4. **Add User To Database** → pilih user dan database yang baru dibuat
5. Centang **ALL PRIVILEGES** → klik **Make Changes**

---

## Langkah 3: Upload Project ke cPanel

### Via File Manager atau FTP:

1. Upload **semua file project** ke folder di cPanel (misal: `/home/username/hutang-app/`)
2. **JANGAN** upload file berikut:
   - `database/database.sqlite` (tidak diperlukan)
   - `node_modules/` (tidak diperlukan di production)
   - `.env` (akan dibuat baru dari template)

### Pengaturan Public HTML:

Arahkan domain/subdomain ke folder `public/` dari project:
- Misal: `/home/username/hutang-app/public`
- Bisa diatur via cPanel → **Domains** atau **Subdomains**

---

## Langkah 4: Konfigurasi .env untuk MySQL

1. Rename file `.env.mysql` menjadi `.env`:
   ```bash
   cp .env.mysql .env
   ```

2. Edit `.env` dan sesuaikan:
   ```ini
   APP_URL=https://namadomain.com

   DB_CONNECTION=mysql
   DB_HOST=localhost
   DB_PORT=3306
   DB_DATABASE=cpanelusername_hutang_db
   DB_USERNAME=cpanelusername_hutang_user
   DB_PASSWORD=password_yang_anda_buat
   ```

3. Generate APP_KEY baru (jika perlu):
   ```bash
   php artisan key:generate
   ```

---

## Langkah 5: Jalankan Migration & Import Data

### Jika ada akses SSH di cPanel:

```bash
cd /home/username/hutang-app
composer install --no-dev --optimize-autoloader
php artisan migrate --force
```

Lalu import data:
```bash
php artisan db:import-sql database/export_mysql.sql
```

Atau import manual via MySQL CLI:
```bash
mysql -u cpanelusername_hutang_user -p cpanelusername_hutang_db < database/export_mysql.sql
```

### Jika TIDAK ada akses SSH:

1. Buka **phpMyAdmin** dari cPanel
2. Pilih database `cpanelusername_hutang_db`
3. Buat tabel migration terlebih dahulu. Upload file project via File Manager lalu buat file `migrate.php` di folder `public/`:

```php
<?php
// File sementara: public/migrate.php
// HAPUS FILE INI SETELAH SELESAI!

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "<pre>";
Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
echo Illuminate\Support\Facades\Artisan::output();
echo "\n\nMigrasi selesai! HAPUS FILE INI SEGERA!";
echo "</pre>";
```

4. Akses `https://namadomain.com/migrate.php` dari browser
5. **HAPUS** file `migrate.php` setelah berhasil!
6. Buka **phpMyAdmin** → pilih database → tab **Import** → pilih file `export_mysql.sql` → klik **Go**

---

## Langkah 6: Optimasi Production

### Via SSH:
```bash
php artisan optimize
php artisan view:cache
php artisan route:cache
php artisan config:cache
```

### Via file browser (tanpa SSH):
Buat file `public/optimize.php`:

```php
<?php
// File sementara: public/optimize.php
// HAPUS FILE INI SETELAH SELESAI!

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "<pre>";
Illuminate\Support\Facades\Artisan::call('optimize');
echo Illuminate\Support\Facades\Artisan::output();
echo "\n\nOptimasi selesai! HAPUS FILE INI SEGERA!";
echo "</pre>";
```

Akses dari browser lalu **HAPUS** file tersebut.

---

## Langkah 7: Verifikasi

1. Buka website di browser
2. Login dengan akun admin: `admin@hutang.test` / `password`
3. Pastikan semua data muncul dengan benar:
   - ✅ Data pelanggan
   - ✅ Data hutang & cicilan
   - ✅ Data piutang
   - ✅ Data produk & penjualan
4. **Ganti password** setelah verifikasi!

---

## Troubleshooting

### Error "SQLSTATE[42S01]: Table already exists"
Tabel sudah dibuat sebelumnya. Jalankan:
```bash
php artisan migrate:fresh --force
```
⚠️ **Hati-hati:** Ini akan menghapus semua data di database. Import ulang data setelahnya.

### Error saat import SQL di phpMyAdmin
- Pastikan ukuran file tidak melebihi batas upload phpMyAdmin (biasanya 50MB)
- Jika file terlalu besar, split import per tabel
- Pastikan encoding UTF-8

### Error "Too many connections"
Edit `.env`:
```ini
DB_HOST=localhost
```
Jangan gunakan `127.0.0.1`, gunakan `localhost` di cPanel.

### Storage permission error
```bash
chmod -R 775 storage bootstrap/cache
```

---

## Ringkasan File Baru

| File | Fungsi |
|------|--------|
| `.env.mysql` | Template konfigurasi MySQL untuk cPanel |
| `app/Console/Commands/ExportToMysqlCommand.php` | Artisan command untuk export data SQLite → MySQL SQL |
| `PANDUAN_MYSQL_CPANEL.md` | Panduan ini |
| `database/export_mysql.sql` | File hasil export (dibuat oleh command) |

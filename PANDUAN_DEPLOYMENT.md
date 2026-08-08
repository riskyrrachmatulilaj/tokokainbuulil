# Panduan Deployment: Menjadikan Website Online

Dokumen ini berisi panduan langkah-demi-langkah untuk mendeploy (mengonlinekan) aplikasi **Manajemen Hutang Pelanggan** (Laravel 12, Filament v4, MySQL) agar bisa diakses oleh publik melalui internet.

---

## 📌 Pilihan Infrastruktur Hosting

Ada tiga metode populer untuk mengonlinekan aplikasi Laravel:

### 1. VPS (Virtual Private Server) — *Sangat Direkomendasikan*
* **Contoh Provider**: DigitalOcean, Hetzner, Vultr, Biznet Gio, IDCloudHost.
* **Kelebihan**: Kontrol penuh, performa tinggi, sangat cocok untuk Filament v4 (yang membutuhkan node/npm build dan antrean tugas/Queue Worker).
* **Kemudahan**: Bisa dipadukan dengan panel otomatis seperti **Laravel Forge**, **RunCloud**, atau **Ploi** agar setup server menjadi sangat mudah (otomatis setup Nginx, PHP, SSL, Database, dan Git).

### 2. PaaS (Platform as a Service) — *Mudah & Modern*
* **Contoh Provider**: Railway.app, Fly.io, Render.
* **Kelebihan**: Deployment otomatis dari GitHub, tidak perlu memikirkan konfigurasi server/Nginx.

### 3. Shared Hosting (cPanel) — *Murah & Populer*
* **Kelebihan**: Murah, pembayaran mudah di lokal Indonesia.
* **Kekurangan**: Tidak ada akses terminal SSH (pada paket murah), performa terbatas, sulit menjalankan Queue Worker (antrean cicilan/pembayaran).

---

## 🛠️ Langkah-Langkah Deployment (Menggunakan VPS Ubuntu & Nginx secara Manual)

Berikut adalah panduan setup manual menggunakan server VPS dengan sistem operasi **Ubuntu 22.04 LTS / 24.04 LTS**:

### Langkah 1: Persiapan Server (LEMP Stack)
Masuk ke server VPS Anda via SSH dan jalankan perintah untuk menginstal Nginx, MySQL, dan PHP:

```bash
# Update repository
sudo apt update && sudo apt upgrade -y

# Install Nginx & Git
sudo apt install nginx git unzip -y

# Tambahkan repository PHP jika versi PHP terbaru belum tersedia
sudo apt install software-properties-common -y
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update

# Install PHP 8.3/8.4 dan ekstensi yang dibutuhkan
sudo apt install php8.3-fpm php8.3-cli php8.3-mysql php8.3-gd php8.3-mbstring php8.3-xml php8.3-curl php8.3-zip -y
```

### Langkah 2: Install Composer & Node.js
```bash
# Install Composer secara global
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install Node.js (untuk compile aset Vite/Tailwind)
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install nodejs -y
```

### Langkah 3: Setup Database MySQL
```bash
sudo apt install mysql-server -y
sudo mysql_secure_installation
```
Masuk ke MySQL (`sudo mysql`) dan buat database baru untuk aplikasi:
```sql
CREATE DATABASE hutang_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'hutang_user'@'localhost' IDENTIFIED BY 'PasswordKuatAnda123!';
GRANT ALL PRIVILEGES ON hutang_db.* TO 'hutang_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### Langkah 4: Kloning Project & Install Dependencies
Pindahkan direktori ke folder web server Nginx:
```bash
cd /var/www
# Clone repository Git Anda
git clone https://github.com/username/repository-anda.git hutang-app
cd hutang-app

# Install PHP dependencies untuk production
composer install --no-dev --optimize-autoloader

# Install & Build frontend assets
npm install
npm run build
```

### Langkah 5: Konfigurasi Environment (`.env`)
Salin file `.env` dan edit isinya:
```bash
cp .env.example .env
nano .env
```
Sesuaikan konfigurasi production berikut di dalam file `.env`:
```env
APP_NAME="Toko Kain Bu Ulil"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://nama-domain-anda.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=hutang_db
DB_USERNAME=hutang_user
DB_PASSWORD=PasswordKuatAnda123!

QUEUE_CONNECTION=database
SESSION_DRIVER=database
CACHE_STORE=database
```
Jalankan perintah berikut untuk men-generate key aplikasi dan migrasi database:
```bash
php artisan key:generate --force
php artisan migrate --force
```

> [!WARNING]  
> Jangan menjalankan `db:seed` bawaan lokal ke server production karena itu akan mengisi database dengan data dummy penjualan/cicilan contoh milik pengembang. Buatlah akun admin pertama Anda secara manual atau gunakan command interaktif.

### Langkah 6: Atur Izin Folder (Permissions)
Web server membutuhkan hak akses ke folder `storage` dan `bootstrap/cache`:
```bash
sudo chown -R www-data:www-data /var/www/hutang-app
sudo find /var/www/hutang-app -type f -exec chmod 644 {} \;
sudo find /var/www/hutang-app -type d -exec chmod 755 {} \;
chmod -R 775 /var/www/hutang-app/storage
chmod -R 775 /var/www/hutang-app/bootstrap/cache
```

Jalankan juga perintah untuk membuat tautan direktori storage:
```bash
php artisan storage:link
```

### Langkah 7: Konfigurasi Nginx
Buat file konfigurasi server block Nginx baru:
```bash
sudo nano /etc/nginx/sites-available/nama-domain-anda.com
```
Masukkan konfigurasi berikut (sesuaikan nama domain dan versi PHP-FPM):
```nginx
server {
    listen 80;
    server_name nama-domain-anda.com www.nama-domain-anda.com;
    root /var/www/hutang-app/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-XSS-Protection "1; mode=block";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```
Aktifkan konfigurasi tersebut dan restart Nginx:
```bash
sudo ln -s /etc/nginx/sites-available/nama-domain-anda.com /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

### Langkah 8: Memasang SSL (HTTPS Gratis Let's Encrypt)
Gunakan Certbot untuk mengamankan website Anda dengan SSL gratis:
```bash
sudo apt install certbot python3-certbot-nginx -y
sudo certbot --nginx -d nama-domain-anda.com -d www.nama-domain-anda.com
```
Certbot akan otomatis memperbarui konfigurasi Nginx Anda ke HTTPS dan mengaktifkan pengalihan otomatis (*auto-redirect*) dari HTTP ke HTTPS.

### Langkah 9: Optimasi Laravel Production
Jalankan perintah ini di direktori project agar website berjalan sangat cepat:
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

---

## 🚀 Menjalankan Queue Worker (Supervisor)
Aplikasi ini menggunakan antrean tugas (*Queue*) untuk beberapa proses latar belakang. Di server production, buatlah worker agar antrean berjalan otomatis:

1. Install Supervisor:
   ```bash
   sudo apt install supervisor -y
   ```
2. Buat file config baru:
   ```bash
   sudo nano /etc/supervisor/conf.d/laravel-worker.conf
   ```
3. Isi dengan konfigurasi berikut:
   ```ini
   [program:laravel-worker]
   process_name=%(program_name)s_%(process_num)02d
   command=php /var/www/hutang-app/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
   autostart=true
   autorestart=true
   stopasgroup=true
   killasgroup=true
   user=www-data
   numprocs=2
   redirect_stderr=true
   stdout_logfile=/var/www/hutang-app/storage/logs/worker.log
   stopwaitsecs=3600
   ```
4. Aktifkan Supervisor Worker:
   ```bash
   sudo supervisorctl reread
   sudo supervisorctl update
   sudo supervisorctl start laravel-worker:*
   ```

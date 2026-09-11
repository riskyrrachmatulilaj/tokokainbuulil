# Panduan Integrasi WhatsApp Chatbot - Toko Kain Bu Ulil

Dokumen ini menjelaskan cara menghubungkan WhatsApp Gateway ke aplikasi Toko Kain Bu Ulil dan menggunakan chatbot interaktif untuk mengecek hutang/piutang pelanggan, hutang supplier, omset penjualan, status jatuh tempo, serta stok kain langsung melalui chat WhatsApp.

---

## 1. Daftar Perintah Chatbot

Chatbot didesain untuk mengenali kata kunci secara fleksibel (huruf besar/kecil tidak berpengaruh):

| Perintah | Contoh Chat | Keterangan |
| :--- | :--- | :--- |
| **Menu / Bantuan** | `menu`, `bantuan`, `help`, `halo` | Menampilkan panduan dan daftar perintah yang tersedia. |
| **Cek Piutang Pelanggan** | `hutang budi`<br>`piutang pak agus`<br>`cek hutang sinta` | Menampilkan total tagihan belum lunas pelanggan beserta rincian nomor faktur dan jatuh temponya. |
| **Cek Hutang Supplier** | `supplier maju textile`<br>`hutang supplier maju` | Menampilkan kewajiban hutang toko ke pihak supplier kain. |
| **Cek Penjualan / Omset** | `penjualan hari ini`<br>`penjualan kemarin`<br>`penjualan 2026-08-11`<br>`omset hari ini` | Menampilkan total omset, jumlah transaksi, total item kain terjual, dan rincian penerimaan (kas tunai, transfer bank, dan tempo). |
| **Cek Jatuh Tempo** | `jatuh tempo`<br>`cek jatuh tempo` | Menampilkan daftar debitur/pelanggan yang memiliki tagihan jatuh tempo atau menunggak. |
| **Cek Stok & Harga Kain** | `stok katun`<br>`stok rayon`<br>`produk crinkle` | Menampilkan harga satuan, jumlah stok kain, dan status keaktifan produk. |

---

## 2. Pengaturan Keamanan (Whitelist Nomor Admin)

Agar data keuangan toko tidak dapat diakses oleh publik atau orang luar, sistem dilengkapi verifikasi nomor pengirim.

Buka file `.env` di server atau komputer Anda, lalu tentukan nomor WhatsApp yang diizinkan (pisahkan dengan koma jika lebih dari satu):

```env
WHATSAPP_AUTHORIZED_NUMBERS=08123456789,08987654321
```

*Sistem secara otomatis menyesuaikan format nomor, baik yang diawali dengan `08...`, `628...`, maupun `+628...`.*

Jika `WHATSAPP_AUTHORIZED_NUMBERS` dikosongkan (default), sistem mengizinkan akses untuk mempermudah tahap pengujian awal.

---

## 3. Konfigurasi WhatsApp Gateway (Fonnte)

Implementasi bawaan menggunakan driver **Fonnte** (layanan gateway WhatsApp lokal populer di Indonesia).

### Langkah Menghubungkan Fonnte:
1. Buat akun di [https://fonnte.com](https://fonnte.com) dan login ke dashboard.
2. Tambahkan perangkat / scan QR code nomor WhatsApp yang ingin dijadikan bot toko.
3. Salin **API Token** dari dashboard Fonnte.
4. Buka file `.env` aplikasi Anda, lalu isi variabel berikut:
   ```env
   WHATSAPP_GATEWAY=fonnte
   WHATSAPP_API_TOKEN=TOKEN_FONNTE_ANDA
   WHATSAPP_API_URL=https://api.fonnte.com/send
   WHATSAPP_WEBHOOK_SECRET=rahasia123
   ```
5. Di menu **Webhook** pada dashboard Fonnte:
   - Isi URL Webhook dengan: `https://domain-anda.com/api/webhook/whatsapp`
   - Jika menggunakan secret token, tambahkan parameter: `https://domain-anda.com/api/webhook/whatsapp?secret=rahasia123`
   - Simpan pengaturan webhook di Fonnte.

---

## 4. Cara Uji Coba Simulasi di Komputer Lokal

Anda dapat menguji interaksi bot langsung dari terminal tanpa harus terhubung ke internet:

```bash
# Uji menu bantuan
php artisan wa:simulate "menu"

# Uji cek piutang pelanggan
php artisan wa:simulate "hutang agus"

# Uji laporan penjualan harian
php artisan wa:simulate "penjualan 2026-08-11"

# Uji cek tagihan jatuh tempo
php artisan wa:simulate "jatuh tempo"

# Uji menggunakan nomor pengirim tertentu
php artisan wa:simulate "menu" --sender=08123456789
```

---

## 5. Arsitektur File Modul WhatsApp

- [config/whatsapp.php](config/whatsapp.php): File konfigurasi gateway dan nomor terdaftar.
- [app/Services/WhatsApp/WhatsAppBotService.php](app/Services/WhatsApp/WhatsAppBotService.php): Mesin pemroses perintah chatbot, normalisasi telepon, dan perakit respon pesan.
- [app/Services/WhatsApp/Gateways/FonnteGateway.php](app/Services/WhatsApp/Gateways/FonnteGateway.php): Driver integrasi HTTP API dan parsing payload webhook.
- [app/Http/Controllers/WhatsAppWebhookController.php](app/Http/Controllers/WhatsAppWebhookController.php): Controller penerima webhook dari gateway.
- [routes/api.php](routes/api.php): Endpoint route `/api/webhook/whatsapp`.
- [app/Console/Commands/SimulateWhatsAppCommand.php](app/Console/Commands/SimulateWhatsAppCommand.php): Perintah CLI `php artisan wa:simulate`.
- [tests/Feature/WhatsAppBotTest.php](tests/Feature/WhatsAppBotTest.php): Rangkaian unit & feature testing.

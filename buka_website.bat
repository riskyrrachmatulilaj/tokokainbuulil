@echo off
title Menjalankan Hutang App...
echo =======================================================
echo        MENJALANKAN APLIKASI HUTANG (LOKAL)
echo =======================================================
echo.
echo  1. Memulai server lokal PHP (php artisan serve)...
start /b php artisan serve --port=8000
echo  2. Menunggu server siap...
timeout /t 3 >nul
echo  3. Membuka aplikasi di browser default...
start "" "http://127.0.0.1:8000/admin"
echo.
echo =======================================================
echo  APLIKASI SUDAH BERJALAN!
echo  Untuk mematikan aplikasi, cukup tutup jendela CMD ini.
echo =======================================================
echo.

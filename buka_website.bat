@echo off
title Hutang App - Local Server

echo =======================================================
echo              HUTANG APP - LOCAL SERVER
echo =======================================================
echo.

echo [1/3] Menjalankan Laravel...
start "Hutang App Server" /b php artisan serve --port=8000 --no-reload

echo [2/3] Menunggu server...
timeout /t 3 /nobreak >nul

echo [3/3] Membuka aplikasi...
start "" "http://127.0.0.1:8000/admin"

echo.
echo =======================================================
echo              HUTANG APP SUDAH BERJALAN
echo =======================================================
echo.
echo Browser: http://127.0.0.1:8000/admin
echo.
echo Tutup jendela ini untuk keluar.
echo.

pause
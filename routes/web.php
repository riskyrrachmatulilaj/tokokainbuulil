<?php

use Illuminate\Support\Facades\Route;
use App\Models\Sale;
use App\Services\SalePdfService;
use App\Services\SaleThermalService;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/nota/{sale}', function (Sale $sale) {
    return SalePdfService::notaInline($sale);
})->name('sales.public-nota');

Route::middleware(['auth'])->group(function () {
    // 1. Nota Standar A4
    Route::get('/sales/{sale}/nota', function (Sale $sale) {
        return SalePdfService::notaInline($sale);
    })->name('sales.nota');

    // 2. Nota Continuous Ringkas 1-Baris (Layout Sekarang, 9.5x14cm Hemat Kertas)
    Route::get('/sales/{sale}/thermal', function (Sale $sale) {
        return SaleThermalService::continuousCompactInline($sale);
    })->name('sales.thermal');

    Route::get('/sales/{sale}/continuous', function (Sale $sale) {
        return SaleThermalService::continuousCompactInline($sale);
    })->name('sales.continuous');

    // 3. Nota Continuous Detail 2-Baris (Layout Kedua, 9.5x14cm Detail Nama)
    Route::get('/sales/{sale}/continuous-detail', function (Sale $sale) {
        return SaleThermalService::continuousDetailInline($sale);
    })->name('sales.continuous-detail');

    // 4. Struk Thermal Roll 72mm (Layout Pertama / Roll POS Standar)
    Route::get('/sales/{sale}/thermal-roll', function (Sale $sale) {
        return SaleThermalService::thermalRollInline($sale);
    })->name('sales.thermal-roll');
});

<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

use App\Models\Sale;
use App\Services\SalePdfService;
use App\Services\SaleThermalService;

Route::middleware(['auth'])->group(function () {
    Route::get('/sales/{sale}/nota', function (Sale $sale) {
        return SalePdfService::notaInline($sale);
    })->name('sales.nota');

    Route::get('/sales/{sale}/thermal', function (Sale $sale) {
        return SaleThermalService::notaInline($sale);
    })->name('sales.thermal');
});

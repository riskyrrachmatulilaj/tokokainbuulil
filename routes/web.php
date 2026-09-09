<?php

use Illuminate\Support\Facades\Route;
use App\Models\Sale;
use App\Services\SalePdfService;
use App\Services\SaleThermalService;

Route::get('/', function () {
    return redirect('/admin');
});

Route::get('/nota/{sale}', function (Sale $sale) {
    return SalePdfService::notaInline($sale);
})->name('sales.public-nota');

Route::middleware(['auth'])->group(function () {
    // 1. Nota Standar A4
    Route::get('/sales/{sale}/nota', function (Sale $sale) {
        return SalePdfService::notaInline($sale);
    })->name('sales.nota');

    // 2. Nota Struk Thermal / Continuous dengan parameter layout (?layout=compact|detail|roll)
    Route::get('/sales/{sale}/thermal', function (Sale $sale) {
        $layout = request()->query('layout', 'compact');
        return match ($layout) {
            'roll', 'thermal' => SaleThermalService::thermalRollInline($sale),
            'detail', '2row' => SaleThermalService::continuousDetailInline($sale),
            default => SaleThermalService::continuousCompactInline($sale),
        };
    })->name('sales.thermal');

    // Rute alias URL langsung
    Route::get('/sales/{sale}/continuous', function (Sale $sale) {
        return SaleThermalService::continuousCompactInline($sale);
    })->name('sales.continuous');

    Route::get('/sales/{sale}/continuous-detail', function (Sale $sale) {
        return SaleThermalService::continuousDetailInline($sale);
    })->name('sales.continuous-detail');

    Route::get('/sales/{sale}/thermal-roll', function (Sale $sale) {
        return SaleThermalService::thermalRollInline($sale);
    })->name('sales.thermal-roll');
});

// Customer Facing Display Routes (Layar Pelanggan HP/Tablet/Monitor)
Route::get('/customer-display', [\App\Http\Controllers\CustomerDisplayController::class, 'index'])->name('customer-display');
Route::get('/display', [\App\Http\Controllers\CustomerDisplayController::class, 'index'])->name('customer-display.alias');
Route::get('/api/customer-display/state', [\App\Http\Controllers\CustomerDisplayController::class, 'state'])->name('customer-display.state');
Route::get('/api/customer-display/stream', [\App\Http\Controllers\CustomerDisplayController::class, 'stream'])->name('customer-display.stream');
Route::post('/api/customer-display/update', [\App\Http\Controllers\CustomerDisplayController::class, 'update'])->name('customer-display.update');


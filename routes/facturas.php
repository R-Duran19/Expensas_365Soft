<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FacturaPrincipalController;

Route::middleware(['auth', 'verified', 'role:admin'])->group(function () {
    
    // FACTURAS PRINCIPALES
    Route::get('periodos/{periodo}/facturas/create', [FacturaPrincipalController::class, 'create'])
        ->name('facturas-principales.create');
    Route::post('periodos/{periodo}/facturas', [FacturaPrincipalController::class, 'store'])
        ->name('facturas-principales.store');
    Route::get('facturas/{factura}/edit', [FacturaPrincipalController::class, 'edit'])
        ->name('facturas-principales.edit');
    Route::put('facturas/{factura}', [FacturaPrincipalController::class, 'update'])
        ->name('facturas-principales.update');
    Route::delete('facturas/{factura}', [FacturaPrincipalController::class, 'destroy'])
        ->name('facturas-principales.destroy');
});
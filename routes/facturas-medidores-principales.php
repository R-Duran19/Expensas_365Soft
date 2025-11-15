<?php

use App\Http\Controllers\FacturaMedidorPrincipalController;
use Illuminate\Support\Facades\Route;

// Rutas para la gestión de facturas de medidores principales
Route::middleware(['auth', 'verified'])->group(function () {

    // Vista principal (selector de períodos o lista)
    Route::get('/facturas-medidores-principales', [FacturaMedidorPrincipalController::class, 'index'])
        ->name('facturas-medidores-principales.index');

    // Vista para crear facturas de un período
    Route::get('/facturas-medidores-principales/create', [FacturaMedidorPrincipalController::class, 'create'])
        ->name('facturas-medidores-principales.create');

    // Guardar facturas
    Route::post('/facturas-medidores-principales', [FacturaMedidorPrincipalController::class, 'store'])
        ->name('facturas-medidores-principales.store');

    // Eliminar factura
    Route::delete('/facturas-medidores-principales/{facturaMedidorPrincipal}', [FacturaMedidorPrincipalController::class, 'destroy'])
        ->name('facturas-medidores-principales.destroy');

    // Obtener resumen (API)
    Route::get('/facturas-medidores-principales/resumen', [FacturaMedidorPrincipalController::class, 'getResumen'])
        ->name('facturas-medidores-principales.resumen');

});
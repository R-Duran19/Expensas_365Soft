<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ExpensaController;

Route::middleware(['auth', 'verified', 'role:admin'])->group(function () {
    
    // EXPENSAS - CRUD básico
    Route::get('expensas', [ExpensaController::class, 'index'])->name('expensas.index');
    Route::get('expensas/{expensa}', [ExpensaController::class, 'show'])->name('expensas.show');
    
    // Generación y gestión de expensas
    Route::post('periodos/{periodo}/generar-expensas', [ExpensaController::class, 'generar'])
        ->name('expensas.generar');
    Route::post('expensas/{expensa}/recalcular', [ExpensaController::class, 'recalcular'])
        ->name('expensas.recalcular');
    Route::post('expensas/{expensa}/pagar', [ExpensaController::class, 'registrarPago'])
        ->name('expensas.registrar-pago');
    
    // Reportes de expensas
    Route::get('expensas-pendientes', [ExpensaController::class, 'pendientes'])
        ->name('expensas.pendientes');
    Route::get('expensas-vencidas', [ExpensaController::class, 'vencidas'])
        ->name('expensas.vencidas');
    
    // Historial por propiedad
    Route::get('api/propiedades/{propiedad}/historial-expensas', [ExpensaController::class, 'historialPropiedad'])
        ->name('propiedades.historial-expensas');
});
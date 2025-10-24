<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PeriodoFacturacionController;

Route::middleware(['auth', 'verified', 'role:admin'])->group(function () {
    
    // PERÍODOS DE FACTURACIÓN
    Route::get('periodos', [PeriodoFacturacionController::class, 'index'])->name('periodos.index');
    Route::get('periodos/create', [PeriodoFacturacionController::class, 'create'])->name('periodos.create');
    Route::post('periodos', [PeriodoFacturacionController::class, 'store'])->name('periodos.store');
    Route::get('periodos/{periodo}', [PeriodoFacturacionController::class, 'show'])->name('periodos.show');
    Route::get('periodos/{periodo}/edit', [PeriodoFacturacionController::class, 'edit'])->name('periodos.edit');
    Route::put('periodos/{periodo}', [PeriodoFacturacionController::class, 'update'])->name('periodos.update');
    Route::delete('periodos/{periodo}', [PeriodoFacturacionController::class, 'destroy'])->name('periodos.destroy');
    
    // Acciones adicionales de períodos
    Route::post('periodos/{periodo}/cerrar', [PeriodoFacturacionController::class, 'cerrar'])
        ->name('periodos.cerrar');
    Route::post('periodos/{periodo}/reabrir', [PeriodoFacturacionController::class, 'reabrir'])
        ->name('periodos.reabrir');
});
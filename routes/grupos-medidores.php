<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GrupoMedidorController;

Route::middleware(['auth', 'verified', 'role:admin'])->group(function () {
    
    // GRUPOS DE MEDIDORES (Compartidos)
    Route::get('grupos-medidores', [GrupoMedidorController::class, 'index'])->name('grupos-medidores.index');
    Route::get('grupos-medidores/create', [GrupoMedidorController::class, 'create'])->name('grupos-medidores.create');
    Route::post('grupos-medidores', [GrupoMedidorController::class, 'store'])->name('grupos-medidores.store');
    Route::get('grupos-medidores/{grupoMedidor}', [GrupoMedidorController::class, 'show'])->name('grupos-medidores.show');
    Route::get('grupos-medidores/{grupoMedidor}/edit', [GrupoMedidorController::class, 'edit'])->name('grupos-medidores.edit');
    Route::put('grupos-medidores/{grupoMedidor}', [GrupoMedidorController::class, 'update'])->name('grupos-medidores.update');
    Route::delete('grupos-medidores/{grupoMedidor}', [GrupoMedidorController::class, 'destroy'])->name('grupos-medidores.destroy');
    
    // Acciones adicionales de grupos
    Route::post('grupos-medidores/{grupoMedidor}/toggle-activo', [GrupoMedidorController::class, 'toggleActivo'])
        ->name('grupos-medidores.toggle-activo');
});
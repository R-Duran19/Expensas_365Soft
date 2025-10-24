<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LecturaController;

Route::middleware(['auth', 'verified', 'role:admin'])->group(function () {
    
    // LECTURAS - CRUD básico
    Route::get('lecturas', [LecturaController::class, 'index'])->name('lecturas.index');
    Route::get('lecturas/create', [LecturaController::class, 'create'])->name('lecturas.create');
    Route::post('lecturas', [LecturaController::class, 'store'])->name('lecturas.store');
    Route::get('lecturas/{lectura}', [LecturaController::class, 'show'])->name('lecturas.show');
    Route::get('lecturas/{lectura}/edit', [LecturaController::class, 'edit'])->name('lecturas.edit');
    Route::put('lecturas/{lectura}', [LecturaController::class, 'update'])->name('lecturas.update');
    Route::delete('lecturas/{lectura}', [LecturaController::class, 'destroy'])->name('lecturas.destroy');
    
    // LECTURAS - Rutas adicionales
    Route::post('lecturas/masivo', [LecturaController::class, 'storeMasivo'])
        ->name('lecturas.store-masivo');
    Route::get('api/medidores/{medidor}/ultima-lectura', [LecturaController::class, 'getUltimaLectura'])
        ->name('medidores.ultima-lectura');
    Route::get('api/lecturas/estadisticas', [LecturaController::class, 'estadisticas'])
        ->name('lecturas.estadisticas');
});
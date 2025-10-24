<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MedidorController;
use App\Http\Controllers\LecturaController;

Route::middleware(['auth', 'verified', 'role:admin'])->group(function () {

    // Medidores
    Route::get('medidores', [MedidorController::class, 'index'])->name('medidores.index');
    Route::post('medidores', [MedidorController::class, 'store'])->name('medidores.store');
    Route::put('medidores/{medidor}', [MedidorController::class, 'update'])->name('medidores.update');
    Route::delete('medidores/{medidor}', [MedidorController::class, 'destroy'])->name('medidores.destroy');
    // routes/web.php
    Route::get('/medidores/propiedades/buscar', [MedidorController::class, 'buscarPropiedades'])
        ->name('medidores.buscar-propiedades');
    // Lecturas
    Route::get('lecturas', [LecturaController::class, 'index'])->name('lecturas.index');
    Route::get('lecturas/create', [LecturaController::class, 'create'])->name('lecturas.create');
    Route::post('lecturas', [LecturaController::class, 'store'])->name('lecturas.store');
    Route::get('lecturas/{lectura}', [LecturaController::class, 'show'])->name('lecturas.show');
    Route::delete('lecturas/{lectura}', [LecturaController::class, 'destroy'])->name('lecturas.destroy');

    // API para obtener última lectura
    Route::get('api/medidores/{medidor}/ultima-lectura', [LecturaController::class, 'getUltimaLectura']);
    Route::get('api/medidores/activos', [MedidorController::class, 'getActivos'])
    ->name('api.medidores.activos');
});

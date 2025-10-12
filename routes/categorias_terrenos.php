<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CategoriaTerrenoController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('categorias_terrenos', [CategoriaTerrenoController::class, 'index'])->name('categorias_terrenos.index');
    Route::post('categorias_terrenos', [CategoriaTerrenoController::class, 'store'])->name('categorias_terrenos.store');
    Route::put('categorias_terrenos/{id}', [CategoriaTerrenoController::class, 'update'])->name('categorias_terrenos.update');
    Route::patch('categorias_terrenos/desactivar/{id}', [CategoriaTerrenoController::class, 'desactivar'])->name('categorias_terrenos.desactivar');
    Route::patch('categorias_terrenos/activar/{id}', [CategoriaTerrenoController::class, 'activar'])->name('categorias_terrenos.activar');
});
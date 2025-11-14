<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PropietarioController;
use App\Http\Controllers\InquilinoController;

Route::middleware(['auth', 'verified', 'role:admin'])->group(function () {
    
    // Propietarios - Lista principal
    Route::get('propietarios', [PropietarioController::class, 'index'])->name('propietarios.index');
    
    Route::get('propietarios/propiedades-disponibles', [PropietarioController::class, 'getPropiedadesDisponibles'])->name('propietarios.propiedades-disponibles');

    Route::post('propietarios', [PropietarioController::class, 'store'])->name('propietarios.store');
    Route::put('propietarios/{propietario}', [PropietarioController::class, 'update'])->name('propietarios.update');
    Route::delete('propietarios/{propietario}', [PropietarioController::class, 'destroy'])->name('propietarios.destroy');
    
    Route::get('propietarios/{propietario}/propiedades', [PropietarioController::class, 'getPropiedades'])->name('propietarios.propiedades');

    Route::get('propietarios/{propietario}', [PropietarioController::class, 'show'])->name('propietarios.show'); // Para edición

    Route::post('propietarios/{propietario}/asignar-propiedad', [PropietarioController::class, 'asignarPropiedad'])->name('propietarios.asignar-propiedad');
    Route::delete('propietarios/{propietario}/desasignar-propiedad/{propiedad}', [PropietarioController::class, 'desasignarPropiedad'])->name('propietarios.desasignar-propiedad');

    // Inquilinos
    Route::post('inquilinos', [InquilinoController::class, 'store'])->name('inquilinos.store');
    Route::put('inquilinos/{inquilino}', [InquilinoController::class, 'update'])->name('inquilinos.update');
    Route::delete('inquilinos/{inquilino}', [InquilinoController::class, 'destroy'])->name('inquilinos.destroy');
    Route::get('inquilinos/{inquilino}', [InquilinoController::class, 'show'])->name('inquilinos.show');
    Route::post('inquilinos/{inquilino}/asignar-propiedad', [InquilinoController::class, 'asignarPropiedad'])->name('inquilinos.asignar-propiedad');
    Route::delete('inquilinos/{inquilino}/desasignar-propiedad/{propiedad}', [InquilinoController::class, 'desasignarPropiedad'])->name('inquilinos.desasignar-propiedad');
});
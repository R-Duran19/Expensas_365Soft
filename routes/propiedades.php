<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PropietarioController;

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
});
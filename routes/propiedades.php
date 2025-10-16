<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PropietarioController;

Route::middleware(['auth', 'verified', 'role:admin'])->group(function () {
    
    // Propietarios
    Route::get('propietarios', [PropietarioController::class, 'index'])->name('propietarios.index');
    Route::get('propietarios/{propietario}', [PropietarioController::class, 'show'])->name('propietarios.show');
    Route::post('propietarios', [PropietarioController::class, 'store'])->name('propietarios.store');
    Route::put('propietarios/{propietario}', [PropietarioController::class, 'update'])->name('propietarios.update');
    Route::delete('propietarios/{propietario}', [PropietarioController::class, 'destroy'])->name('propietarios.destroy');
    
    // Asignar/Desasignar propiedades
    Route::post('propietarios/{propietario}/asignar-propiedad', [PropietarioController::class, 'asignarPropiedad'])->name('propietarios.asignar-propiedad');
    Route::delete('propietarios/{propietario}/desasignar-propiedad/{propiedad}', [PropietarioController::class, 'desasignarPropiedad'])->name('propietarios.desasignar-propiedad');
});
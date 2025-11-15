<?php

use App\Http\Controllers\PropertyExpenseController;
use Illuminate\Support\Facades\Route;

// Rutas para la gestión de expensas de propiedades
Route::middleware(['auth', 'verified'])->group(function () {

    // Vista para generar expensas
    Route::get('/property-expenses/create', [PropertyExpenseController::class, 'create'])
        ->name('property-expenses.create');

    // Generar expensas para un período
    Route::post('/property-expenses/generate', [PropertyExpenseController::class, 'generateForPeriod'])
        ->name('property-expenses.generate');

    // Calcular expensa preview
    Route::post('/property-expenses/calculate', [PropertyExpenseController::class, 'calculate'])
        ->name('property-expenses.calculate');

    // Listar expensas de un período
    Route::get('/property-expenses', [PropertyExpenseController::class, 'index'])
        ->name('property-expenses.index');

    // Ver detalle de una expensa
    Route::get('/property-expenses/{propertyExpense}', [PropertyExpenseController::class, 'show'])
        ->name('property-expenses.show');

    // Actualizar expensa
    Route::put('/property-expenses/{propertyExpense}', [PropertyExpenseController::class, 'update'])
        ->name('property-expenses.update');

    // Eliminar expensa
    Route::delete('/property-expenses/{propertyExpense}', [PropertyExpenseController::class, 'destroy'])
        ->name('property-expenses.destroy');

});
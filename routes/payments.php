<?php

use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

// Rutas para gestión de pagos
Route::middleware('auth')->group(function () {

    // Listado y creación de pagos
    Route::get('/pagos', [PaymentController::class, 'index'])->name('payments.index');
    Route::get('/pagos/seleccionar-propietario', [PaymentController::class, 'selectOwner'])->name('payments.select.owner');
    Route::get('/pagos/crear/{propietarioId?}', [PaymentController::class, 'create'])->name('payments.create');
    Route::post('/pagos', [PaymentController::class, 'store'])->name('payments.store');
    Route::get('/pagos/{payment}', [PaymentController::class, 'show'])->name('payments.show');

    // API endpoints para funcionalidades AJAX
    Route::prefix('api/pagos')->group(function () {
        Route::get('/propietario/{propietario_id}/deudas', [PaymentController::class, 'getOwnerDebts'])
            ->name('api.payments.owner.debts');

        Route::get('/propietario/{propietario_id}/propiedades', [PaymentController::class, 'getOwnerProperties'])
            ->name('api.payments.owner.properties');

        Route::get('/propietario/{propietario_id}/expensa-actual', [PaymentController::class, 'getOwnerCurrentExpense'])
            ->name('api.payments.owner.current_expense');

        Route::get('/propietarios-con-expensas/{periodId}', [PaymentController::class, 'getOwnersWithExpenses'])
            ->name('api.payments.owners.with.expenses');

        Route::post('/preview-imputacion', [PaymentController::class, 'previewAllocation'])
            ->name('api.payments.preview.allocation');

        Route::post('/{payment}/anular', [PaymentController::class, 'cancel'])
            ->name('api.payments.cancel');
    });

    // API para integraciones y estadísticas
    Route::prefix('api/payments')->group(function () {
        Route::get('/', [PaymentController::class, 'apiIndex'])
            ->name('api.payments.index');

        Route::get('/stats', [PaymentController::class, 'stats'])
            ->name('api.payments.stats');
    });
});
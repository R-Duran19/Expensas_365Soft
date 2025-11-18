<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ExpensePeriodController;

Route::middleware(['auth', 'verified', 'role:admin'])->group(function () {
    
    // PERÍODOS DE EXPENSAS
    Route::get('expense-periods', [ExpensePeriodController::class, 'index'])
        ->name('expense-periods.index');
    Route::post('expense-periods', [ExpensePeriodController::class, 'store'])
        ->name('expense-periods.store');
    Route::get('expense-periods/{expensePeriod}', [ExpensePeriodController::class, 'show'])
        ->name('expense-periods.show');
    Route::post('expense-periods/{expensePeriod}/close', [ExpensePeriodController::class, 'close'])
        ->name('expense-periods.close');
    Route::get('expense-periods/{expensePeriod}/receipts', [ExpensePeriodController::class, 'receipts'])
        ->name('expense-periods.receipts');
});


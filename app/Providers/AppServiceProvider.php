<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\CalculoExpensasService;
use App\Services\ExpenseCalculatorService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(CalculoExpensasService::class);
        $this->app->singleton(ExpenseCalculatorService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}

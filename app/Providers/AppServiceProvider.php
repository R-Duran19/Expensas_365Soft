<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\CalculoExpensasService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(CalculoExpensasService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}

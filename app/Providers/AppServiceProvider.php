<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\KardexServices;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind('kardex', function ($app) {
            return new KardexServices();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}

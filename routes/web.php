<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TenantController;
use App\Http\Controllers\PosController;

foreach (config('tenancy.central_domains') as $domain) {
    Route::domain($domain)->group(function () {
        // SPA POS: ruta base y comodín para deep links
        Route::get('/pos/{posSession}', [PosController::class, '__invoke'])
            ->middleware(['auth'])
            ->name('pos.show');

        Route::get('/pos/{posSession}/{any?}', [PosController::class, '__invoke'])
            ->where('any', '.*')
            ->middleware(['auth'])
            ->name('pos.any');

        Route::redirect('/', '/admin');

        Route::middleware([
            'auth:sanctum',
            config('jetstream.auth_session'),
            'verified',
        ])->group(function () {
            Route::get('/dashboard', function () {
                return view('dashboard');
            })->name('dashboard');

            Route::resource('tenants', TenantController::class)->except(['show']);

            Route::get('/wireui-test', function () {
                return view('wireui-test');
            })->name('wireui.test');
        });
    });
}

<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\PosController;

Route::get('/pos', PosController::class);

Route::redirect('/', '/admin');

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
    
    Route::get('/wireui-test', function () {
        return view('wireui-test');
    })->name('wireui.test');
});

<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\PosController;
use App\Http\Controllers\Admin\PurchaseOrderController;
use App\Http\Controllers\Admin\PurchaseController;
use App\Http\Controllers\Admin\SaleController;
use App\Http\Controllers\Admin\QuoteController;

// SPA POS: ruta base y comodín para deep links
Route::get('/pos/{posSession}', [PosController::class, '__invoke'])
    ->middleware(['auth'])
    ->name('pos.show');

Route::get('/pos/{posSession}/{any?}', [PosController::class, '__invoke'])
    ->where('any', '.*')
    ->middleware(['auth'])
    ->name('pos.any');

Route::redirect('/', '/admin');

Route::get('public/purchases-orders/{purchaseOrder}/pdf/view', [PurchaseOrderController::class, 'publicPdfView'])
    ->name('public.purchases-orders.pdf.view')
    ->middleware('signed');

Route::get('public/purchases/{purchase}/pdf/view', [PurchaseController::class, 'publicPdfView'])
    ->name('public.purchases.pdf.view')
    ->middleware('signed');

Route::get('public/sales/{sale}/pdf/view', [SaleController::class, 'publicPdfView'])
    ->name('public.sales.pdf.view')
    ->middleware('signed');

Route::get('public/quotes/{quote}/pdf/view', [QuoteController::class, 'publicPdfView'])
    ->name('public.quotes.pdf.view')
    ->middleware('signed');

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

require __DIR__ . '/web-new.php';
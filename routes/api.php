<?php

use App\Http\Controllers\Api\PurchaseOrderController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\WarehouseController;
use App\Http\Controllers\Api\VariantController;
use App\Http\Controllers\Api\QuoteController;
use App\Http\Controllers\Api\ReasonController;
use App\Http\Controllers\Api\AttributeController;
use App\Http\Controllers\Api\AttributeValueController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\PosSessionController;
use App\Http\Controllers\Api\PaymentMethodController;
use Illuminate\Support\Facades\Route;


Route::apiResource('categories', CategoryController::class);

Route::post('/suppliers', [SupplierController::class, 'index'])->name('api.suppliers.index');

Route::post('/customers', [CustomerController::class, 'index'])->name('api.customers.index');

Route::post('/warehouses', [WarehouseController::class, 'index'])->name('api.warehouse.index');

Route::post('/product', [VariantController::class, 'index'])->name('api.product.index');

Route::post('/product-pos', [VariantController::class, 'getVariantsPos'])->name('api.product.getVariantsPos');

Route::post('/purchase-orders', [PurchaseOrderController::class, 'index'])->name('api.purchase-orders.index');

Route::post('/quotes', [QuoteController::class, 'index'])->name('api.quotes.index');

Route::post('/reasons', [ReasonController::class, 'index'])->name('api.reasons.index');

Route::post('/attributes', [AttributeController::class, 'index'])->name('api.attributes.index');

Route::post('/attribute-values/{attributeId}', [AttributeValueController::class, 'index'])->name('api.attribute-values.show');

// Rutas de sesiones POS (protegidas por token si está disponible)
Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/pos-sessions/open', [PosSessionController::class, 'open'])->name('api.pos-sessions.open');
    Route::get('/pos-sessions/{id}/bootstrap', [PosSessionController::class, 'bootstrap'])->name('api.pos-sessions.bootstrap');
    Route::post('/pos-sessions/{id}/opening-balance', [PosSessionController::class, 'setOpeningBalance'])->name('api.pos-sessions.opening-balance');
    Route::post('/pos-sessions/{id}/sync', [PosSessionController::class, 'sync'])->name('api.pos-sessions.sync');
    Route::post('/pos-sessions/{id}/close', [PosSessionController::class, 'close'])->name('api.pos-sessions.close');
    Route::get('/pos-sessions/{id}/summary', [PosSessionController::class, 'summary'])->name('api.pos-sessions.summary');
    Route::get('/payment-methods', [PaymentMethodController::class, 'index'])->name('api.payment-methods.index');
});

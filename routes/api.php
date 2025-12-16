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
use App\Http\Controllers\Api\IdentityController;
use App\Http\Controllers\Api\CustomerLookupController;
use App\Http\Controllers\Api\LoyaltyPosController;
use App\Http\Controllers\Api\UbigeoController;
use Illuminate\Support\Facades\Route;

Route::apiResource('categories', CategoryController::class);

Route::post('categories/search', [CategoryController::class, 'search']);

Route::post('/suppliers', [SupplierController::class, 'index'])->name('api.suppliers.index');

Route::post('/customers', [CustomerController::class, 'index'])->name('api.customers.index');

Route::post('/customers/store', [CustomerController::class, 'store'])->name('api.customers.store');

Route::post('/customers/lookup', [CustomerLookupController::class, 'lookup'])->name('api.customers.lookup');

Route::post('/warehouses', [WarehouseController::class, 'index'])->name('api.warehouse.index');

Route::post('/product', [VariantController::class, 'index'])->name('api.product.index');

Route::post('/product/search', [VariantController::class, 'search'])->name('api.product.search');

Route::post('/product-pos', [VariantController::class, 'getVariantsPos'])->name('api.product.getVariantsPos');

Route::post('/purchase-orders', [PurchaseOrderController::class, 'index'])->name('api.purchase-orders.index');

Route::post('/quotes', [QuoteController::class, 'index'])->name('api.quotes.index');

Route::post('/reasons', [ReasonController::class, 'index'])->name('api.reasons.index');

Route::post('/attributes', [AttributeController::class, 'index'])->name('api.attributes.index');

Route::post('/attribute-values/{attributeId}', [AttributeValueController::class, 'index'])->name('api.attribute-values.show');

Route::get('/identities', [IdentityController::class, 'index'])->name('api.identities.index');

// Ubigeo API
Route::get('/ubigeo/departments', [UbigeoController::class, 'departments'])->name('api.ubigeo.departments');
Route::get('/ubigeo/provinces', [UbigeoController::class, 'provinces'])->name('api.ubigeo.provinces');
Route::get('/ubigeo/districts', [UbigeoController::class, 'districts'])->name('api.ubigeo.districts');

// Rutas de sesiones POS (protegidas por token si está disponible)
Route::middleware(['auth:sanctum'])->group(function () {
    // Lealtad (config y cuenta)
    Route::get('/loyalty/config', [LoyaltyPosController::class, 'config'])->name('api.loyalty.config');
    Route::get('/loyalty/account/{customer}', [LoyaltyPosController::class, 'account'])->name('api.loyalty.account');

    // POS
    Route::post('/pos-sessions/open', [PosSessionController::class, 'open'])->name('api.pos-sessions.open');
    Route::get('/pos-sessions/{id}/bootstrap', [PosSessionController::class, 'bootstrap'])->name('api.pos-sessions.bootstrap');
    Route::post('/pos-sessions/{id}/opening-balance', [PosSessionController::class, 'setOpeningBalance'])->name('api.pos-sessions.opening-balance');
    Route::post('/pos-sessions/{id}/sync', [PosSessionController::class, 'sync'])->name('api.pos-sessions.sync');
    Route::post('/pos-sessions/{id}/close', [PosSessionController::class, 'close'])->name('api.pos-sessions.close');
    Route::get('/pos-sessions/{id}/summary', [PosSessionController::class, 'summary'])->name('api.pos-sessions.summary');
    Route::get('/payment-methods', [PaymentMethodController::class, 'index'])->name('api.payment-methods.index');
});

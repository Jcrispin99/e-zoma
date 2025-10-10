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
use Illuminate\Support\Facades\Route;


Route::post('/suppliers', [SupplierController::class, 'index'])->name('api.suppliers.index');

Route::post('/customers', [CustomerController::class, 'index'])->name('api.customers.index');

Route::post('/warehouses', [WarehouseController::class, 'index'])->name('api.warehouse.index');

Route::post('/product', [VariantController::class, 'index'])->name('api.product.index');

Route::post('/purchase-orders', [PurchaseOrderController::class, 'index'])->name('api.purchase-orders.index');

Route::post('/quotes', [QuoteController::class, 'index'])->name('api.quotes.index');

Route::post('/reasons', [ReasonController::class, 'index'])->name('api.reasons.index');

Route::post('/attributes', [AttributeController::class, 'index'])->name('api.attributes.index');

Route::post('/attribute-values/{attributeId}', [AttributeValueController::class, 'index'])->name('api.attribute-values.show');

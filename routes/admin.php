<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\VariantController;
use App\Http\Controllers\Admin\ImageController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\SupplierController;
use App\Http\Controllers\Admin\WarehouseController;
use App\Http\Controllers\Admin\PurchaseOrderController;
use App\Http\Controllers\Admin\PurchaseController;

// Dashboard

Route::get('/', function () {
    return view('admin.dashboard');
})->name('dashboard');
// inventario
Route::resource('categories', CategoryController::class)->except(['show']);
Route::resource('products', ProductController::class)->except(['show']);
Route::resource('variants', VariantController::class)->except(['show']);
Route::resource('warehouses', WarehouseController::class)->except(['show']);

// compras
Route::resource('suppliers', SupplierController::class)->except(['show']);
Route::resource('purchases-orders', PurchaseOrderController::class)->only(['index', 'create']);
Route::resource('purchases', PurchaseController::class)->only(['index', 'create']);

//ventas
Route::resource('customers', CustomerController::class)->except(['show']);

route::post('variants/{variant}/dropzone', [VariantController::class, 'dropzone'])->name('variants.dropzone');
route::post('products/{product}/dropzone', [ProductController::class, 'dropzone'])->name('products.dropzone');

Route::delete('images/{image}', [ImageController::class, 'destroy'])->name('images.destroy');

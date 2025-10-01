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
use App\Http\Controllers\Admin\QuoteController;
use App\Http\Controllers\Admin\SaleController;
use App\Http\Controllers\Admin\MovementController;
use App\Http\Controllers\Admin\TransferController;
use App\Http\Controllers\Admin\AttributeController;


// Dashboard

Route::get('/', function () {
    return view('admin.dashboard');
})->name('dashboard');
// inventario
Route::resource('categories', CategoryController::class)->except(['show']);
Route::resource('products', ProductController::class)->except(['show']);
Route::resource('variants', VariantController::class)->except(['show', 'create', 'store']);
Route::get('variants/{variant}/kardex', [VariantController::class, 'kardex'])->name('variants.kardex');
Route::resource('warehouses', WarehouseController::class)->except(['show']);
Route::resource('attributes', AttributeController::class)->except(['show']);

// compras
Route::resource('suppliers', SupplierController::class)->except(['show']);
Route::resource('purchases-orders', PurchaseOrderController::class)->only(['index', 'create']);
Route::get('purchases-orders/{purchaseOrder}/pdf', [PurchaseOrderController::class, 'pdf'])->name('purchases-orders.pdf');

Route::resource('purchases', PurchaseController::class)->only(['index', 'create']);
Route::get('purchases/{purchase}/pdf', [PurchaseController::class, 'pdf'])->name('purchases.pdf');

//ventas
Route::resource('customers', CustomerController::class)->except(['show']);

Route::resource('quotes', QuoteController::class)->only(['index', 'create']);
Route::get('quotes/{quote}/pdf', [QuoteController::class, 'pdf'])->name('quotes.pdf');

Route::resource('sales', SaleController::class)->only(['index', 'create']);
Route::get('sales/{sale}/pdf', [SaleController::class, 'pdf'])->name('sales.pdf');

//movimientos
Route::resource('movements', MovementController::class)->only(['index', 'create']);
Route::get('movements/{movement}/pdf', [MovementController::class, 'pdf'])->name('movements.pdf');

Route::resource('transfers', TransferController::class)->only(['index', 'create']);
Route::get('transfers/{transfer}/pdf', [TransferController::class, 'pdf'])->name('transfers.pdf');



// Imagenes
route::post('variants/{variant}/dropzone', [VariantController::class, 'dropzone'])->name('variants.dropzone');
route::post('products/{product}/dropzone', [ProductController::class, 'dropzone'])->name('products.dropzone');

Route::delete('images/{image}', [ImageController::class, 'destroy'])->name('images.destroy');

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
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\CompanyController;
use App\Http\Controllers\Admin\PosConfigController;
use App\Http\Controllers\Admin\SequenceController;
use App\Http\Controllers\Admin\JournalController;

// Dashboard

Route::get('/', function () {
    return view('admin.dashboard');
})->name('dashboard');

// Usuario
Route::resource('users', UserController::class)->except(['show']);

// inventario
Route::resource('categories', CategoryController::class)->except(['show']);
Route::resource('products', ProductController::class)->except(['show']);
Route::get('products/import', [ProductController::class, 'import'])->name('products.import');

Route::resource('variants', VariantController::class)->except(['show', 'create', 'store']);
Route::get('variants/{variant}/kardex', [VariantController::class, 'kardex'])->name('variants.kardex');
Route::resource('warehouses', WarehouseController::class)->except(['show']);
Route::resource('attributes', AttributeController::class)->except(['show']);

// compras
Route::resource('suppliers', SupplierController::class)->except(['show']);

Route::get('purchases-orders', [PurchaseOrderController::class, 'index'])->name('purchases-orders.index');
Route::get('purchases-orders/create', [PurchaseOrderController::class, 'create'])->name('purchases-orders.create');
Route::get('purchases-orders/{purchaseOrder}/edit', \App\Livewire\Admin\PurchaseOrderEdit::class)->name('purchases-orders.edit');

Route::get('purchases-orders/{purchaseOrder}/pdf', [PurchaseOrderController::class, 'pdf'])->name('purchases-orders.pdf');

Route::get('purchases', [PurchaseController::class, 'index'])->name('purchases.index');
Route::get('purchases/create/{purchase_order_id?}', \App\Livewire\Admin\PurchaseCreate::class)->name('purchases.create');
Route::get('purchases/{purchase}/edit', \App\Livewire\Admin\PurchaseEdit::class)->name('purchases.edit');
Route::get('purchases/{purchase}/pdf', [PurchaseController::class, 'pdf'])->name('purchases.pdf');

//ventas
Route::resource('customers', CustomerController::class)->except(['show']);

Route::resource('quotes', QuoteController::class)->only(['index', 'create']);
Route::get('quotes/{quote}/pdf', [QuoteController::class, 'pdf'])->name('quotes.pdf');
Route::get('quotes/{quote}/edit', \App\Livewire\Admin\QuoteEdit::class)->name('quotes.edit');

Route::resource('sales', SaleController::class)->only(['index', 'create']);
Route::get('sales/{sale}/edit', \App\Livewire\Admin\SaleEdit::class)->name('sales.edit');
Route::get('sales/{sale}/pdf', [SaleController::class, 'pdf'])->name('sales.pdf');

//movimientos
Route::resource('movements', MovementController::class)->only(['index', 'create']);
Route::get('movements/{movement}/pdf', [MovementController::class, 'pdf'])->name('movements.pdf');

Route::resource('transfers', TransferController::class)->only(['index', 'create']);
Route::get('transfers/{transfer}/pdf', [TransferController::class, 'pdf'])->name('transfers.pdf');

// POS
Route::resource('posconfig', PosConfigController::class)->except(['show'])->parameters([
    'posconfig' => 'posConfig'
]);

// Diarios
Route::resource('journals', JournalController::class)->except(['show']);
Route::resource('sequences', SequenceController::class)->except(['show']);

// Imagenes
route::post('variants/{variant}/dropzone', [VariantController::class, 'dropzone'])->name('variants.dropzone');
route::post('products/{product}/dropzone', [ProductController::class, 'dropzone'])->name('products.dropzone');

Route::delete('images/{image}', [ImageController::class, 'destroy'])->name('images.destroy');

// Company
Route::resource('companies', CompanyController::class)->except(['show']);

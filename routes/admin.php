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
use App\Http\Controllers\Admin\SunatConnectionController;
use App\Http\Controllers\Admin\LoyaltyProgramController;
use App\Http\Controllers\Admin\Companycontroller as CompanycontrollerLower;

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

Route::resource('purchases-orders', PurchaseOrderController::class)->parameters(['purchases-orders' => 'purchaseOrder'])->only(['index', 'create', 'edit']);
Route::get('purchases-orders/{purchaseOrder}/pdf', [PurchaseOrderController::class, 'pdf'])->name('purchases-orders.pdf');
Route::get('purchases-orders/{purchaseOrder}/pdf/view', [PurchaseOrderController::class, 'pdfView'])->name('purchases-orders.pdf.view');

Route::resource('purchases', PurchaseController::class)->only(['index', 'create', 'edit']);
Route::get('purchases/{purchase}/pdf', [PurchaseController::class, 'pdf'])->name('purchases.pdf');
Route::get('purchases/{purchase}/pdf/view', [PurchaseController::class, 'pdfView'])->name('purchases.pdf.view');

//ventas
Route::resource('customers', CustomerController::class)->except(['show']);

Route::resource('quotes', QuoteController::class)->only(['index', 'create', 'edit']);
Route::get('quotes/{quote}/pdf', [QuoteController::class, 'pdf'])->name('quotes.pdf');
Route::get('quotes/{quote}/pdf/view', [QuoteController::class, 'pdfView'])->name('quotes.pdf.view');

Route::resource('sales', SaleController::class)->only(['index', 'create', 'edit']);
Route::get('sales/{sale}/pdf', [SaleController::class, 'pdf'])->name('sales.pdf');
Route::get('sales/{sale}/pdf/view', [SaleController::class, 'pdfView'])->name('sales.pdf.view');

//movimientos
Route::resource('movements', MovementController::class)->only(['index', 'create']);
Route::get('movements/{movement}/pdf', [MovementController::class, 'pdf'])->name('movements.pdf');

Route::resource('transfers', TransferController::class)->only(['index', 'create']);
Route::get('transfers/{transfer}/pdf', [TransferController::class, 'pdf'])->name('transfers.pdf');

// QR Labels
Route::get('qr/labels/{type}/{id}', \App\Livewire\Admin\Qr\QrGenerator::class)->name('qr.labels');

// POS
Route::resource('posconfig', PosConfigController::class)->except(['show'])->parameters([
    'posconfig' => 'posConfig'
]);

Route::get('posconfig/{posConfig}/sessions', function (\App\Models\PosConfig $posConfig) {
    return view('admin.possessions.index', compact('posConfig'));
})->name('posconfig.sessions');

Route::get('possessions/{posSession}', function (\App\Models\PosSession $posSession) {
    return view('admin.possessions.show', compact('posSession'));
})->name('possessions.show');

// Diarios
Route::resource('journals', JournalController::class)->except(['show']);
Route::resource('sequences', SequenceController::class)->except(['show']);

// Imagenes
route::post('variants/{variant}/dropzone', [VariantController::class, 'dropzone'])->name('variants.dropzone');
route::post('products/{product}/dropzone', [ProductController::class, 'dropzone'])->name('products.dropzone');

Route::delete('images/{image}', [ImageController::class, 'destroy'])->name('images.destroy');

// Company
Route::resource('companies', CompanyController::class)->except(['show']);
// Ubigeo endpoints para selects dependientes
Route::get('ubigeo/provinces', [CompanycontrollerLower::class, 'ubigeoProvinces'])->name('ubigeo.provinces');
Route::get('ubigeo/districts', [CompanycontrollerLower::class, 'ubigeoDistricts'])->name('ubigeo.districts');
Route::get('sunat-connections', [SunatConnectionController::class, 'index'])->name('sunat-connections.index');
Route::post('sunat-connections', [SunatConnectionController::class, 'store'])->name('sunat-connections.store');
Route::put('sunat-connections', [SunatConnectionController::class, 'update'])->name('sunat-connections.update');

// Loyalty
Route::resource('loyalty-programs', LoyaltyProgramController::class)
    ->except(['show'])
    ->parameters(['loyalty-programs' => 'program']);

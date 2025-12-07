<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\VariantController;
use App\Http\Controllers\Admin\WarehouseController;

Route::get('/web', function () {
    return Inertia::render('MainApps');
})->name('web');

// INVENTARIO

// PRODUCTOS
Route::get('/finanzas/inventario', [ProductController::class, 'inventoryDashboard'])->name('inventory.index');
Route::get('/finanzas/inventario/productos', [ProductController::class, 'indexWeb'])->name('inventory.products.index');
Route::get('/finanzas/inventario/productos/crear', [ProductController::class, 'createWeb'])->name('inventory.products.create');
Route::get('/finanzas/inventario/productos/{product}/editar', [ProductController::class, 'editWeb'])->name('inventory.products.edit');
Route::post('/finanzas/inventario/productos', [ProductController::class, 'storeWeb'])->name('inventory.products.store');
Route::put('/finanzas/inventario/productos/{product}', [ProductController::class, 'updateWeb'])->name('inventory.products.update');
Route::get('/finanzas/inventario/productos/{product}/qr', [ProductController::class, 'qrWeb'])->name('inventory.products.qr');
Route::delete('/finanzas/inventario/productos/{product}', [ProductController::class, 'destroyWeb'])->name('inventory.products.destroy');
Route::get('/finanzas/inventario/productos/{product}/kardex', [ProductController::class, 'kardexWeb'])->name('inventory.products.kardex');
Route::post('/finanzas/inventario/productos/mass-destroy', [ProductController::class, 'massDestroyWeb'])->name('inventory.products.mass_destroy');
Route::match(['get', 'post'], '/finanzas/inventario/productos/qr-masivo', [ProductController::class, 'massQrWeb'])->name('inventory.products.mass_qr');

// VARIANTES
Route::get('/finanzas/inventario/variantes', [VariantController::class, 'indexWeb'])->name('inventory.variants.index');
Route::get('/finanzas/inventario/variantes/{variant}/editar', [VariantController::class, 'editWeb'])->name('inventory.variants.edit');
Route::put('/finanzas/inventario/variantes/{variant}', [VariantController::class, 'updateWeb'])->name('inventory.variants.update');
Route::get('/finanzas/inventario/variantes/{variant}/kardex', [VariantController::class, 'kardexWeb'])->name('inventory.variants.kardex');
Route::get('/finanzas/inventario/variantes/{variant}/qr', [VariantController::class, 'qrWeb'])->name('inventory.variants.qr');
Route::post('/finanzas/inventario/variantes/mass-destroy', [VariantController::class, 'massDestroy'])->name('inventory.variants.mass_destroy');
Route::match(['get', 'post'], '/finanzas/inventario/variantes/qr-masivo', [VariantController::class, 'massQrWeb'])->name('inventory.variants.mass_qr');

// ALMACENES
Route::get('/finanzas/inventario/almacenes', [WarehouseController::class, 'indexWeb'])->name('inventory.warehouses.index');
Route::get('/finanzas/inventario/almacenes/crear', [WarehouseController::class, 'createWeb'])->name('inventory.warehouses.create');
Route::get('/finanzas/inventario/almacenes/{warehouse}/editar', [WarehouseController::class, 'editWeb'])->name('inventory.warehouses.edit');
Route::post('/finanzas/inventario/almacenes', [WarehouseController::class, 'storeWeb'])->name('inventory.warehouses.store');
Route::put('/finanzas/inventario/almacenes/{warehouse}', [WarehouseController::class, 'updateWeb'])->name('inventory.warehouses.update');
Route::post('/finanzas/inventario/almacenes/mass-destroy', [WarehouseController::class, 'massDestroy'])->name('inventory.warehouses.mass_destroy');
Route::delete('/finanzas/inventario/almacenes/{warehouse}', [WarehouseController::class, 'destroyWeb'])->name('inventory.warehouses.destroy');

// API Routes
Route::get('/api/categories', [ProductController::class, 'getCategoriesApi'])->name('api.categories');
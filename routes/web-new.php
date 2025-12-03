<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\VariantController;

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
Route::delete('/finanzas/inventario/productos/{product}', [ProductController::class, 'destroyWeb'])->name('inventory.products.destroy');
Route::post('/finanzas/inventario/productos/mass-destroy', [ProductController::class, 'massDestroyWeb'])->name('inventory.products.mass_destroy');

// VARIANTES
Route::get('/finanzas/inventario/variantes', [VariantController::class, 'indexWeb'])->name('inventory.variants.index');
Route::get('/finanzas/inventario/variantes/{variant}/editar', [VariantController::class, 'editWeb'])->name('inventory.variants.edit');
Route::put('/finanzas/inventario/variantes/{variant}', [VariantController::class, 'updateWeb'])->name('inventory.variants.update');
Route::post('/finanzas/inventario/variantes/mass-destroy', [VariantController::class, 'massDestroy'])->name('inventory.variants.mass_destroy');

// API Routes
Route::get('/api/categories', [ProductController::class, 'getCategoriesApi'])->name('api.categories');
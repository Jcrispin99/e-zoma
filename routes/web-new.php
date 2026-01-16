<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\VariantController;
use App\Http\Controllers\Admin\WarehouseController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\AttributeController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\ReportController;

use App\Http\Controllers\Admin\PurchaseController;
use App\Http\Controllers\Admin\SupplierController;
use App\Http\Controllers\Admin\PurchaseOrderController;

use App\Http\Controllers\Admin\SaleController;

Route::get('/web', function () {
    return Inertia::render('MainApps');
})->name('web');

// INVENTARIO
Route::get('/finanzas/inventario', [InventoryController::class, 'dashboard'])->name('inventory.index');

// REPORTES
Route::get('/finanzas/inventario/reportes', [ReportController::class, 'index'])->name('inventory.reports.index');
Route::get('/finanzas/inventario/reportes/bajo-stock', [ReportController::class, 'lowStock'])->name('inventory.reports.low_stock');
Route::post('/finanzas/inventario/reportes/bajo-stock/export', [ReportController::class, 'exportLowStock'])->name('inventory.reports.low_stock.export');
Route::get('/finanzas/inventario/reportes/valorizacion', [ReportController::class, 'valuation'])->name('inventory.reports.valuation');
Route::post('/finanzas/inventario/reportes/valorizacion/export', [ReportController::class, 'exportValuation'])->name('inventory.reports.valuation.export');
Route::get('/finanzas/inventario/reportes/transacciones', [ReportController::class, 'kardex'])->name('inventory.reports.kardex');
Route::post('/finanzas/inventario/reportes/transacciones/export', [ReportController::class, 'exportKardex'])->name('inventory.reports.kardex.export');

// PRODUCTOS
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

// CATEGORIAS
Route::get('/finanzas/inventario/categorias', [CategoryController::class, 'indexWeb'])->name('inventory.categories.index');
Route::get('/finanzas/inventario/categorias/crear', [CategoryController::class, 'createWeb'])->name('inventory.categories.create');
Route::get('/finanzas/inventario/categorias/{category}/editar', [CategoryController::class, 'editWeb'])->name('inventory.categories.edit');
Route::post('/finanzas/inventario/categorias', [CategoryController::class, 'storeWeb'])->name('inventory.categories.store');
Route::put('/finanzas/inventario/categorias/{category}', [CategoryController::class, 'updateWeb'])->name('inventory.categories.update');
Route::post('/finanzas/inventario/categorias/mass-destroy', [CategoryController::class, 'massDestroyWeb'])->name('inventory.categories.mass_destroy');
Route::delete('/finanzas/inventario/categorias/{category}', [CategoryController::class, 'destroyWeb'])->name('inventory.categories.destroy');

// ATRIBUTOS
Route::get('/finanzas/inventario/atributos', [AttributeController::class, 'indexWeb'])->name('inventory.attributes.index');
Route::get('/finanzas/inventario/atributos/crear', [AttributeController::class, 'createWeb'])->name('inventory.attributes.create');
Route::get('/finanzas/inventario/atributos/{attribute}/editar', [AttributeController::class, 'editWeb'])->name('inventory.attributes.edit');
Route::post('/finanzas/inventario/atributos', [AttributeController::class, 'storeWeb'])->name('inventory.attributes.store');
Route::put('/finanzas/inventario/atributos/{attribute}', [AttributeController::class, 'updateWeb'])->name('inventory.attributes.update');
Route::post('/finanzas/inventario/atributos/mass-destroy', [AttributeController::class, 'massDestroyWeb'])->name('inventory.attributes.mass_destroy');
Route::delete('/finanzas/inventario/atributos/{attribute}', [AttributeController::class, 'destroyWeb'])->name('inventory.attributes.destroy');


// ALMACENES
Route::get('/finanzas/inventario/almacenes', [WarehouseController::class, 'indexWeb'])->name('inventory.warehouses.index');
Route::get('/finanzas/inventario/almacenes/crear', [WarehouseController::class, 'createWeb'])->name('inventory.warehouses.create');
Route::get('/finanzas/inventario/almacenes/{warehouse}/editar', [WarehouseController::class, 'editWeb'])->name('inventory.warehouses.edit');
Route::post('/finanzas/inventario/almacenes', [WarehouseController::class, 'storeWeb'])->name('inventory.warehouses.store');
Route::put('/finanzas/inventario/almacenes/{warehouse}', [WarehouseController::class, 'updateWeb'])->name('inventory.warehouses.update');
Route::post('/finanzas/inventario/almacenes/mass-destroy', [WarehouseController::class, 'massDestroy'])->name('inventory.warehouses.mass_destroy');
Route::delete('/finanzas/inventario/almacenes/{warehouse}', [WarehouseController::class, 'destroyWeb'])->name('inventory.warehouses.destroy');

// COMPRAS
Route::get('/finanzas/compras', [PurchaseController::class, 'dashboard'])->name('purchases.index');

// PROVEEDORES
Route::get('/finanzas/compras/proveedores', [SupplierController::class, 'indexWeb'])->name('purchases.suppliers.index');
Route::get('/finanzas/compras/proveedores/crear', [SupplierController::class, 'createWeb'])->name('purchases.suppliers.create');
Route::get('/finanzas/compras/proveedores/{supplier}/editar', [SupplierController::class, 'editWeb'])->name('purchases.suppliers.edit');
Route::post('/finanzas/compras/proveedores', [SupplierController::class, 'storeWeb'])->name('purchases.suppliers.store');
Route::put('/finanzas/compras/proveedores/{supplier}', [SupplierController::class, 'updateWeb'])->name('purchases.suppliers.update');
Route::post('/finanzas/compras/proveedores/mass-destroy', [SupplierController::class, 'massDestroyWeb'])->name('purchases.suppliers.mass_destroy');
Route::delete('/finanzas/compras/proveedores/{supplier}', [SupplierController::class, 'destroyWeb'])->name('purchases.suppliers.destroy');

// ORDENES DE COMPRA
Route::get('/finanzas/compras/ordenes', [PurchaseOrderController::class, 'indexWeb'])->name('purchases.orders.index');
Route::get('/finanzas/compras/ordenes/crear', [PurchaseOrderController::class, 'createWeb'])->name('purchases.orders.create');
Route::get('/finanzas/compras/ordenes/{purchaseOrder}/editar', [PurchaseOrderController::class, 'editWeb'])->name('purchases.orders.edit');
Route::post('/finanzas/compras/ordenes', [PurchaseOrderController::class, 'storeWeb'])->name('purchases.orders.store');
Route::put('/finanzas/compras/ordenes/{purchaseOrder}', [PurchaseOrderController::class, 'updateWeb'])->name('purchases.orders.update');
Route::post('/finanzas/compras/ordenes/mass-destroy', [PurchaseOrderController::class, 'massDestroyWeb'])->name('purchases.orders.mass_destroy');
Route::delete('/finanzas/compras/ordenes/{purchaseOrder}', [PurchaseOrderController::class, 'destroyWeb'])->name('purchases.orders.destroy');
Route::post('/finanzas/compras/ordenes/{purchaseOrder}/confirm', [PurchaseOrderController::class, 'confirmWeb'])->name('purchases.orders.confirm');
Route::post('/finanzas/compras/ordenes/{purchaseOrder}/cancel', [PurchaseOrderController::class, 'cancelWeb'])->name('purchases.orders.cancel');
Route::post('/finanzas/compras/ordenes/{purchaseOrder}/reopen', [PurchaseOrderController::class, 'reopenWeb'])->name('purchases.orders.reopen');
Route::get('/finanzas/compras/ordenes/{purchaseOrder}/api-details', [PurchaseOrderController::class, 'apiDetails'])->name('purchases.orders.api-details');

// COMPRAS (FACTURAS)
Route::get('/finanzas/compras/facturas', [PurchaseController::class, 'indexWeb'])->name('purchases.invoices.index');
Route::get('/finanzas/compras/facturas/crear', [PurchaseController::class, 'createWeb'])->name('purchases.invoices.create');
Route::get('/finanzas/compras/facturas/{purchase}/editar', [PurchaseController::class, 'editWeb'])->name('purchases.invoices.edit');
Route::post('/finanzas/compras/facturas', [PurchaseController::class, 'storeWeb'])->name('purchases.invoices.store');
Route::put('/finanzas/compras/facturas/{purchase}', [PurchaseController::class, 'updateWeb'])->name('purchases.invoices.update');
Route::post('/finanzas/compras/facturas/mass-destroy', [PurchaseController::class, 'massDestroyWeb'])->name('purchases.invoices.mass_destroy');
Route::delete('/finanzas/compras/facturas/{purchase}', [PurchaseController::class, 'destroyWeb'])->name('purchases.invoices.destroy');
Route::post('/finanzas/compras/facturas/{purchase}/contabilizar', [PurchaseController::class, 'confirmWeb'])->name('purchases.invoices.confirm');
Route::post('/finanzas/compras/facturas/{purchase}/cancelar', [PurchaseController::class, 'cancelWeb'])->name('purchases.invoices.cancel');
Route::post('/finanzas/compras/facturas/{purchase}/reabrir', [PurchaseController::class, 'reopenWeb'])->name('purchases.invoices.reopen');
Route::post('/finanzas/compras/facturas/{purchase}/pagar', [PurchaseController::class, 'markPaidWeb'])->name('purchases.invoices.mark-paid');
Route::post('/finanzas/compras/facturas/{purchase}/anular-pago', [PurchaseController::class, 'markUnpaidWeb'])->name('purchases.invoices.mark-unpaid');

// VENTAS
Route::get('/finanzas/ventas', [SaleController::class, 'dashboard'])->name('sales.index');

// API Routes
Route::get('/api/categories', [ProductController::class, 'getCategoriesApi'])->name('api.categories');
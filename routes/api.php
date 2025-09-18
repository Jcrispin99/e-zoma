<?php

use App\Models\Supplier;
use App\Models\Variant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::post('/supplier', function (Request $request) {
    return Supplier::select('id', 'name')
        ->when($request->search, function ($query, $search) {
            $query->where('name', 'like', "{$search}")
                ->orWhere('document_number', 'like', "{$search}");
        })
        ->when(
            $request->exists('selected'),
            fn($query) => $query->whereIn('id', $request->input('selected', [])),
            fn($query) => $query->limit(10)
        )->get();
})->name('supplier.index');

Route::post('/product', function (Request $request) {
    return Variant::select('id', 'sku')
        ->when($request->search, function ($query, $search) {
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('sku', 'like', "%{$search}%");
        })
        ->when(
            $request->exists('selected'),
            fn($query) => $query->whereIn('id', $request->input('selected', [])),
            fn($query) => $query->limit(10)
        )->get();
})->name('product.index');

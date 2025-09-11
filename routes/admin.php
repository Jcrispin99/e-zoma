<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\AttributeController;

Route::get('/', function () {
    return view('admin.dashboard');
})->name('dashboard');

Route::resource('categories', CategoryController::class)->except(['show']);
Route::resource('products', ProductController::class)->except(['show']);
Route::get('search-attribute-values/{index}', [AttributeController::class, 'searchValues'])->name('search-attribute-values');
Route::get('search-attributes', [AttributeController::class, 'searchAttributes'])->name('search-attributes');

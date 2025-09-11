<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AttributeController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Rutas para atributos y valores
Route::prefix('attributes')->group(function () {
    Route::get('/', [AttributeController::class, 'index']);
    Route::get('/search', [AttributeController::class, 'search']);
    Route::get('/{attributeId}/values', [AttributeController::class, 'getValues']);
    Route::post('/{attributeId}/values', [AttributeController::class, 'createValue']);
});

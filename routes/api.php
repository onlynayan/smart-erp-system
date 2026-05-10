<?php

use App\Http\Controllers\ProductCategoryController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::get('products', [ProductController::class, 'index']);
Route::post('products', [ProductController::class, 'store']);
Route::get('products/{id}', [ProductController::class, 'show']);
Route::put('products/{id}', [ProductController::class, 'update']);
Route::delete('products/{id}', [ProductController::class, 'destroy']);

Route::get('categories', [ProductCategoryController::class, 'index']);
Route::get('categories/{id}/children', [ProductCategoryController::class, 'children']);
Route::get('categories/{id}/products', [ProductCategoryController::class, 'products']);

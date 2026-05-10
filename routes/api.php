<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PartyController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductCategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PurchaseReceiveController;
use App\Http\Controllers\SalesInvoiceController;
use App\Http\Controllers\StockReportController;
use Illuminate\Support\Facades\Route;

Route::get('dashboard/summary', [DashboardController::class, 'summary']);
Route::get('dashboard/recent-transactions', [DashboardController::class, 'recentTransactions']);
Route::get('dashboard/low-stock-products', [DashboardController::class, 'lowStockProducts']);
Route::get('dashboard/top-selling-products', [DashboardController::class, 'topSellingProducts']);

Route::get('stock-report', [StockReportController::class, 'index']);
Route::get('stock-report/{product_id}', [StockReportController::class, 'show']);

Route::get('payments', [PaymentController::class, 'index']);
Route::post('payments', [PaymentController::class, 'store']);
Route::get('payments/{id}', [PaymentController::class, 'show']);

Route::get('sales-invoices', [SalesInvoiceController::class, 'index']);
Route::post('sales-invoices', [SalesInvoiceController::class, 'store']);
Route::get('sales-invoices/{id}', [SalesInvoiceController::class, 'show']);

Route::get('purchase-receives', [PurchaseReceiveController::class, 'index']);
Route::post('purchase-receives', [PurchaseReceiveController::class, 'store']);
Route::get('purchase-receives/{id}', [PurchaseReceiveController::class, 'show']);

Route::get('parties', [PartyController::class, 'index']);
Route::post('parties', [PartyController::class, 'store']);
Route::get('parties/{id}', [PartyController::class, 'show']);
Route::put('parties/{id}', [PartyController::class, 'update']);
Route::delete('parties/{id}', [PartyController::class, 'destroy']);

Route::get('products', [ProductController::class, 'index']);
Route::post('products', [ProductController::class, 'store']);
Route::get('products/{id}', [ProductController::class, 'show']);
Route::put('products/{id}', [ProductController::class, 'update']);
Route::delete('products/{id}', [ProductController::class, 'destroy']);

Route::get('categories', [ProductCategoryController::class, 'index']);
Route::get('categories/{id}/children', [ProductCategoryController::class, 'children']);
Route::get('categories/{id}/products', [ProductCategoryController::class, 'products']);

<?php

use App\Http\Controllers\Api\Admin\BusinessSettingController;
use App\Http\Controllers\Api\Admin\CustomerController;
use App\Http\Controllers\Api\Admin\PaymentController;
use App\Http\Controllers\Api\Admin\ProductController;
use App\Http\Controllers\Api\Admin\PurchaseController;
use App\Http\Controllers\Api\Admin\QuotationController;
use App\Http\Controllers\Api\Admin\ReportController;
use App\Http\Controllers\Api\Admin\SaleController;
use App\Http\Controllers\Api\Admin\SupplierController;
use App\Http\Controllers\Api\Admin\TaxController;
use App\Http\Controllers\Api\Admin\UnitController;
use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);
Route::get('/business', [BusinessSettingController::class, 'publicShow']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);
    Route::put('/profile/password', [AuthController::class, 'updatePassword']);
    Route::get('/taxes', [AuthController::class, 'taxes']);
    Route::post('/tax-preview', [AuthController::class, 'taxPreview']);

    Route::prefix('admin')->middleware('admin')->name('admin.')->group(function () {
        Route::apiResource('products', ProductController::class);
        Route::apiResource('customers', CustomerController::class);
        Route::apiResource('suppliers', SupplierController::class);
        Route::apiResource('taxes', TaxController::class);
        Route::apiResource('units', UnitController::class);
        Route::apiResource('payments', PaymentController::class);
        Route::apiResource('purchases', PurchaseController::class);
        Route::apiResource('sales', SaleController::class);
        Route::get('sales/{sale}/pdf', [SaleController::class, 'pdf'])->name('sales.pdf');
        Route::apiResource('quotations', QuotationController::class);
        Route::post('quotations/{quotation}/convert', [QuotationController::class, 'convert'])->name('quotations.convert');
        Route::get('quotations/{quotation}/pdf', [QuotationController::class, 'pdf'])->name('quotations.pdf');
        Route::get('settings', [BusinessSettingController::class, 'show'])->name('settings.show');
        Route::post('settings', [BusinessSettingController::class, 'update'])->name('settings.update');

        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('purchase', [ReportController::class, 'purchase'])->name('purchase');
            Route::get('sales', [ReportController::class, 'sales'])->name('sales');
            Route::get('invoice-profit-loss', [ReportController::class, 'invoiceProfitLoss'])->name('invoice-profit-loss');
            Route::get('item-profit-loss', [ReportController::class, 'itemProfitLoss'])->name('item-profit-loss');
            Route::get('total-profit-loss', [ReportController::class, 'totalProfitLoss'])->name('total-profit-loss');
            Route::get('product-list', [ReportController::class, 'productList'])->name('product-list');
            Route::get('stock-summary', [ReportController::class, 'stockSummary'])->name('stock-summary');
            Route::get('valuation', [ReportController::class, 'valuation'])->name('valuation');
        });
    });
});

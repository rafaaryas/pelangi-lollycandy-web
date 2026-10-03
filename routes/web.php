<?php

use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\CustomerController as AdminCustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\MarketplaceLinkController as AdminMarketplaceLinkController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\ProductionController as AdminProductionController;
use App\Http\Controllers\Admin\PurchaseController as AdminPurchaseController;
use App\Http\Controllers\Admin\RawMaterialController as AdminRawMaterialController;
use App\Http\Controllers\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Admin\SaleController as AdminSaleController;
use App\Http\Controllers\Admin\StockController as AdminStockController;
use App\Http\Controllers\Admin\SupplierController as AdminSupplierController;
use App\Http\Controllers\Public\AboutController;
use App\Http\Controllers\Public\CatalogController;
use App\Http\Controllers\Public\ContactController;
use App\Http\Controllers\Public\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/about', [AboutController::class, 'index'])->name('about');
Route::get('/products', [CatalogController::class, 'index'])->name('products.index');
Route::get('/products/{product:slug}', [CatalogController::class, 'show'])->name('products.show');
Route::get('/contact', [ContactController::class, 'index'])->name('contact.index');
Route::post('/contact', [ContactController::class, 'store'])->name('contact.store');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AdminAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AdminAuthController::class, 'login'])->name('login.submit');

    Route::middleware(['auth', 'admin'])->group(function () {
        Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::resource('products', AdminProductController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::get('products/{product}', [AdminProductController::class, 'show'])->name('products.show');
        Route::resource('categories', AdminCategoryController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('marketplace-links', AdminMarketplaceLinkController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('raw-materials', AdminRawMaterialController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::get('raw-materials/{raw_material}', [AdminRawMaterialController::class, 'show'])->name('raw-materials.show');
        Route::resource('suppliers', AdminSupplierController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::get('suppliers/{supplier}', [AdminSupplierController::class, 'show'])->name('suppliers.show');
        Route::resource('customers', AdminCustomerController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::get('customers/{customer}', [AdminCustomerController::class, 'show'])->name('customers.show');

        Route::resource('purchases', AdminPurchaseController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update']);
        Route::post('purchases/{purchase}/confirm', [AdminPurchaseController::class, 'confirm'])->name('purchases.confirm');
        Route::post('purchases/{purchase}/cancel', [AdminPurchaseController::class, 'cancel'])->name('purchases.cancel');

        Route::resource('productions', AdminProductionController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update']);
        Route::post('productions/{production}/confirm', [AdminProductionController::class, 'confirm'])->name('productions.confirm');
        Route::post('productions/{production}/cancel', [AdminProductionController::class, 'cancel'])->name('productions.cancel');

        Route::resource('sales', AdminSaleController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update']);
        Route::post('sales/{sale}/confirm', [AdminSaleController::class, 'confirm'])->name('sales.confirm');
        Route::post('sales/{sale}/cancel', [AdminSaleController::class, 'cancel'])->name('sales.cancel');

        Route::get('stock', [AdminStockController::class, 'index'])->name('stock.index');
        Route::post('stock/adjust', [AdminStockController::class, 'adjust'])->name('stock.adjust');
        Route::get('stock/{type}/{id}', [AdminStockController::class, 'history'])->name('stock.history');
        Route::get('reports', [AdminReportController::class, 'index'])->name('reports.index');
    });
});

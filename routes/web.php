<?php

use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FaqController as AdminFaqController;
use App\Http\Controllers\Admin\HeroBannerController as AdminHeroBannerController;
use App\Http\Controllers\Admin\InquiryController as AdminInquiryController;
use App\Http\Controllers\Admin\MarketplaceLinkController as AdminMarketplaceLinkController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\TestimonialController as AdminTestimonialController;
use App\Http\Controllers\Public\AboutController;
use App\Http\Controllers\Public\CatalogController;
use App\Http\Controllers\Public\ContactController;
use App\Http\Controllers\Public\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/about', [AboutController::class, 'index'])->name('about');
Route::get('/products', [CatalogController::class, 'index'])->name('products.index');
Route::get('/products/{product:slug}', [CatalogController::class, 'show'])->name('products.show');
Route::post('/products/{product}/click', [CatalogController::class, 'click'])->name('products.click');
Route::get('/contact', [ContactController::class, 'index'])->name('contact.index');
Route::post('/contact', [ContactController::class, 'store'])->name('contact.store');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AdminAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AdminAuthController::class, 'login'])->name('login.submit');

    Route::middleware(['auth', 'admin'])->group(function () {
        Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::resource('products', AdminProductController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('categories', AdminCategoryController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('hero-banners', AdminHeroBannerController::class)->except(['show']);
        Route::resource('marketplace-links', AdminMarketplaceLinkController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('testimonials', AdminTestimonialController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('faqs', AdminFaqController::class)->except(['show']);
        Route::get('inquiries', [AdminInquiryController::class, 'index'])->name('inquiries.index');
        Route::post('inquiries/{inquiry}/handle', [AdminInquiryController::class, 'markHandled'])->name('inquiries.handle');
        Route::get('inquiries-export', [AdminInquiryController::class, 'export'])->name('inquiries.export');
    });
});

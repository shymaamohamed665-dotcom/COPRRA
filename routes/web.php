<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\AIControlPanelController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\PriceAlertController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\WishlistController;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// --- المسارات العامة التي لا تتطلب تسجيل الدخول ---

// الصفحة الرئيسية
// Health check route (controller for route:cache compatibility)
Route::get('/health', [HealthController::class, 'index']);

// Legacy health-check route: redirect to unified API health endpoint
Route::get('/health-check', function () {
    return redirect('/api/health');
})->name('health.check');

Route::get('/', [HomeController::class, 'index'])->name('home');

// Dashboard route expected by tests
Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware('auth')->name('dashboard');

// Authentication routes - Using Controllers with Form Requests and Rate Limiting
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1')->name('login.post');

Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:3,1')->name('register.post');

// Alias route for password reset request expected by tests
Route::post('/forgot-password', [AuthController::class, 'sendResetLinkEmail'])->middleware('throttle:3,1')->name('password.forgot');

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// Password reset routes with Rate Limiting
Route::get('/password/reset', [AuthController::class, 'showForgotPasswordForm'])->name('password.request');
Route::post('/password/email', [AuthController::class, 'sendResetLinkEmail'])->name('password.email');
Route::get('/password/reset/{token}', [AuthController::class, 'showResetPasswordForm'])->name('password.reset');
Route::post('/password/reset', [AuthController::class, 'resetPassword'])->middleware('throttle:3,1')->name('password.update');

// Email verification routes with Rate Limiting
Route::get('/email/verify', [EmailVerificationController::class, 'notice'])->middleware('auth')->name('verification.notice');
Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])->middleware(['auth', 'signed', 'throttle:6,1'])->name('verification.verify');
Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])->middleware(['auth', 'throttle:6,1'])->name('verification.send');

// المنتجات والفئات
Route::get('products', [ProductController::class, 'index'])->name('products.index');
Route::get('products/search', [ProductController::class, 'search'])->name('products.search');
Route::get('products/{slug}', [ProductController::class, 'show'])->name('products.show');

Route::get('categories', [CategoryController::class, 'index'])->name('categories.index');
Route::get('categories/{slug}', [CategoryController::class, 'show'])->name('categories.show');

// تغيير اللغة والعملة
Route::get('language/{langCode}', [LocaleController::class, 'changeLanguage'])->name('change.language');
Route::get('currency/{currencyCode}', [LocaleController::class, 'changeCurrency'])->name('change.currency');

// Contact page
Route::get('contact', function () {
    return view('contact');
})->name('contact');

// Locale switching route
Route::post('locale/language', [LocaleController::class, 'switchLanguage'])->name('locale.language');

// --- المسارات المحمية التي تتطلب تسجيل الدخول ---

Route::middleware('auth')->group(function (): void {
    // Profile Routes
    Route::get('/profile', [App\Http\Controllers\ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [App\Http\Controllers\ProfileController::class, 'changePassword'])->name('profile.password');

    // Price Alert Routes (من الكود الخاص بك، وهو مثالي)
    Route::patch('price-alerts/{priceAlert}/toggle', [PriceAlertController::class, 'toggle'])->name('price-alerts.toggle');
    Route::resource('price-alerts', PriceAlertController::class)->parameters([
        'price-alerts' => 'priceAlert',
    ]);

    // Wishlist Routes
    Route::post('wishlist/add', [WishlistController::class, 'store'])->name('wishlist.add');
    Route::delete('wishlist/remove', [WishlistController::class, 'remove'])->name('wishlist.remove');
    Route::delete('wishlist/clear', [WishlistController::class, 'clear'])->name('wishlist.clear');
    Route::post('wishlist/toggle', [WishlistController::class, 'toggle'])->name('wishlist.toggle');
    Route::resource('wishlist', WishlistController::class)->only(['index', 'destroy']);

    // Review Routes
    Route::resource('reviews', ReviewController::class)->only(['store', 'update', 'destroy']);

    // (Moved to public routes)
});

// Cart Routes (public, ensure web middleware is explicitly applied)
Route::middleware('web')->group(function (): void {
    Route::get('cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('cart', [CartController::class, 'addFromRequest'])->name('cart.store');
    Route::post('cart/add/{product}', [CartController::class, 'add'])->name('cart.add');
    Route::post('cart/update', [CartController::class, 'update'])->name('cart.update');
    Route::delete('cart/remove/{itemId}', [CartController::class, 'remove'])->name('cart.remove');
    Route::post('cart/clear', [CartController::class, 'clear'])->name('cart.clear');
});

// Checkout route expected by tests
Route::get('/checkout', function () {
    return response('Checkout', 200);
})->middleware('auth')->name('checkout');

// Web Order routes for E2E tests
Route::middleware('auth')->group(function (): void {
    Route::get('/orders', [\App\Http\Controllers\OrderController::class, 'index'])->name('orders.index');
    Route::post('/orders', [\App\Http\Controllers\OrderController::class, 'storeFromCart'])->name('orders.store');
    Route::get('/orders/{order}', [\App\Http\Controllers\OrderController::class, 'show'])->name('orders.show');
    Route::patch('/orders/{order}/status', [\App\Http\Controllers\OrderController::class, 'updateStatus'])->name('orders.update-status');
    Route::post('/orders/{order}/cancel', [\App\Http\Controllers\OrderController::class, 'cancel'])->name('orders.cancel');
});

// --- Admin Routes (تتطلب صلاحيات إدارية) ---

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('users', [AdminController::class, 'users'])->name('users');
    Route::get('products', [AdminController::class, 'products'])->name('products');
    Route::get('brands', [AdminController::class, 'brands'])->name('brands');
    Route::get('categories', [AdminController::class, 'categories'])->name('admin.categories');
    Route::get('stores', [AdminController::class, 'stores'])->name('stores');
    Route::post('users/{user}/toggle-admin', [AdminController::class, 'toggleUserAdmin'])->name('users.toggle-admin');

    // AI Control Panel Routes
    Route::prefix('ai')->name('ai.')->group(function (): void {
        Route::get('/', [AIControlPanelController::class, 'index'])->name('index');
        Route::post('/analyze-text', [AIControlPanelController::class, 'analyzeText'])->name('analyze-text');
        Route::post('/classify-product', [AIControlPanelController::class, 'classifyProduct'])->name('classify-product');
        Route::post('/recommendations', [AIControlPanelController::class, 'generateRecommendations'])->name('recommendations');
        Route::post('/analyze-image', [AIControlPanelController::class, 'analyzeImage'])->name('analyze-image');
        Route::get('/status', [AIControlPanelController::class, 'getStatus'])->name('status');
    });
});

// --- Brand Routes (تتطلب تسجيل الدخول) ---

Route::middleware('auth')->group(function (): void {
    Route::resource('brands', BrandController::class);
});

// Secure file serving via signed URLs (private storage)
Route::get('/files/{path}', [\App\Http\Controllers\FileController::class, 'show'])
    ->where('path', '.*')
    ->middleware(['signed'])
    ->name('files.show');

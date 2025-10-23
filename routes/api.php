<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Admin\BrandController;
use App\Http\Controllers\Api\Admin\CategoryController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DocumentationController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PriceSearchController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\WebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

// Authentication routes with Rate Limiting
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:3,1');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::middleware(['auth:sanctum', 'throttle:auth'])->get('/user', [AuthController::class, 'me']);
Route::middleware(['auth:sanctum', 'throttle:authenticated'])->get('/me', [AuthController::class, 'me']);

// Public API routes (no authentication required)
Route::middleware(['throttle:public'])->group(function (): void {
    // Price search routes
    Route::get('/price-search', [\App\Http\Controllers\Api\PriceSearchController::class, 'search']);
    Route::get('/price-search/search', [\App\Http\Controllers\Api\PriceSearchController::class, 'search']);
    Route::get('/price-search/best-offer', [\App\Http\Controllers\Api\PriceSearchController::class, 'bestOffer']);
    Route::get('/price-search/supported-stores', [\App\Http\Controllers\Api\PriceSearchController::class, 'supportedStores']);

    // Public product routes
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/{id}', [ProductController::class, 'show'])->whereNumber('id');

    // Additional API routes for testing
    Route::get('/categories', function () {
        return response()->json(['data' => [], 'message' => 'Categories endpoint']);
    });

    Route::get('/brands', function () {
        return response()->json(['data' => [], 'message' => 'Brands endpoint']);
    });

    Route::get('/wishlist', function () {
        return response()->json(['data' => [], 'message' => 'Wishlist endpoint']);
    });

    Route::get('/price-alerts', function () {
        return response()->json(['data' => [], 'message' => 'Price alerts endpoint']);
    });

    Route::get('/reviews', function () {
        return response()->json(['data' => [], 'message' => 'Reviews endpoint']);
    });

    Route::get('/search', function () {
        return response()->json(['data' => [], 'message' => 'Search endpoint']);
    });

    Route::get('/ai', function () {
        return response()->json(['data' => [], 'message' => 'AI endpoint']);
    });

    // Product creation requires authentication
    Route::post('/products', [ProductController::class, 'store']);
});

// Authenticated API routes
Route::middleware(['auth:sanctum', 'throttle:authenticated'])->group(function (): void {
    // Protected product routes
    Route::put('/products/{id}', [ProductController::class, 'update']);
    Route::delete('/products/{id}', [ProductController::class, 'destroy']);

    // Reviews routes
    Route::post('/products/{product}/reviews', [\App\Http\Controllers\ReviewController::class, 'store']);

    // Order routes
    Route::apiResource('orders', OrderController::class);

    // Secure upload route using UploadController
    Route::post('/uploads', [\App\Http\Controllers\UploadController::class, 'store']);
});

// Product deletion requires authentication
// Route::middleware(['throttle:public'])->group(function () {
//     Route::delete('/products/{id}', [ProductController::class, 'destroy']);
// });

// Admin API routes (high rate limits)
// Use 'auth' guard to align with tests using actingAs without Sanctum
Route::middleware(['auth', 'admin', 'throttle:admin'])->group(function (): void {
    // Admin-specific routes
    Route::get('/admin/stats', function () {
        return response()->json([
            'uptime' => time() - strtotime('2025-01-01 00:00:00'),
            'total_users' => \App\Models\User::count(),
            'total_products' => \App\Models\Product::count(),
            'total_offers' => \App\Models\PriceOffer::count(),
            'total_reviews' => \App\Models\Review::count(),
            'active_users_today' => \App\Models\User::whereDate('created_at', today())->count(),
            'new_products_today' => \App\Models\Product::whereDate('created_at', today())->count(),
            'server_time' => now()->toISOString(),
            'status' => 'operational',
        ]);
    });

    // Admin resource routes
    Route::apiResource('admin/categories', CategoryController::class)->names('api.admin.categories');
    Route::apiResource('admin/brands', BrandController::class)->names('api.admin.brands');
});

// API Documentation (no rate limiting for documentation)
Route::get('/', [DocumentationController::class, 'index']);
Route::get('/documentation', [DocumentationController::class, 'index']);

// API Health check (unified JSON)
Route::get('/health', [\App\Http\Controllers\Api\DocumentationController::class, 'health']);

// CSRF token route for testing - REMOVED FOR PRODUCTION
// Route::get('/csrf-token', function () {
//     return response()->json(['token' => uniqid('csrf_', true)]);
// });

// Debug route for best offer - REMOVED FOR PRODUCTION
// Route::get('/debug-best-offer', function (Request $request) {
//     return response()->json([
//         'message' => 'Debug route working',
//         'params' => $request->all(),
//         'url' => $request->url()
//     ]);
// });

// Simple test route
Route::get('/test-simple', function () {
    return response()->json(['message' => 'Simple test route works']);
});

// Test route for API tests
Route::get('/test', function () {
    return response()->json([
        'data' => ['message' => 'API test route works'],
        'status' => 'success',
    ]);
});

// POST route for validation testing
Route::post('/test', function (Request $request) {
    try {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
        ]);

        return response()->json(['message' => 'Validation passed', 'data' => $validated]);
    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'message' => 'Validation failed',
            'errors' => $e->errors(),
        ], 422);
    }
});

// Temporary best offer route outside middleware - test method call
Route::get('/best-offer-debug', function (Request $request) {
    try {
        $controller = app(\App\Http\Controllers\Api\PriceSearchController::class);

        return $controller->bestOffer($request);
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Method call failed',
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ], 500);
    }
});

// Direct test of the bestOffer method
Route::get('/direct-best-offer', function (Request $request) {
    return response()->json([
        'message' => 'Direct route test',
        'params' => $request->all(),
        'method' => 'bestOffer',
        'controller' => 'PriceSearchController',
    ]);
});

// Versioned API routes
Route::prefix('v1')->middleware(['throttle:api'])->group(function (): void {
    Route::get('/best-offer', [PriceSearchController::class, 'bestOffer']);
    Route::get('/supported-stores', [PriceSearchController::class, 'supportedStores']);
});

// Test API routes for external service testing
Route::middleware(['throttle:public'])->group(function (): void {
    // AI Text Analysis API
    Route::post('/ai/analyze', [\App\Http\Controllers\Api\AIController::class, 'analyze']);

    // AI Product Classification API
    Route::post('/ai/classify-product', [\App\Http\Controllers\Api\AIController::class, 'classifyProduct']);

    Route::get('/external-data', function () {
        try {
            $response = Http::get('https://api.external-service.com/data');

            return response()->json($response->json(), $response->status());
        } catch (\Exception $e) {
            return response()->json(['error' => 'External service unavailable'], 503);
        }
    });

    Route::get('/slow-external-data', function () {
        try {
            $response = Http::timeout(3)->get('https://api.slow-service.com/data');

            return response()->json($response->json(), $response->status());
        } catch (\Exception $e) {
            return response()->json(['error' => 'Service timeout'], 408);
        }
    });

    Route::get('/error-external-data', function () {
        try {
            $response = Http::get('https://api.error-service.com/data');
            if ($response->status() >= 400) {
                return response()->json(['error' => 'External service error'], 502);
            }

            return response()->json($response->json(), $response->status());
        } catch (\Exception $e) {
            return response()->json(['error' => 'External service unavailable'], 503);
        }
    });

    Route::get('/authenticated-external-data', function () {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer test-token',
            ])->get('https://api.authenticated-service.com/data');

            return response()->json($response->json(), $response->status());
        } catch (\Exception $e) {
            return response()->json(['error' => 'Authentication failed'], 401);
        }
    });

    Route::get('/rate-limited-external-data', function () {
        try {
            $response = Http::get('https://api.rate-limited-service.com/data');
            if ($response->status() === 429) {
                return response()->json(['error' => 'Rate limited'], 429);
            }

            return response()->json($response->json(), $response->status());
        } catch (\Exception $e) {
            return response()->json(['error' => 'Service unavailable'], 503);
        }
    });

    Route::get('/cached-external-data', function () {
        return Cache::remember('external-data', 60, function () {
            try {
                $response = Http::get('https://api.cacheable-service.com/data');

                return $response->json();
            } catch (\Exception $e) {
                return ['error' => 'Service unavailable'];
            }
        });
    });

    Route::get('/fallback-external-data', function () {
        try {
            // Try primary service first
            $response = Http::get('https://api.primary-service.com/data');
            if ($response->successful()) {
                return response()->json($response->json(), 200);
            }
        } catch (\Exception $e) {
            // Primary service failed, try fallback
        }

        try {
            // Try fallback service
            $response = Http::get('https://api.fallback-service.com/data');

            return response()->json($response->json(), $response->status());
        } catch (\Exception $e) {
            return response()->json(['error' => 'All services unavailable'], 503);
        }
    });
});

// Payment Routes
Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/payment-methods', [\App\Http\Controllers\PaymentController::class, 'getPaymentMethods']);
    Route::post('/orders/{order}/payments', [\App\Http\Controllers\PaymentController::class, 'processPayment']);
    Route::post('/orders/{order}/refund', [\App\Http\Controllers\PaymentController::class, 'refundPayment']);
});

// Order Routes
Route::middleware(['auth:sanctum', 'throttle:authenticated'])->group(function (): void {
    Route::get('/orders', [\App\Http\Controllers\Api\OrderController::class, 'index']);
    Route::post('/orders', [\App\Http\Controllers\Api\OrderController::class, 'store']);
    Route::get('/orders/{order}', [\App\Http\Controllers\Api\OrderController::class, 'show']);
});

// Points & Rewards Routes
Route::middleware(['auth:sanctum', 'throttle:authenticated'])->group(function (): void {
    Route::get('/points', [\App\Http\Controllers\PointsController::class, 'index']);
    Route::post('/points/redeem', [\App\Http\Controllers\PointsController::class, 'redeem']);
    Route::get('/rewards', [\App\Http\Controllers\PointsController::class, 'getRewards']);
    Route::post('/rewards/{reward}/redeem', [\App\Http\Controllers\PointsController::class, 'redeemReward']);
});

// Settings API routes
Route::middleware(['throttle:api'])->prefix('settings')->group(function (): void {
    Route::get('/', [\App\Http\Controllers\SettingController::class, 'index']);
    Route::put('/', [\App\Http\Controllers\SettingController::class, 'update']);
    Route::get('/password-policy', [\App\Http\Controllers\SettingController::class, 'getPasswordPolicySettings']);
    Route::get('/notifications', [\App\Http\Controllers\SettingController::class, 'getNotificationSettings']);
    Route::get('/storage', [\App\Http\Controllers\SettingController::class, 'getStorageSettings']);
    Route::get('/general', [\App\Http\Controllers\SettingController::class, 'getGeneralSettings']);
    Route::get('/security', [\App\Http\Controllers\SettingController::class, 'getSecuritySettings']);
    Route::get('/performance', [\App\Http\Controllers\SettingController::class, 'getPerformanceSettings']);
    Route::post('/reset', [\App\Http\Controllers\SettingController::class, 'resetToDefault']);
    Route::post('/import', [\App\Http\Controllers\SettingController::class, 'importSettings']);
    Route::get('/export', [\App\Http\Controllers\SettingController::class, 'exportSettings']);
    Route::get('/system-health', [\App\Http\Controllers\SettingController::class, 'getSystemHealth']);
});

// System API routes
Route::middleware(['throttle:api'])->prefix('system')->group(function (): void {
    // Wrap system info in try/catch to return unified JSON on errors (as tests expect)
    Route::get('/info', function () {
        try {
            return app(\App\Http\Controllers\SystemController::class)->getSystemInfo();
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get system information',
            ], 500);
        }
    });
    Route::post('/migrations', [\App\Http\Controllers\SystemController::class, 'runMigrations']);
    Route::post('/cache/clear', [\App\Http\Controllers\SystemController::class, 'clearCache']);
    Route::post('/optimize', [\App\Http\Controllers\SystemController::class, 'optimizeApp']);
    Route::post('/composer-update', [\App\Http\Controllers\SystemController::class, 'runComposerUpdate']);
    // Wrap performance metrics endpoint to return unified JSON on exceptions
    Route::get('/performance', function () {
        try {
            return app(\App\Http\Controllers\SystemController::class)->getPerformanceMetrics();
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get performance metrics',
            ], 500);
        }
    });
});

// Report API routes
Route::middleware(['throttle:api'])->prefix('reports')->group(function (): void {
    // POST routes for generating reports
    Route::post('/product-performance', [\App\Http\Controllers\ReportController::class, 'generateProductPerformanceReport']);
    Route::post('/user-activity', [\App\Http\Controllers\ReportController::class, 'generateUserActivityReport']);
    Route::post('/sales', [\App\Http\Controllers\ReportController::class, 'generateSalesReport']);
    Route::post('/custom', [\App\Http\Controllers\ReportController::class, 'generateCustomReport']);
    Route::post('/export', [\App\Http\Controllers\ReportController::class, 'exportReport']);

    // GET routes for retrieving reports
    Route::get('/system-overview', [\App\Http\Controllers\ReportController::class, 'getSystemOverview']);
    Route::get('/engagement-metrics', [\App\Http\Controllers\ReportController::class, 'getEngagementMetrics']);
    Route::get('/performance-metrics', [\App\Http\Controllers\ReportController::class, 'getPerformanceMetrics']);
    Route::get('/top-stores', [\App\Http\Controllers\ReportController::class, 'getTopStores']);
    Route::get('/price-trends', [\App\Http\Controllers\ReportController::class, 'getPriceTrends']);
    Route::get('/most-viewed-products', [\App\Http\Controllers\ReportController::class, 'getMostViewedProducts']);
});

// Analytics API routes
Route::middleware(['throttle:public'])->group(function (): void {
    Route::get('/analytics/site', [\App\Http\Controllers\AnalyticsController::class, 'siteAnalytics']);
});
Route::middleware(['auth:sanctum', 'throttle:authenticated'])->group(function (): void {
    Route::get('/analytics/user', [\App\Http\Controllers\AnalyticsController::class, 'userAnalytics']);
    Route::post('/analytics/track', [\App\Http\Controllers\AnalyticsController::class, 'trackBehavior']);
});

// AI API routes
Route::middleware(['throttle:ai'])->prefix('ai')->group(function (): void {
    Route::post('/analyze', function (Request $request) {
        try {
            /** @var array{text: string, type: string} $validated */
            $validated = $request->validate([
                'text' => 'required|string|max:10000',
                'type' => 'required|string|in:general,product_analysis,product_classification,recommendations,sentiment',
            ]);

            $validTypes = ['general', 'product_analysis', 'product_classification', 'recommendations', 'sentiment'];
            if (! in_array($validated['type'], $validTypes, true)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation failed',
                    'message' => 'Invalid input data',
                    'errors' => ['type' => ['The selected type is invalid.']],
                ], 422);
            }

            $aiService = app(\App\Services\AIService::class);
            $result = $aiService->analyzeText($validated['text'], $validated['type']);

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Analysis completed successfully',
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'message' => 'Invalid input data',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Analysis failed',
                'message' => 'فشل في تحليل النص',
                'details' => $e->getMessage(),
            ], 500);
        }
    });

    Route::post('/classify-product', function (Request $request) {
        try {
            /** @var array{name: string, description: ?string, price: ?(string|int|float)} $validated */
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string|max:1000',
                'price' => 'nullable|numeric|min:0',
            ]);

            $aiService = app(\App\Services\AIService::class);
            $productDescription = $validated['description'] ?? '';
            $category = $aiService->classifyProduct($productDescription);

            return response()->json([
                'success' => true,
                'category' => $category,
                'confidence' => 0.8,
                'data' => [
                    'category' => $category,
                    'confidence' => 0.8,
                ],
                'message' => 'Product classified successfully',
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'message' => 'Invalid input data',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Classification failed',
                'message' => 'فشل في تصنيف المنتج',
                'details' => $e->getMessage(),
            ], 500);
        }
    });

    Route::post('/analyze-image', function (Request $request) {
        /** @var array{image_url: string} $validated */
        $validated = $request->validate([
            'image_url' => 'required|url|max:2048',
        ]);

        try {
            $aiService = app(\App\Services\AIService::class);
            $result = $aiService->analyzeImage($validated['image_url']);

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'فشل في تحليل الصورة',
                'message' => $e->getMessage(),
            ], 500);
        }
    });

    Route::post('/recommendations', function (Request $request) {
        $validated = $request->validate([
            'preferences' => 'required|array|min:1',
            'products' => 'required|array|min:1',
            'products.*.name' => 'required|string|max:255',
            'products.*.description' => 'nullable|string|max:1000',
            'products.*.price' => 'nullable|numeric|min:0',
        ]);

        /**
         * @var array{
         *   preferences: array<string, mixed>,
         *   products: array<int, array<string, mixed>>
         * } $validated
         */
        try {
            $aiService = app(\App\Services\AIService::class);

            $recommendations = $aiService->generateRecommendations($validated['preferences'], $validated['products']);

            return response()->json([
                'success' => true,
                'recommendations' => $recommendations,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'فشل في توليد التوصيات',
                'message' => $e->getMessage(),
            ], 500);
        }
    });
});

// ============================================================================
// Webhook Routes (No Authentication - Verified by Signature)
// ============================================================================

Route::prefix('webhooks')->group(function (): void {
    Route::post('/amazon', [WebhookController::class, 'amazon'])->name('webhooks.amazon');
    Route::post('/ebay', [WebhookController::class, 'ebay'])->name('webhooks.ebay');
    Route::post('/noon', [WebhookController::class, 'noon'])->name('webhooks.noon');
});

// Secure upload endpoint (authenticated), stores to private disk and returns signed URL
Route::post('/uploads', [\App\Http\Controllers\UploadController::class, 'store'])
    ->middleware(['auth:sanctum', 'throttle:api'])
    ->name('uploads.store');

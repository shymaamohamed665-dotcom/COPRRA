<?php

declare(strict_types=1);

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Testing OptimizedQueryService...\n";

try {
    $queryService = app(\App\Services\OptimizedQueryService::class);

    // Test dashboard analytics
    $analytics = $queryService->getDashboardAnalytics();
    echo "Dashboard analytics retrieved successfully.\n";
    echo 'Total users: '.$analytics['total_users']."\n";
    echo 'Total products: '.$analytics['total_products']."\n";

    // Test popular products
    $popularProducts = $queryService->getPopularProducts(5);
    echo 'Popular products retrieved successfully. Count: '.$popularProducts->count()."\n";

    // Test product search
    $searchResults = $queryService->searchProducts('test', 10);
    echo 'Product search completed successfully. Total results: '.$searchResults->total()."\n";
} catch (Exception $e) {
    echo 'Error: '.$e->getMessage()."\n";
}

echo "OptimizedQueryService test completed.\n";

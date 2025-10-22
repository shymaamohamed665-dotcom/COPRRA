<?php

declare(strict_types=1);

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Testing CacheService...\n";

try {
    $cacheService = app(\App\Services\CacheService::class);
    $cacheService->warmUpFrequentlyAccessedData();
    echo "Cache warm-up completed successfully.\n";

    // Test cache retrieval
    $cachedData = $cacheService->get('popular_products');
    if ($cachedData) {
        echo "Popular products cache retrieved successfully.\n";
    } else {
        echo "Popular products cache is empty (expected if no data).\n";
    }
} catch (Exception $e) {
    echo 'Error: '.$e->getMessage()."\n";
}

echo "CacheService test completed.\n";

<?php

declare(strict_types=1);

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Testing Product caching...\n";

$product = App\Models\Product::first();

if ($product) {
    echo 'Product found: '.$product->name.PHP_EOL;
    echo 'Avg rating: '.$product->getAverageRating().PHP_EOL;
    echo 'Total reviews: '.$product->getTotalReviews().PHP_EOL;
    echo 'Current price: '.$product->getCurrentPrice().PHP_EOL;
    echo 'Is in wishlist (user 1): '.($product->isInWishlist(1) ? 'Yes' : 'No').PHP_EOL;
} else {
    echo 'No products found'.PHP_EOL;
}

echo "Cache test completed.\n";

<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\AnalyticsService;
use App\Services\CacheService;
use App\Services\PriceComparisonService;
use App\Services\StoreAdapterManager;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PriceComparisonController extends Controller
{
    public function __construct(
        private readonly AnalyticsService $analyticsService,
        private readonly CacheService $cacheService,
        private readonly StoreAdapterManager $storeAdapterManager,
        private readonly PriceComparisonService $priceComparisonService
    ) {}

    /**
     * Show price comparison for a product.
     */
    public function show(Request $request, Product $product): View
    {
        $this->analyticsService->trackPriceComparison(
            $product->id,
            auth()->check() ? (int) auth()->id() : null
        );

        /** @var array<int, array<string, string|float|bool|null>> $prices */
        $prices = $this->cacheService->getCachedPriceComparison($product->id);

        if (! $prices) {
            $prices = $this->priceComparisonService->fetchPricesFromStores($product);
            $this->cacheService->cachePriceComparison($product->id, $prices);
        }

        $prices = $this->priceComparisonService->markBestDeal($prices);

        $showHistory = $request->boolean('history', false);
        $priceHistory = $showHistory ? $this->getPriceHistory() : [];

        return view('products.price-comparison', [
            'product' => $product,
            'prices' => $prices,
            'showHistory' => $showHistory,
            'priceHistory' => $priceHistory,
            'availableStores' => $this->storeAdapterManager->getAvailableStores(),
        ]);
    }

    /**
     * API endpoint to refresh prices.
     */
    public function refresh(Product $product): \Illuminate\Http\JsonResponse
    {
        $this->cacheService->invalidateProduct($product->id);

        $prices = $this->priceComparisonService->fetchPricesFromStores($product);
        $this->cacheService->cachePriceComparison($product->id, $prices);

        return response()->json([
            'success' => true,
            'prices' => $this->priceComparisonService->markBestDeal($prices),
        ]);
    }

    /**
     * Get price history for product.
     *
     * @psalm-return array<never, never>
     */
    private function getPriceHistory(): array
    {
        return [];
    }
}

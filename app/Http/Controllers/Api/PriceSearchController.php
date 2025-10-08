<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PriceOffer;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PriceSearchController extends Controller
{
    public function bestOffer(Request $request): JsonResponse
    {
        try {
            // Support both product_id and product_name parameters
            $productId = $request->input('product_id');
            $productName = $request->input('product_name');

            if (($productId === null) && ($productName === null || $productName === '')) {
                // If no parameters, return all products as a list
                $queryBuilder = Product::with([
                    'priceOffers' => static function ($query): void {
                        /** @var \Illuminate\Database\Eloquent\Builder<\App\Models\PriceOffer> $query */
                        $query->where('is_available', true)
                            ->orderBy('price', 'asc')
                            ->with('store:id,name');
                    },
                    'brand:id,name',
                    'category:id,name',
                ]);
                /** @var \Illuminate\Support\Collection<int, \App\Models\Product> $products */
                $products = $queryBuilder->where('is_active', true)->limit(10)->get();

                return response()->json([
                    'data' => $products->map(static function (Product $product): array {
                        $bestOffer = $product->priceOffers->first();

                        return [
                            'product_id' => $product->id,
                            'name' => $product->name,
                            'price' => $bestOffer ? $bestOffer->price : $product->price,
                            'store' => $bestOffer && $bestOffer->store ? $bestOffer->store->name : 'Unknown Store',
                            'is_available' => $bestOffer ? $bestOffer->is_available : true,
                        ];
                    })->toArray(),
                ]);
            }

            // Find product by ID or name
            $product = null;
            if ($productId) {
                $queryBuilder = Product::with([
                    'priceOffers' => static function ($query): void {
                        /** @var \Illuminate\Database\Eloquent\Builder<\App\Models\PriceOffer> $query */
                        $query->where('is_available', true)
                            ->orderBy('price', 'asc')
                            ->with('store:id,name');
                    },
                    'brand:id,name',
                    'category:id,name',
                ]);
                $product = $queryBuilder->find($productId);
            } elseif ($productName) {
                $productNameStr = is_string($productName) ? $productName : '';
                $queryBuilder = Product::with([
                    'priceOffers' => static function ($query): void {
                        /** @var \Illuminate\Database\Eloquent\Builder<\App\Models\PriceOffer> $query */
                        $query->where('is_available', true)
                            ->orderBy('price', 'asc')
                            ->with('store:id,name');
                    },
                    'brand:id,name',
                    'category:id,name',
                ]);
                /** @var \App\Models\Product $product */
                $product = $queryBuilder->where('name', 'like', '%'.$productNameStr.'%')->first();
            }

            if (! $product) {
                return response()->json([
                    'message' => 'Product not found',
                ], 404);
            }

            if ($product->priceOffers->isEmpty()) {
                return response()->json([
                    'message' => 'No offers available for this product',
                ], 404);
            }

            /** @var \App\Models\PriceOffer $bestOffer */
            $bestOffer = $product->priceOffers->first();

            return response()->json([
                'data' => [
                    'product_id' => $product->id,
                    'price' => $bestOffer->price,
                    'store_id' => $bestOffer->store_id,
                    'store' => $bestOffer->store ? $bestOffer->store->name : 'Unknown Store',
                    'store_url' => $bestOffer->product_url,
                    'is_available' => $bestOffer->is_available,
                    'total_offers' => $product->priceOffers->count(),
                ],
                'offers' => $product->priceOffers->map(static function (PriceOffer $offer): array {
                    return [
                        'id' => $offer->id,
                        'price' => $offer->price,
                        'store_id' => $offer->store_id,
                        'store' => $offer->store ? $offer->store->name : 'Unknown Store',
                        'store_url' => $offer->product_url,
                        'is_available' => (bool) $offer->is_available,
                    ];
                })->toArray(),
            ]);
        } catch (\Exception $exception) {
            Log::error('PriceSearchController@bestOffer failed: '.$exception->getMessage());

            return response()->json([
                'message' => 'An error occurred while finding the best offer',
                'error' => $exception->getMessage(),
            ], 500);
        }
    }

    // Either implement getCountryCode() logic or remove if truly unused
    protected function getCountryCode(Request $request): string
    {
        return $request->header('CF-IPCountry') ?? 'US';
    }
}

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
            // Reject invalid types provided via query string or request body
            foreach ([
                $request->input('q'),
                $request->query('q'),
                $request->input('query'),
                $request->query('query'),
                $request->input('name'),
                $request->query('name'),
            ] as $value) {
                if (is_array($value) || is_object($value) || is_bool($value)) {
                    return response()->json([
                        'message' => 'Search query is required. Use parameter: q, query, or name',
                    ], 400);
                }
            }
            // Support parameters from query string, request body, or headers
            $productId = $request->query('product_id')
                ?? $request->input('product_id')
                ?? $request->header('product_id');
            $productName = $request->query('product_name')
                ?? $request->input('product_name')
                ?? $request->header('product_name');

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

                if ($products->isEmpty()) {
                    return response()->json([
                        'message' => 'No products available',
                    ], 404);
                }

                return response()->json([
                    'data' => $products->map(/**
                     * @return array<scalar>
                     *
                     * @psalm-return array{product_id: int, name: string, price: float|string, store: string, is_available: bool}
                     */
                        static function (Product $product): array {
                            $bestOffer = $product->priceOffers->first();

                            return [
                                'product_id' => $product->id,
                                'name' => $product->name,
                                'price' => $bestOffer ? $bestOffer->price : $product->price,
                                'store' => $bestOffer && $bestOffer->store ? $bestOffer->store->name : 'Unknown Store',
                                'is_available' => $bestOffer ? (bool) $bestOffer->is_available : true,
                            ];
                        }
                    )->toArray(),
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
                    'is_available' => (bool) $bestOffer->is_available,
                    'total_offers' => $product->priceOffers->count(),
                    'offers' => $product->priceOffers->map(/**
                     * @return array<scalar|null>
                     *
                     * @psalm-return array{id: int, price: float, store_id: int, store: string, store_url: string|null, is_available: bool}
                     */
                        static function (PriceOffer $offer): array {
                            return [
                                'id' => $offer->id,
                                'price' => $offer->price,
                                'store_id' => $offer->store_id,
                                'store' => $offer->store ? $offer->store->name : 'Unknown Store',
                                'store_url' => $offer->product_url,
                                'is_available' => (bool) $offer->is_available,
                            ];
                        }
                    )->toArray(),
                ],
            ]);
        } catch (\Exception $exception) {
            Log::error('PriceSearchController@bestOffer failed: '.$exception->getMessage());

            return response()->json([
                'message' => 'An error occurred while finding the best offer',
                'error' => $exception->getMessage(),
            ], 500);
        }
    }

    public function supportedStores(Request $request): JsonResponse
    {
        try {
            $stores = \App\Models\Store::query()
                ->select(['id', 'name', 'slug', 'is_active'])
                ->get();

            return response()->json($stores);
        } catch (\Exception $exception) {
            Log::error('PriceSearchController@supportedStores failed: '.$exception->getMessage());

            return response()->json([
                'message' => 'An error occurred while fetching supported stores',
                'error' => $exception->getMessage(),
            ], 500);
        }
    }

    public function search(Request $request): JsonResponse
    {
        try {
            // If headers provide multiple values or non-scalar values, treat as invalid
            foreach (['q', 'query', 'name'] as $param) {
                $headerValues = $request->headers->get($param, null);
                if (is_array($headerValues)) {
                    if (count($headerValues) !== 1) {
                        return response()->json([
                            'message' => 'Search query is required. Use parameter: q, query, or name',
                        ], 400);
                    }
                    $hv = $headerValues[0];
                    if (is_array($hv) || is_object($hv) || is_bool($hv)) {
                        return response()->json([
                            'message' => 'Search query is required. Use parameter: q, query, or name',
                        ], 400);
                    }
                }
                // Also check raw server variables for headers that may have been passed as arrays
                $serverKey = 'HTTP_'.strtoupper(str_replace('-', '_', $param));
                $serverVal = $request->server($serverKey);
                if (is_array($serverVal) || is_object($serverVal) || is_bool($serverVal)) {
                    return response()->json([
                        'message' => 'Search query is required. Use parameter: q, query, or name',
                    ], 400);
                }
            }
            // Accept search term from query string, request body, or headers
            $candidate = $request->query('q')
                ?? $request->input('q')
                ?? $request->header('q');
            $candidate ??= ($request->query('query')
                ?? $request->input('query')
                ?? $request->header('query'));
            $candidate ??= ($request->query('name')
                ?? $request->input('name')
                ?? $request->header('name'));

            if (is_array($candidate) || is_object($candidate) || is_bool($candidate)) {
                return response()->json([
                    'message' => 'Search query is required. Use parameter: q, query, or name',
                ], 400);
            }

            if ($candidate === null) {
                return response()->json([
                    'message' => 'Search query is required. Use parameter: q, query, or name',
                ], 400);
            }

            $queryString = trim((string) $candidate);

            if ($queryString === '') {
                return response()->json([
                    'message' => 'Search query is required. Use parameter: q, query, or name',
                ], 400);
            }

            $products = Product::query()
                ->where('is_active', true)
                ->where('name', 'like', '%'.$queryString.'%')
                ->limit(10)
                ->get();

            return response()->json([
                'data' => $products->toArray(),
                'results' => $products->count(),
                'products' => $products->toArray(),
                'total' => $products->count(),
                'query' => $queryString,
            ], 200);
        } catch (\Exception $exception) {
            Log::error('PriceSearchController@search failed: '.$exception->getMessage());

            return response()->json([
                'message' => 'An error occurred while searching for products',
                'error' => $exception->getMessage(),
            ], 500);
        }
    }

    // Either implement getCountryCode() logic or remove if truly unused
    protected function getCountryCode(Request $request): array|string
    {
        return $request->header('CF-IPCountry') ?? 'US';
    }
}

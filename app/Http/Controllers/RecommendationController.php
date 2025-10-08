<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\RecommendationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RecommendationController extends Controller
{
    public function __construct(private RecommendationService $recommendationService) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $limitValue = $request->get('limit', 10);
        $limit = is_numeric($limitValue) ? (int) $limitValue : 10;

        $recommendations = $this->recommendationService->getRecommendations($user, $limit);

        return response()->json([
            'recommendations' => $recommendations,
        ]);
    }

    public function similar(Product $product): JsonResponse
    {
        $similarProducts = $this->recommendationService->getSimilarProducts($product);

        return response()->json([
            'similar_products' => $similarProducts,
        ]);
    }

    public function frequentlyBought(Product $product): JsonResponse
    {
        $frequentlyBought = $this->recommendationService->getFrequentlyBoughtTogether($product);

        return response()->json([
            'frequently_bought_together' => $frequentlyBought,
        ]);
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Services\AIService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AIController extends BaseApiController
{
    private readonly AIService $aiService;

    public function __construct(AIService $aiService)
    {
        $this->aiService = $aiService;
    }

    public function analyze(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'text' => 'required|string|min:1|max:10000',
                'type' => 'nullable|string|in:general,product_analysis,product_classification,recommendations,sentiment',
            ]);

            $text = $validated['text'];
            $type = $validated['type'] ?? 'general';

            // Use AIService for actual analysis
            $result = $this->aiService->analyzeText($text, $type);

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Analysis completed successfully',
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Analysis failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function classifyProduct(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|min:1|max:255',
                'description' => 'nullable|string|max:1000',
                'price' => 'nullable|numeric|min:0',
            ]);

            $name = $validated['name'];

            // Use AIService for actual classification
            $result = $this->aiService->classifyProduct($name);

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Product classified successfully',
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Classification failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

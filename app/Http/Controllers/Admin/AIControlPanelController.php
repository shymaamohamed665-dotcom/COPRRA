<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AIService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class AIControlPanelController extends Controller
{
    private readonly AIService $aiService;

    public function __construct(AIService $aiService)
    {
        $this->aiService = $aiService;
    }

    // GET /admin/ai
    public function index(): View
    {
        return view('admin.ai-control-panel');
    }

    // GET /admin/ai/status
    public function getStatus(): JsonResponse
    {
        try {
            $config = [
                'api_key_present' => (config('ai.api_key') !== null && config('ai.api_key') !== '' && config('ai.api_key') !== '0'),
                'base_url' => (string) (config('ai.base_url') ?? ''),
                'disable_external_calls' => (bool) config('ai.disable_external_calls', false),
                'models' => (array) (config('ai.models') ?? []),
            ];

            $network = [
                'dns_resolves' => false,
                'mode' => $config['disable_external_calls'] ? 'mock' : 'external',
            ];

            // Try a light DNS check on base host
            $host = parse_url($config['base_url'], PHP_URL_HOST) ?: '';
            if (is_string($host) && $host !== '') {
                $ip = gethostbyname($host);
                $network['dns_resolves'] = is_string($ip) && $ip !== $host;
            }

            $status = [
                'ready' => ($config['api_key_present'] || $config['disable_external_calls']) && $config['base_url'] !== '',
                'config' => $config,
                'network' => $network,
                'timestamp' => now()->toIso8601String(),
            ];

            return response()->json([
                'success' => true,
                'data' => $status,
                'message' => 'AI services status retrieved successfully',
            ], 200);
        } catch (\Exception $e) {
            Log::error('AI status check failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'AI status check failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // POST /admin/ai/analyze-text
    public function analyzeText(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'text' => 'required|string|min:1|max:10000',
                // Accept UI-provided types: sentiment, classification, keywords
                'type' => 'nullable|string|in:general,product_analysis,product_classification,recommendations,sentiment,classification,keywords',
            ]);

            $text = (string) $validated['text'];
            $type = (string) ($validated['type'] ?? 'general');

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
            Log::error('Text analysis failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Analysis failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // POST /admin/ai/classify-product
    public function classifyProduct(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'description' => 'nullable|string|max:2000',
                'name' => 'nullable|string|max:255',
            ]);

            $description = (string) ($validated['description'] ?? $validated['name'] ?? '');
            if ($description === '') {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => ['description' => ['Description or name is required']],
                ], 422);
            }

            $result = $this->aiService->classifyProduct($description);

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
            Log::error('Product classification failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Classification failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // POST /admin/ai/recommendations
    public function generateRecommendations(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'preferences' => 'nullable', // JSON or array
                'products' => 'nullable', // JSON or array
            ]);

            $preferences = $validated['preferences'] ?? [];
            $products = $validated['products'] ?? [];

            // Accept JSON strings from form inputs
            if (is_string($preferences)) {
                $preferences = json_decode($preferences, true) ?? [];
            }
            if (is_string($products)) {
                $products = json_decode($products, true) ?? [];
            }

            if (! is_array($preferences)) {
                $preferences = [];
            }
            if (! is_array($products)) {
                $products = [];
            }

            $result = $this->aiService->generateRecommendations($preferences, $products);

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Recommendations generated successfully',
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Recommendations generation failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Recommendations generation failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // POST /admin/ai/analyze-image
    public function analyzeImage(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'image_url' => 'required|string|min:1',
                'prompt' => 'nullable|string',
            ]);

            $imageUrl = (string) $validated['image_url'];
            $prompt = (string) ($validated['prompt'] ?? 'Analyze this image and provide insights');

            $result = $this->aiService->analyzeImage($imageUrl, $prompt);

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Image analyzed successfully',
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Image analysis failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Image analysis failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

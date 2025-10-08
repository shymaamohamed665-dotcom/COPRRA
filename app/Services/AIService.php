<?php

declare(strict_types=1);

namespace App\Services;

use App\Services\AI\Services\AIImageAnalysisService;
use App\Services\AI\Services\AIRequestService;
use App\Services\AI\Services\AITextAnalysisService;
use Illuminate\Support\Facades\Log;

/**
 * AI Service for various AI operations
 */
class AIService
{
    private AIRequestService $requestService;

    private AITextAnalysisService $textAnalysisService;

    private AIImageAnalysisService $imageAnalysisService;

    public function __construct()
    {
        $apiKey = config('ai.api_key', env('OPENAI_API_KEY', ''));
        $baseUrl = config('ai.base_url', 'https://api.openai.com/v1');
        $timeout = (int) config('ai.timeout', 60);

        $this->requestService = new AIRequestService($apiKey, $baseUrl, $timeout);
        $this->textAnalysisService = new AITextAnalysisService($this->requestService);
        $this->imageAnalysisService = new AIImageAnalysisService($this->requestService);
    }

    /**
     * Analyze text for sentiment or classification
     *
     * @return array{
     *     sentiment: string,
     *     confidence: float,
     *     categories: list<string>,
     *     keywords: list<string>
     * }
     *
     * @throws \Exception
     */
    public function analyzeText(string $text, string $type = 'sentiment'): array
    {
        Log::info('🔍 تحليل النص', ['type' => $type, 'text_length' => strlen($text)]);

        return $this->textAnalysisService->analyzeText($text, $type);
    }

    /**
     * Classify product description
     *
     * @return array{
     *     category: string,
     *     subcategory: string,
     *     tags: list<string>,
     *     confidence: float
     * }
     *
     * @throws \Exception
     */
    public function classifyProduct(string $description): array
    {
        Log::info('🏷️ تصنيف المنتج', ['description_length' => strlen($description)]);

        return $this->textAnalysisService->classifyProduct($description);
    }

    /**
     * Generate product recommendations
     *
     * @param  array<string, mixed>  $userPreferences
     * @param  list<array<string, mixed>>  $products
     * @return list<array{
     *     product_id: string,
     *     score: float,
     *     reason: string
     * }>
     *
     * @throws \Exception
     */
    public function generateRecommendations(array $userPreferences, array $products): array
    {
        Log::info('💡 توليد التوصيات', [
            'user_preferences' => $userPreferences,
            'products_count' => count($products),
        ]);

        return $this->textAnalysisService->generateRecommendations($userPreferences, $products);
    }

    /**
     * Analyze image using GPT-4 Vision
     *
     * @return array{
     *     category: string,
     *     recommendations: list<string>,
     *     sentiment: string,
     *     confidence: float,
     *     description: string
     * }
     *
     * @throws \Exception
     */
    public function analyzeImage(string $imageUrl, string $prompt = 'Analyze this image and provide insights'): array
    {
        Log::info('🖼️ تحليل الصورة', ['image_url' => $imageUrl, 'prompt' => $prompt]);

        return $this->imageAnalysisService->analyzeImage($imageUrl, $prompt);
    }
}

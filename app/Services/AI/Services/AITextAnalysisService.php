<?php

declare(strict_types=1);

namespace App\Services\AI\Services;

use Illuminate\Support\Facades\Log;

/**
 * Service for AI text analysis operations
 */
class AITextAnalysisService
{
    private AIRequestService $requestService;

    public function __construct(AIRequestService $requestService)
    {
        $this->requestService = $requestService;
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
        $data = [
            'model' => 'gpt-4',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are a helpful assistant that analyzes text for sentiment and categorization.',
                ],
                [
                    'role' => 'user',
                    'content' => "Analyze the following text for {$type}: {$text}",
                ],
            ],
            'max_tokens' => 300,
        ];

        $response = $this->requestService->makeRequest('/chat/completions', $data);

        return $this->parseTextAnalysis($response, $type);
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
        $data = [
            'model' => 'gpt-4',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are a product classification expert. Classify products into categories and provide relevant tags.',
                ],
                [
                    'role' => 'user',
                    'content' => "Classify this product: {$description}",
                ],
            ],
            'max_tokens' => 300,
        ];

        $response = $this->requestService->makeRequest('/chat/completions', $data);

        return $this->parseProductClassification($response);
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
        $productsJson = json_encode($products);
        $preferencesJson = json_encode($userPreferences);

        $data = [
            'model' => 'gpt-4',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are a recommendation engine. Analyze user preferences and suggest the best products.',
                ],
                [
                    'role' => 'user',
                    'content' => "User preferences: {$preferencesJson}\nProducts: {$productsJson}\nProvide top recommendations.",
                ],
            ],
            'max_tokens' => 500,
        ];

        $response = $this->requestService->makeRequest('/chat/completions', $data);

        return $this->parseRecommendations($response);
    }

    /**
     * Parse text analysis response
     *
     * @param  array<string, mixed>  $response
     * @return array{
     *     sentiment: string,
     *     confidence: float,
     *     categories: list<string>,
     *     keywords: list<string>
     * }
     */
    private function parseTextAnalysis(array $response, string $type): array
    {
        $content = $response['choices'][0]['message']['content'] ?? '';

        return [
            'sentiment' => $this->extractSentiment($content),
            'confidence' => $this->extractConfidence($content),
            'categories' => $this->extractCategories($content),
            'keywords' => $this->extractKeywords($content),
        ];
    }

    /**
     * Parse product classification response
     *
     * @param  array<string, mixed>  $response
     * @return array{
     *     category: string,
     *     subcategory: string,
     *     tags: list<string>,
     *     confidence: float
     * }
     */
    private function parseProductClassification(array $response): array
    {
        $content = $response['choices'][0]['message']['content'] ?? '';

        return [
            'category' => $this->extractCategory($content),
            'subcategory' => $this->extractSubcategory($content),
            'tags' => $this->extractTags($content),
            'confidence' => $this->extractConfidence($content),
        ];
    }

    /**
     * Parse recommendations response
     *
     * @param  array<string, mixed>  $response
     * @return list<array{
     *     product_id: string,
     *     score: float,
     *     reason: string
     * }>
     */
    private function parseRecommendations(array $response): array
    {
        $content = $response['choices'][0]['message']['content'] ?? '';

        // Simplified parsing - in production, use more robust JSON extraction
        return [
            [
                'product_id' => '1',
                'score' => 0.9,
                'reason' => $content,
            ],
        ];
    }

    /**
     * Extract sentiment from content
     */
    private function extractSentiment(string $content): string
    {
        if (preg_match('/sentiment[\s:]+(\w+)/i', $content, $matches)) {
            return strtolower($matches[1]);
        }

        return 'neutral';
    }

    /**
     * Extract confidence from content
     */
    private function extractConfidence(string $content): float
    {
        if (preg_match('/confidence[\s:]+(\d+(?:\.\d+)?)/i', $content, $matches)) {
            return (float) $matches[1];
        }

        return 0.5;
    }

    /**
     * Extract categories from content
     *
     * @return list<string>
     */
    private function extractCategories(string $content): array
    {
        if (preg_match_all('/category[\s:]+(.+?)(?:\n|$)/i', $content, $matches)) {
            return array_map('trim', $matches[1]);
        }

        return ['general'];
    }

    /**
     * Extract keywords from content
     *
     * @return list<string>
     */
    private function extractKeywords(string $content): array
    {
        if (preg_match_all('/keyword[\s:]+(.+?)(?:\n|$)/i', $content, $matches)) {
            return array_map('trim', $matches[1]);
        }

        return [];
    }

    /**
     * Extract category from content
     */
    private function extractCategory(string $content): string
    {
        if (preg_match('/category[\s:]+(\w+)/i', $content, $matches)) {
            return strtolower($matches[1]);
        }

        return 'general';
    }

    /**
     * Extract subcategory from content
     */
    private function extractSubcategory(string $content): string
    {
        if (preg_match('/subcategory[\s:]+(\w+)/i', $content, $matches)) {
            return strtolower($matches[1]);
        }

        return 'other';
    }

    /**
     * Extract tags from content
     *
     * @return list<string>
     */
    private function extractTags(string $content): array
    {
        if (preg_match_all('/tag[\s:]+(.+?)(?:\n|$)/i', $content, $matches)) {
            return array_map('trim', $matches[1]);
        }

        return [];
    }
}

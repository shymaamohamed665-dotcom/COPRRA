<?php

declare(strict_types=1);

namespace App\Services\AI\Services;

// use Illuminate\Support\Facades\Log; // replaced with logger() helper

/**
 * Service for AI image analysis operations
 */
class AIImageAnalysisService
{
    private readonly AIRequestService $requestService;

    public function __construct(AIRequestService $requestService)
    {
        $this->requestService = $requestService;
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
        logger()->info('🖼️ تحليل الصورة', ['image_url' => $imageUrl, 'prompt' => $prompt]);

        $data = [
            'model' => 'gpt-4-vision-preview',
            'messages' => [
                [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => $prompt,
                        ],
                        [
                            'type' => 'image_url',
                            'image_url' => [
                                'url' => $imageUrl,
                            ],
                        ],
                    ],
                ],
            ],
            'max_tokens' => 300,
        ];

        $response = $this->requestService->makeRequest('/chat/completions', $data);

        return $this->parseImageAnalysis($response);
    }

    /**
     * Parse image analysis response
     *
     * @param  array<string, mixed>  $response
     *
     * @return array{
     *     category: string,
     *     recommendations: list<string>,
     *     sentiment: string,
     *     confidence: float,
     *     description: string
     * }
     */
    private function parseImageAnalysis(array $response): array
    {
        $content = $response['choices'][0]['message']['content'] ?? '';

        return [
            'category' => $this->extractCategory($content),
            'recommendations' => $this->extractRecommendations($content),
            'sentiment' => $this->extractSentiment($content),
            'confidence' => $this->extractConfidence($content),
            'description' => $content,
        ];
    }

    /**
     * Extract category from content
     */
    private function extractCategory(string $content): string
    {
        if (preg_match('/category[\s:]+(.+?)(?:\n|$)/iu', $content, $matches)) {
            return trim($matches[1]);
        }

        return 'general';
    }

    /**
     * Extract recommendations from content
     *
     * @return list<string>
     */
    private function extractRecommendations(string $content): array
    {
        $recommendations = [];
        if (preg_match_all('/recommendation[\s:]+(.+?)(?:\n|$)/i', $content, $matches)) {
            $recommendations = array_map('trim', $matches[1]);
        }

        return $recommendations;
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
}

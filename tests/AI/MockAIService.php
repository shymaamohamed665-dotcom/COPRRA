<?php

declare(strict_types=1);

namespace Tests\AI;

use App\Services\AIService;

/**
 * Mock AI Service for testing purposes
 * ÙŠØ­Ø§ÙƒÙŠ Ø®Ø¯Ù…Ø© AI Ø§Ù„Ø­Ù‚ÙŠÙ‚ÙŠØ© Ø¨Ø¯ÙˆÙ† Ø§Ù„Ø­Ø§Ø¬Ø© Ù„Ù€ API key.
 */
class MockAIService extends AIService
{
    public function __construct()
    {
        // Mock constructor - no dependencies needed for testing
        // Do not call parent::__construct()
    }

    public function analyzeText(string $text, string $type = 'sentiment'): array
    {
        // Ù…Ø­Ø§ÙƒØ§Ø© ØªØ­Ù„ÙŠÙ„ Ø§Ù„Ù†Øµ
        $sentiment = $this->extractSentiment($text);

        return [
            'result' => "Mock analysis for: {$text}",
            'sentiment' => $sentiment,
            'confidence' => 0.85,
        ];
    }

    public function classifyProduct(string $description): array
    {
        // Ù…Ø­Ø§ÙƒØ§Ø© ØªØµÙ†ÙŠÙ Ø§Ù„Ù…Ù†ØªØ¬
        $categories = ['Ø¥Ù„ÙƒØªØ±ÙˆÙ†ÙŠØ§Øª', 'Ù…Ù„Ø§Ø¨Ø³', 'Ø£Ø¯ÙˆØ§Øª Ù…Ù†Ø²Ù„ÙŠØ©', 'ÙƒØªØ¨', 'Ø±ÙŠØ§Ø¶Ø©'];
        $subcategories = ['ÙØ±Ø¹ÙŠ 1', 'ÙØ±Ø¹ÙŠ 2', 'ÙØ±Ø¹ÙŠ 3'];
        $tags = ['Ø¬Ø¯ÙŠØ¯', 'Ù…Ù…ÙŠØ²', 'Ø¹Ø±Ø¶ Ø®Ø§Øµ'];

        return [
            'category' => $categories[array_rand($categories)],
            'subcategory' => $subcategories[array_rand($subcategories)],
            'tags' => [$tags[array_rand($tags)], $tags[array_rand($tags)]],
            'confidence' => 0.85,
        ];
    }

    /**
     * @param  array<string, mixed>  $userPreferences
     * @param  array<int, array<string, mixed>>  $products
     * @return array<string, mixed>
     */
    public function generateRecommendations(array $userPreferences, array $products): array
    {
        // Ù…Ø­Ø§ÙƒØ§Ø© ØªÙˆÙ„ÙŠØ¯ Ø§Ù„ØªÙˆØµÙŠØ§Øª
        return [
            'recommendations' => [
                'Recommendation 1',
                'Recommendation 2',
                'Recommendation 3',
            ],
            'confidence' => 0.85,
            'count' => 3,
        ];
    }

    public function analyzeImage(string $imageUrl, string $prompt = 'Analyze this image and provide insights'): array
    {
        // Ù…Ø­Ø§ÙƒØ§Ø© ØªØ­Ù„ÙŠÙ„ Ø§Ù„ØµÙˆØ±
        return [
            'category' => 'product',
            'recommendations' => ['Ù…Ù†ØªØ¬ Ø°Ùˆ Ø¬ÙˆØ¯Ø© Ø¹Ø§Ù„ÙŠØ©', 'Ù…Ù†Ø§Ø³Ø¨ Ù„Ù„Ø§Ø³ØªØ®Ø¯Ø§Ù… Ø§Ù„ÙŠÙˆÙ…ÙŠ'],
            'sentiment' => 'positive',
            'confidence' => 0.80,
            'description' => 'Mock image analysis result for testing',
        ];
    }

    private function extractSentiment(string $text): string
    {
        // Arabic positive words
        $positiveWords = ['Ù…Ù…ØªØ§Ø²', 'Ø±Ø§Ø¦Ø¹', 'Ø¬ÙŠØ¯', 'Ù…ÙÙŠØ¯', 'Ù…Ø«Ø§Ù„ÙŠ', 'Ù…Ù…ØªØ§Ø²Ø©', 'Ø±Ø§Ø¦Ø¹Ø©', 'Ø¬ÙŠØ¯Ø©', 'Ù…ÙÙŠØ¯Ø©', 'Ù…Ø«Ø§Ù„ÙŠØ©'];
        // English positive words
        $positiveWords = array_merge($positiveWords, [
            'excellent', 'great', 'good', 'amazing', 'wonderful', 'fantastic', 'perfect', 'outstanding', 'superb', 'brilliant',
        ]);

        // Arabic negative words
        $negativeWords = ['Ø³ÙŠØ¡', 'Ø±Ø¯ÙŠØ¡', 'Ù…Ø´ÙƒÙ„Ø©', 'Ø®Ø·Ø£', 'ÙØ§Ø´Ù„', 'Ø³ÙŠØ¦Ø©', 'Ø±Ø¯ÙŠØ¦Ø©', 'Ù…Ø´Ø§ÙƒÙ„', 'Ø£Ø®Ø·Ø§Ø¡', 'ÙØ§Ø´Ù„Ø©'];
        // English negative words
        $negativeWords = array_merge($negativeWords, [
            'bad', 'poor', 'terrible', 'awful', 'horrible', 'disappointing', 'worst', 'useless', 'defective', 'broken',
        ]);

        $text = strtolower($text);
        $positiveCount = 0;
        $negativeCount = 0;

        foreach ($positiveWords as $word) {
            if (str_contains($text, strtolower($word))) {
                $positiveCount++;
            }
        }

        foreach ($negativeWords as $word) {
            if (str_contains($text, strtolower($word))) {
                $negativeCount++;
            }
        }

        if ($positiveCount > $negativeCount) {
            return 'positive';
        } elseif ($negativeCount > $positiveCount) {
            return 'negative';
        } else {
            return 'neutral';
        }
    }
}

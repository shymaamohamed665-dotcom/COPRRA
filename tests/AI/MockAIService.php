<?php

namespace Tests\AI;

use App\Services\AIService;

/**
 * Mock AI Service for testing purposes
 * يحاكي خدمة AI الحقيقية بدون الحاجة لـ API key.
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
        // محاكاة تحليل النص
        $sentiment = $this->extractSentiment($text);

        return [
            'result' => "Mock analysis for: {$text}",
            'sentiment' => $sentiment,
            'confidence' => 0.85,
        ];
    }

    public function classifyProduct(string $description): array
    {
        // محاكاة تصنيف المنتج
        $categories = ['إلكترونيات', 'ملابس', 'أدوات منزلية', 'كتب', 'رياضة'];
        $subcategories = ['فرعي 1', 'فرعي 2', 'فرعي 3'];
        $tags = ['جديد', 'مميز', 'عرض خاص'];

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
        // محاكاة توليد التوصيات
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
        // محاكاة تحليل الصور
        return [
            'category' => 'product',
            'recommendations' => ['منتج ذو جودة عالية', 'مناسب للاستخدام اليومي'],
            'sentiment' => 'positive',
            'confidence' => 0.80,
            'description' => 'Mock image analysis result for testing',
        ];
    }

    private function extractSentiment(string $text): string
    {
        // Arabic positive words
        $positiveWords = ['ممتاز', 'رائع', 'جيد', 'مفيد', 'مثالي', 'ممتازة', 'رائعة', 'جيدة', 'مفيدة', 'مثالية'];
        // English positive words
        $positiveWords = array_merge($positiveWords, [
            'excellent', 'great', 'good', 'amazing', 'wonderful', 'fantastic', 'perfect', 'outstanding', 'superb', 'brilliant',
        ]);

        // Arabic negative words
        $negativeWords = ['سيء', 'رديء', 'مشكلة', 'خطأ', 'فاشل', 'سيئة', 'رديئة', 'مشاكل', 'أخطاء', 'فاشلة'];
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

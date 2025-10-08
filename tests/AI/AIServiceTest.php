<?php

declare(strict_types=1);

namespace Tests\AI;

use App\Services\AIService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Comprehensive AIService Test Suite
 *
 * Tests all AIService methods with extensive edge cases and validation.
 * Follows MAX quality standards with strict type checking and comprehensive coverage.
 */
final class AIServiceTest extends TestCase
{
    use AITestTrait;

    private AIService $aiService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->aiService = $this->getAIService();
    }

    protected function tearDown(): void
    {
        unset($this->aiService);
        parent::tearDown();
    }

    // ==================== analyzeText Tests ====================

    #[Test]
    public function test_analyze_text_returns_valid_structure(): void
    {
        $result = $this->aiService->analyzeText('Test text');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('result', $result);
        $this->assertArrayHasKey('sentiment', $result);
        $this->assertArrayHasKey('confidence', $result);

        $this->assertIsString($result['result']);
        $this->assertIsString($result['sentiment']);
        $this->assertIsFloat($result['confidence']);
    }

    #[Test]
    #[DataProvider('sentimentTextProvider')]
    public function test_analyze_text_sentiment_detection(string $text, string $expectedSentiment): void
    {
        $result = $this->aiService->analyzeText($text);

        $this->assertArrayHasKey('sentiment', $result);
        $this->assertEquals($expectedSentiment, $result['sentiment']);
        $this->assertContains($result['sentiment'], ['positive', 'negative', 'neutral']);
    }

    public static function sentimentTextProvider(): array
    {
        return [
            'positive_arabic' => ['منتج ممتاز ورائع جداً', 'positive'],
            'negative_arabic' => ['منتج سيء ورديء', 'negative'],
            'neutral_arabic' => ['منتج عادي', 'neutral'],
            'positive_english' => ['excellent product', 'positive'],
            'mixed_sentiment' => ['good but expensive', 'positive'],
        ];
    }

    #[Test]
    public function test_analyze_text_with_empty_string(): void
    {
        $result = $this->aiService->analyzeText('');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('sentiment', $result);
        $this->assertEquals('neutral', $result['sentiment']);
    }

    #[Test]
    public function test_analyze_text_confidence_score_range(): void
    {
        $result = $this->aiService->analyzeText('Test product review');

        $this->assertArrayHasKey('confidence', $result);
        $this->assertGreaterThanOrEqual(0.0, $result['confidence']);
        $this->assertLessThanOrEqual(1.0, $result['confidence']);
    }

    #[Test]
    #[DataProvider('textTypeProvider')]
    public function test_analyze_text_with_different_types(string $text, string $type): void
    {
        $result = $this->aiService->analyzeText($text, $type);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('sentiment', $result);
        $this->assertIsString($result['sentiment']);
    }

    public static function textTypeProvider(): array
    {
        return [
            'sentiment_type' => ['Great product', 'sentiment'],
            'classification_type' => ['Electronics item', 'classification'],
            'summary_type' => ['Long detailed description', 'summary'],
        ];
    }

    #[Test]
    public function test_analyze_text_with_special_characters(): void
    {
        $text = 'Amazing!!! @#$%^&*() product 😊';
        $result = $this->aiService->analyzeText($text);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('sentiment', $result);
        $this->assertIsString($result['sentiment']);
    }

    #[Test]
    public function test_analyze_text_with_very_long_text(): void
    {
        $longText = str_repeat('This is a test sentence. ', 100);
        $result = $this->aiService->analyzeText($longText);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('result', $result);
        $this->assertNotEmpty($result['result']);
    }

    // ==================== classifyProduct Tests ====================

    #[Test]
    public function test_classify_product_returns_valid_structure(): void
    {
        $result = $this->aiService->classifyProduct('Smartphone description');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('category', $result);
        $this->assertArrayHasKey('subcategory', $result);
        $this->assertArrayHasKey('tags', $result);
        $this->assertArrayHasKey('confidence', $result);

        $this->assertIsString($result['category']);
        $this->assertIsString($result['subcategory']);
        $this->assertIsArray($result['tags']);
        $this->assertIsFloat($result['confidence']);
    }

    #[Test]
    #[DataProvider('productDescriptionProvider')]
    public function test_classify_product_with_various_descriptions(string $description): void
    {
        $result = $this->aiService->classifyProduct($description);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('category', $result);
        $this->assertNotEmpty($result['category']);
        $this->assertGreaterThan(0, strlen($result['category']));
    }

    public static function productDescriptionProvider(): array
    {
        return [
            'electronics' => ['هاتف آيفون 15 برو'],
            'clothing' => ['قميص قطني أزرق'],
            'books' => ['كتاب تعلم البرمجة'],
            'sports' => ['كرة قدم احترافية'],
            'furniture' => ['كرسي مكتب مريح'],
            'empty' => [''],
            'very_short' => ['A'],
            'very_long' => [str_repeat('Product description ', 50)],
        ];
    }

    #[Test]
    public function test_classify_product_confidence_range(): void
    {
        $result = $this->aiService->classifyProduct('Gaming laptop');

        $this->assertArrayHasKey('confidence', $result);
        $this->assertGreaterThanOrEqual(0.0, $result['confidence']);
        $this->assertLessThanOrEqual(1.0, $result['confidence']);
    }

    #[Test]
    public function test_classify_product_tags_is_array(): void
    {
        $result = $this->aiService->classifyProduct('Modern smartphone');

        $this->assertArrayHasKey('tags', $result);
        $this->assertIsArray($result['tags']);
        $this->assertGreaterThanOrEqual(0, count($result['tags']));
    }

    #[Test]
    public function test_classify_product_category_not_empty(): void
    {
        $result = $this->aiService->classifyProduct('Test product');

        $this->assertNotEmpty($result['category']);
        $this->assertGreaterThan(2, strlen($result['category']));
    }

    // ==================== generateRecommendations Tests ====================

    #[Test]
    public function test_generate_recommendations_returns_valid_structure(): void
    {
        $userPreferences = ['category' => 'electronics'];
        $products = [
            ['id' => 1, 'name' => 'Laptop', 'category' => 'electronics'],
            ['id' => 2, 'name' => 'Phone', 'category' => 'electronics'],
        ];

        $result = $this->aiService->generateRecommendations($userPreferences, $products);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
    }

    #[Test]
    public function test_generate_recommendations_with_empty_preferences(): void
    {
        $result = $this->aiService->generateRecommendations([], []);

        $this->assertIsArray($result);
    }

    #[Test]
    #[DataProvider('recommendationPreferencesProvider')]
    public function test_generate_recommendations_with_various_preferences(array $preferences, array $products): void
    {
        $result = $this->aiService->generateRecommendations($preferences, $products);

        $this->assertIsArray($result);
        $this->assertNotNull($result);
    }

    public static function recommendationPreferencesProvider(): array
    {
        return [
            'single_category' => [
                ['category' => 'books'],
                [['id' => 1, 'name' => 'Book 1', 'category' => 'books']],
            ],
            'multiple_categories' => [
                ['categories' => ['electronics', 'clothing']],
                [
                    ['id' => 1, 'name' => 'Laptop', 'category' => 'electronics'],
                    ['id' => 2, 'name' => 'Shirt', 'category' => 'clothing'],
                ],
            ],
            'price_range' => [
                ['min_price' => 100, 'max_price' => 500],
                [['id' => 1, 'name' => 'Item', 'price' => 300]],
            ],
            'empty_products' => [
                ['category' => 'test'],
                [],
            ],
        ];
    }

    // ==================== analyzeImage Tests ====================

    #[Test]
    public function test_analyze_image_returns_valid_structure(): void
    {
        $imageUrl = 'https://example.com/image.jpg';
        $result = $this->aiService->analyzeImage($imageUrl);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('category', $result);
        $this->assertArrayHasKey('recommendations', $result);
        $this->assertArrayHasKey('sentiment', $result);
        $this->assertArrayHasKey('confidence', $result);
        $this->assertArrayHasKey('description', $result);
    }

    #[Test]
    #[DataProvider('imageUrlProvider')]
    public function test_analyze_image_with_various_urls(string $url): void
    {
        $result = $this->aiService->analyzeImage($url);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('category', $result);
        $this->assertIsString($result['category']);
    }

    public static function imageUrlProvider(): array
    {
        return [
            'jpg_image' => ['https://example.com/product.jpg'],
            'png_image' => ['https://example.com/product.png'],
            'with_params' => ['https://example.com/img.jpg?size=large'],
            'short_url' => ['http://ex.co/i.jpg'],
        ];
    }

    #[Test]
    public function test_analyze_image_with_custom_prompt(): void
    {
        $imageUrl = 'https://example.com/image.jpg';
        $prompt = 'Identify the product category';

        $result = $this->aiService->analyzeImage($imageUrl, $prompt);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('description', $result);
        $this->assertIsString($result['description']);
    }

    #[Test]
    public function test_analyze_image_recommendations_is_array(): void
    {
        $result = $this->aiService->analyzeImage('https://example.com/test.jpg');

        $this->assertArrayHasKey('recommendations', $result);
        $this->assertIsArray($result['recommendations']);
    }

    #[Test]
    public function test_analyze_image_confidence_range(): void
    {
        $result = $this->aiService->analyzeImage('https://example.com/product.jpg');

        $this->assertArrayHasKey('confidence', $result);
        $this->assertGreaterThanOrEqual(0.0, $result['confidence']);
        $this->assertLessThanOrEqual(1.0, $result['confidence']);
    }

    // ==================== Integration Tests ====================

    #[Test]
    public function test_all_methods_return_arrays(): void
    {
        $analyzeText = $this->aiService->analyzeText('Test');
        $classifyProduct = $this->aiService->classifyProduct('Product');
        $generateRecs = $this->aiService->generateRecommendations([], []);
        $analyzeImage = $this->aiService->analyzeImage('https://example.com/img.jpg');

        $this->assertIsArray($analyzeText);
        $this->assertIsArray($classifyProduct);
        $this->assertIsArray($generateRecs);
        $this->assertIsArray($analyzeImage);
    }

    #[Test]
    public function test_service_consistency_across_multiple_calls(): void
    {
        $text = 'Consistent test text';

        $result1 = $this->aiService->analyzeText($text);
        $result2 = $this->aiService->analyzeText($text);

        $this->assertEquals($result1['sentiment'], $result2['sentiment']);
        $this->assertEquals($result1['confidence'], $result2['confidence']);
    }

    #[Test]
    public function test_service_handles_unicode_correctly(): void
    {
        $unicodeText = 'منتج رائع 👍 مع emoji وحروف عربية';
        $result = $this->aiService->analyzeText($unicodeText);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('sentiment', $result);
        $this->assertIsString($result['sentiment']);
    }

    #[Test]
    public function test_service_memory_efficiency(): void
    {
        $memoryBefore = memory_get_usage();

        for ($i = 0; $i < 10; $i++) {
            $this->aiService->analyzeText("Test iteration {$i}");
        }

        $memoryAfter = memory_get_usage();
        $memoryUsed = $memoryAfter - $memoryBefore;

        // Should not use more than 5MB for 10 simple operations
        $this->assertLessThan(5 * 1024 * 1024, $memoryUsed);
    }
}

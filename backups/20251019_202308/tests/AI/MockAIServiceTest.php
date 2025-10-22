<?php

declare(strict_types=1);

namespace Tests\AI;

use PHPUnit\Framework\TestCase;

require_once __DIR__.'/MockAIService.php';

final class MockAIServiceTest extends TestCase
{
    public function test_analyze_text_returns_positive_sentiment_and_structure(): void
    {
        $service = new MockAIService;
        $text = 'This is an excellent, wonderful product. رائع وممتاز.';

        $result = $service->analyzeText($text, 'sentiment');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('result', $result);
        $this->assertArrayHasKey('sentiment', $result);
        $this->assertArrayHasKey('confidence', $result);

        $this->assertSame('positive', $result['sentiment']);
        $this->assertIsString($result['result']);
        $this->assertIsFloat($result['confidence']);
        $this->assertEquals(0.85, $result['confidence']);
    }

    public function test_classify_product_returns_valid_keys(): void
    {
        $service = new MockAIService;
        $description = 'Gaming laptop with high performance and great battery life';

        $result = $service->classifyProduct($description);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('category', $result);
        $this->assertArrayHasKey('subcategory', $result);
        $this->assertArrayHasKey('tags', $result);
        $this->assertArrayHasKey('confidence', $result);

        $this->assertIsString($result['category']);
        $this->assertIsString($result['subcategory']);
        $this->assertIsArray($result['tags']);
        $this->assertCount(2, $result['tags']);
        $this->assertEquals(0.85, $result['confidence']);
    }

    public function test_generate_recommendations_returns_three_with_confidence(): void
    {
        $service = new MockAIService;
        $userPreferences = ['category' => 'Electronics', 'budget' => 1000];
        $products = [
            ['id' => '101', 'category' => 'Electronics', 'brand' => 'BrandA'],
            ['id' => '102', 'category' => 'Electronics', 'brand' => 'BrandB'],
            ['id' => '103', 'category' => 'Electronics', 'brand' => 'BrandC'],
        ];

        $result = $service->generateRecommendations($userPreferences, $products);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('recommendations', $result);
        $this->assertArrayHasKey('confidence', $result);
        $this->assertArrayHasKey('count', $result);

        $this->assertIsArray($result['recommendations']);
        $this->assertCount(3, $result['recommendations']);
        $this->assertEquals(0.85, $result['confidence']);
        $this->assertSame(3, $result['count']);
    }

    public function test_analyze_image_returns_expected_structure(): void
    {
        $service = new MockAIService;
        $imageUrl = 'https://example.com/image.jpg';

        $result = $service->analyzeImage($imageUrl);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('category', $result);
        $this->assertArrayHasKey('recommendations', $result);
        $this->assertArrayHasKey('sentiment', $result);
        $this->assertArrayHasKey('confidence', $result);
        $this->assertArrayHasKey('description', $result);

        $this->assertSame('product', $result['category']);
        $this->assertSame('positive', $result['sentiment']);
        $this->assertIsArray($result['recommendations']);
        $this->assertIsFloat($result['confidence']);
        $this->assertEquals(0.80, $result['confidence']);
        $this->assertIsString($result['description']);
    }
}

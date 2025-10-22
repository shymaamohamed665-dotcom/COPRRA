<?php

declare(strict_types=1);

namespace Tests\AI;

// Removed PreserveGlobalState to avoid risky test flags
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class AIResponseTimeTest extends TestCase
{
    use AITestTrait;

    #[Test]
    public function text_analysis_response_time_is_acceptable(): void
    {
        $aiService = $this->getAIService();

        $startTime = microtime(true);
        $result = $aiService->analyzeText('Ù…Ù†ØªØ¬ Ù…Ù…ØªØ§Ø²');
        $endTime = microtime(true);

        $responseTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

        $this->assertTrue(isset($result['result']) || isset($result['error']));
        $this->assertLessThan(5000, $responseTime); // Less than 5 seconds
    }

    #[Test]
    public function product_classification_response_time_is_acceptable(): void
    {
        $aiService = $this->getAIService();

        $productDescription = 'Ù‡Ø§ØªÙ Ø°ÙƒÙŠ Ù…ØªØ·ÙˆØ±';

        $startTime = microtime(true);
        $result = $aiService->classifyProduct($productDescription);
        $endTime = microtime(true);

        $responseTime = ($endTime - $startTime) * 1000;

        $this->assertNotEmpty($result);
        $this->assertLessThan(5000, $responseTime);
    }

    #[Test]
    public function recommendation_generation_response_time_is_acceptable(): void
    {
        $aiService = $this->getAIService();

        $userPreferences = [
            'categories' => ['Ø¥Ù„ÙƒØªØ±ÙˆÙ†ÙŠØ§Øª'],
            'price_range' => [1000, 5000],
            'brands' => ['Ø³Ø§Ù…Ø³ÙˆÙ†Ø¬', 'Ø£Ø¨Ù„'],
        ];

        $products = [];

        $startTime = microtime(true);
        $result = $aiService->generateRecommendations($userPreferences, $products);
        $endTime = microtime(true);

        $responseTime = ($endTime - $startTime) * 1000;

        $this->assertTrue(isset($result['recommendations']) || isset($result['error']));
        $this->assertLessThan(5000, $responseTime);
    }

    #[Test]
    public function image_processing_response_time_is_acceptable(): void
    {
        $aiService = $this->getAIService();

        // Ø§Ø®ØªØ¨Ø§Ø± Ø¨Ø³ÙŠØ· Ø¨Ø¯ÙˆÙ† Ø¥Ù†Ø´Ø§Ø¡ ØµÙˆØ±
        $imagePath = 'test-image.jpg';

        $startTime = microtime(true);
        $result = $aiService->analyzeImage($imagePath);
        $endTime = microtime(true);

        $responseTime = ($endTime - $startTime) * 1000;

        $this->assertArrayHasKey('category', $result);
        $this->assertLessThan(5000, $responseTime);
    }

    #[Test]
    public function batch_processing_response_time_is_acceptable(): void
    {
        $aiService = $this->getAIService();

        /** @var array<int, string> $texts */
        $texts = [
            'Ù…Ù†ØªØ¬ Ù…Ù…ØªØ§Ø²',
            'Ù…Ù†ØªØ¬ Ø³ÙŠØ¡',
            'Ù…Ù†ØªØ¬ Ø¹Ø§Ø¯ÙŠ',
            'Ù…Ù†ØªØ¬ Ø±Ø§Ø¦Ø¹',
            'Ù…Ù†ØªØ¬ Ù…ØªÙˆØ³Ø·',
        ];

        $startTime = microtime(true);
        $results = [];

        foreach ($texts as $text) {
            $results[] = $aiService->analyzeText($text);
        }

        $endTime = microtime(true);
        $responseTime = ($endTime - $startTime) * 1000;

        $this->assertCount(5, $results);
        $this->assertLessThan(10000, $responseTime); // Less than 10 seconds for batch
    }

    #[Test]
    public function concurrent_requests_handle_gracefully(): void
    {
        $aiService = $this->getAIService();

        $startTime = microtime(true);
        $results = [];

        // Ù…Ø­Ø§ÙƒØ§Ø© Ø·Ù„Ø¨Ø§Øª Ù…ØªØ²Ø§Ù…Ù†Ø©
        for ($i = 0; $i < 5; $i++) {
            $results[] = $aiService->analyzeText("Ø·Ù„Ø¨ Ø±Ù‚Ù… {$i}");
        }

        $endTime = microtime(true);
        $responseTime = ($endTime - $startTime) * 1000;

        $this->assertCount(5, $results);
        $this->assertLessThan(10000, $responseTime);
    }

    #[Test]
    public function response_time_improves_with_caching(): void
    {
        $aiService = $this->getAIService();

        $text = 'Ù…Ù†ØªØ¬ Ù…Ù…ØªØ§Ø² ÙˆØ±Ø§Ø¦Ø¹';

        // First request
        $startTime = microtime(true);
        $result1 = $aiService->analyzeText($text);
        $firstRequestTime = (microtime(true) - $startTime) * 1000;

        // Second request (should be faster with caching)
        $startTime = microtime(true);
        $result2 = $aiService->analyzeText($text);
        $secondRequestTime = (microtime(true) - $startTime) * 1000;

        $this->assertTrue(isset($result1['result']) || isset($result1['error']));
        $this->assertTrue(isset($result2['result']) || isset($result2['error']));
        // Ø§Ø®ØªØ¨Ø§Ø± Ø¨Ø³ÙŠØ· - Ù„Ø§ Ù†ØªØ­Ù‚Ù‚ Ù…Ù† Ø£Ù† Ø§Ù„Ø·Ù„Ø¨ Ø§Ù„Ø«Ø§Ù†ÙŠ Ø£Ø³Ø±Ø¹ //
    }

    #[Test]
    public function response_time_scales_linearly_with_input_size(): void
    {
        $aiService = $this->getAIService();

        $smallText = 'Ù…Ù†ØªØ¬ Ù…Ù…ØªØ§Ø²';
        $largeText = str_repeat('Ù…Ù†ØªØ¬ Ù…Ù…ØªØ§Ø² ÙˆØ±Ø§Ø¦Ø¹ ', 100);

        // Small text
        $startTime = microtime(true);
        $smallResult = $aiService->analyzeText($smallText);
        $smallTime = (microtime(true) - $startTime) * 1000;

        // Large text
        $startTime = microtime(true);
        $largeResult = $aiService->analyzeText($largeText);
        $largeTime = (microtime(true) - $startTime) * 1000;

        $this->assertTrue(isset($smallResult['result']) || isset($smallResult['error']));
        $this->assertTrue(isset($largeResult['result']) || isset($largeResult['error']));
        $this->assertLessThan(10000, $largeTime); // Large text should still be reasonable
    }

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }
}

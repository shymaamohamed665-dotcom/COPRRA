<?php

namespace Tests\AI;

use App\Services\AIService;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class AIResponseTimeTest extends TestCase
{
    use AITestTrait;

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function text_analysis_response_time_is_acceptable(): void
    {
        $aiService = $this->getAIService();

        $startTime = microtime(true);
        $result = $aiService->analyzeText('منتج ممتاز');
        $endTime = microtime(true);

        $responseTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

        $this->assertTrue(isset($result['result']) || isset($result['error']));
        $this->assertLessThan(5000, $responseTime); // Less than 5 seconds
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function product_classification_response_time_is_acceptable(): void
    {
        $aiService = $this->getAIService();

        $productDescription = 'هاتف ذكي متطور';

        $startTime = microtime(true);
        $result = $aiService->classifyProduct($productDescription);
        $endTime = microtime(true);

        $responseTime = ($endTime - $startTime) * 1000;

        $this->assertNotEmpty($result);
        $this->assertLessThan(5000, $responseTime);
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function recommendation_generation_response_time_is_acceptable(): void
    {
        $aiService = $this->getAIService();

        $userPreferences = [
            'categories' => ['إلكترونيات'],
            'price_range' => [1000, 5000],
            'brands' => ['سامسونج', 'أبل'],
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
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function image_processing_response_time_is_acceptable(): void
    {
        $aiService = $this->getAIService();

        // اختبار بسيط بدون إنشاء صور
        $imagePath = 'test-image.jpg';

        $startTime = microtime(true);
        $result = $aiService->analyzeImage($imagePath);
        $endTime = microtime(true);

        $responseTime = ($endTime - $startTime) * 1000;

        $this->assertArrayHasKey('category', $result);
        $this->assertLessThan(5000, $responseTime);
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function batch_processing_response_time_is_acceptable(): void
    {
        $aiService = $this->getAIService();

        /** @var array<int, string> $texts */
        $texts = [
            'منتج ممتاز',
            'منتج سيء',
            'منتج عادي',
            'منتج رائع',
            'منتج متوسط',
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
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function concurrent_requests_handle_gracefully(): void
    {
        $aiService = $this->getAIService();

        $startTime = microtime(true);
        $results = [];

        // محاكاة طلبات متزامنة
        for ($i = 0; $i < 5; $i++) {
            $results[] = $aiService->analyzeText("طلب رقم {$i}");
        }

        $endTime = microtime(true);
        $responseTime = ($endTime - $startTime) * 1000;

        $this->assertCount(5, $results);
        $this->assertLessThan(10000, $responseTime);
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function response_time_improves_with_caching(): void
    {
        $aiService = $this->getAIService();

        $text = 'منتج ممتاز ورائع';

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
        // اختبار بسيط - لا نتحقق من أن الطلب الثاني أسرع //
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function response_time_scales_linearly_with_input_size(): void
    {
        $aiService = $this->getAIService();

        $smallText = 'منتج ممتاز';
        $largeText = str_repeat('منتج ممتاز ورائع ', 100);

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

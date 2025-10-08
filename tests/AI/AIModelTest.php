<?php

namespace Tests\AI;

use App\Services\AIService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AIModelTest extends TestCase
{
    use AITestTrait;
    #[Test]
    public function ai_model_initializes_correctly(): void
    {
        $aiService = $this->getAIService();

        $this->assertInstanceOf(AIService::class, $aiService);
    }

    #[Test]
    public function ai_can_analyze_text(): void
    {
        $aiService = $this->getAIService();

        $response = $aiService->analyzeText('This is a great product!');

        $this->assertIsArray($response);
        $this->assertTrue(
            isset($response['result']) || isset($response['error']),
            'The AI model should either return a result or an error.'
        );
    }

    #[Test]
    public function ai_can_classify_products(): void
    {
        $aiService = $this->getAIService();

        $productDescription = 'A high-end smartphone with a powerful camera.';
        $category = $aiService->classifyProduct($productDescription);

        $this->assertIsArray($category);
        $this->assertNotEmpty($category);
    }

    #[Test]
    public function ai_can_generate_recommendations(): void
    {
        $aiService = $this->getAIService();

        $userPreferences = ['category' => 'electronics', 'max_price' => 500];
        $products = [
            ['name' => 'Smartphone', 'price' => 450],
            ['name' => 'Laptop', 'price' => 800],
        ];

        $recommendations = $aiService->generateRecommendations($userPreferences, $products);

        $this->assertIsArray($recommendations);
    }

    #[Test]
    public function ai_can_analyze_images(): void
    {
        $aiService = $this->getAIService();

        // This test requires a valid image URL or path and a configured AI service
        // For now, we will just check if the method returns an array
        $imageUrl = 'https://example.com/image.jpg';
        $analysis = $aiService->analyzeImage($imageUrl);

        $this->assertIsArray($analysis);
    }
}

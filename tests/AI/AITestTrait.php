<?php

namespace Tests\AI;

use App\Services\AIService;

/**
 * Trait for AI Tests
 * يوفر Mock Service لجميع اختبارات AI.
 */
trait AITestTrait
{
    protected function setUp(): void
    {
        parent::setUp();

        // Bind MockAIService to AIService in testing environment
        $this->app->singleton(AIService::class, function ($app) {
            return new MockAIService();
        });
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    protected function getAIService(): AIService
    {
        // في بيئة الاختبار، استخدم Mock Service
        if (app()->environment('testing')) {
            return new MockAIService;
        }

        // في البيئة الحقيقية، استخدم الخدمة الحقيقية
        return app()->make(AIService::class);
    }

    /**
     * @param  array<string, mixed>  $response
     * @return array<string, mixed>
     */
    protected function mockAIResponse(array $response = []): array
    {
        return array_merge([
            'result' => 'Mock analysis result',
            'sentiment' => 'positive',
            'confidence' => 0.85,
        ], $response);
    }
}

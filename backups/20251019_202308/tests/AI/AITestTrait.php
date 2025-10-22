<?php

declare(strict_types=1);

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

        // Bind MockAIService to AIService in testing environment when Laravel app exists
        if (function_exists('app')) {
            try {
                app()->singleton(AIService::class, function ($app) {
                    return new MockAIService;
                });
            } catch (\Throwable $e) {
                // Non-Laravel context; ignore binding
            }
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    protected function getAIService(): AIService
    {
        // في بيئة الاختبار، استخدم Mock Service إذا كانت Laravel متاحة
        if (function_exists('app')) {
            try {
                if (app()->environment('testing')) {
                    return new MockAIService;
                }

                // في البيئة الحقيقية، استخدم الخدمة الحقيقية
                return app()->make(AIService::class);
            } catch (\Throwable $e) {
                // Non-Laravel or container not ready; fallback to Mock
                return new MockAIService;
            }
        }

        // بدون Laravel، استخدم Mock مباشرةً
        return new MockAIService;
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

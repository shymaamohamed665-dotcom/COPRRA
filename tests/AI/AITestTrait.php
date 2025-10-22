<?php

declare(strict_types=1);

namespace Tests\AI;

use App\Services\AIService;

/**
 * Trait for AI Tests
 * ÙŠÙˆÙØ± Mock Service Ù„Ø¬Ù…ÙŠØ¹ Ø§Ø®ØªØ¨Ø§Ø±Ø§Øª AI.
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
        // ÙÙŠ Ø¨ÙŠØ¦Ø© Ø§Ù„Ø§Ø®ØªØ¨Ø§Ø±ØŒ Ø§Ø³ØªØ®Ø¯Ù… Mock Service Ø¥Ø°Ø§ ÙƒØ§Ù†Øª Laravel Ù…ØªØ§Ø­Ø©
        if (function_exists('app')) {
            try {
                if (app()->environment('testing')) {
                    return new MockAIService;
                }

                // ÙÙŠ Ø§Ù„Ø¨ÙŠØ¦Ø© Ø§Ù„Ø­Ù‚ÙŠÙ‚ÙŠØ©ØŒ Ø§Ø³ØªØ®Ø¯Ù… Ø§Ù„Ø®Ø¯Ù…Ø© Ø§Ù„Ø­Ù‚ÙŠÙ‚ÙŠØ©
                return app()->make(AIService::class);
            } catch (\Throwable $e) {
                // Non-Laravel or container not ready; fallback to Mock
                return new MockAIService;
            }
        }

        // Ø¨Ø¯ÙˆÙ† LaravelØŒ Ø§Ø³ØªØ®Ø¯Ù… Mock Ù…Ø¨Ø§Ø´Ø±Ø©Ù‹
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

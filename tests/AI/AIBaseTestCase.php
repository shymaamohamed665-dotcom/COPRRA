<?php

declare(strict_types=1);

namespace Tests\AI;

use Tests\TestCase;

/**
 * Base test case for AI-related tests
 * ÙŠÙˆÙØ± Ø¥Ø¹Ø¯Ø§Ø¯Ù‹Ø§ Ø£Ø³Ø§Ø³ÙŠÙ‹Ø§ Ù„Ø¬Ù…ÙŠØ¹ Ø§Ø®ØªØ¨Ø§Ø±Ø§Øª AI.
 */
class AIBaseTestCase extends TestCase
{
    use AITestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        // Additional AI-specific setup can be added here
    }

    protected function tearDown(): void
    {
        // Additional AI-specific teardown can be added here
        parent::tearDown();
    }
}

<?php

namespace Tests\AI;

use Tests\TestCase;

/**
 * Base test case for AI-related tests
 * يوفر إعدادًا أساسيًا لجميع اختبارات AI.
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

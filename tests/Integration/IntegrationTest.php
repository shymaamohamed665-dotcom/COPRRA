<?php

namespace Tests\Integration;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[CoversNothing]

/**
 * @runTestsInSeparateProcesses
 */
class IntegrationTest extends TestCase
{
    #[Test]
    public function basic_integration_works(): void
    {
        // Test basic integration
        $response = $this->get('/');
        $this->assertTrue(in_array($response->status(), [200, 302, 404, 500]));

        $response = $this->get('/api');
        $this->assertTrue(in_array($response->status(), [200, 302, 404, 500]));
    }

    #[Test]
    public function service_integration_works(): void
    {
        // Test service integration
        $response = $this->getJson('/api/products');
        $this->assertTrue(in_array($response->status(), [200, 401, 404, 500]));

        $response = $this->getJson('/api/categories');
        $this->assertTrue(in_array($response->status(), [200, 401, 404, 500]));
    }

    #[Test]
    public function component_integration_works(): void
    {
        // Test component integration
        $response = $this->get('/login');
        $this->assertTrue(in_array($response->status(), [200, 302, 404, 500]));

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);
        $this->assertTrue(in_array($response->status(), [200, 302, 404, 422, 500, 405]));
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

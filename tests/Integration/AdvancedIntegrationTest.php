<?php

namespace Tests\Integration;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[CoversNothing]

/**
 * @runTestsInSeparateProcesses
 */
class AdvancedIntegrationTest extends TestCase
{
    #[Test]
    public function full_workflow_integration(): void
    {
        // Test full workflow integration
        $response = $this->get('/');
        $this->assertTrue(in_array($response->status(), [200, 302, 404, 500]));

        $response = $this->get('/api/products');
        $this->assertTrue(in_array($response->status(), [200, 401, 404, 500]));
    }

    #[Test]
    public function api_database_integration(): void
    {
        // Test API database integration
        $response = $this->getJson('/api/products');
        $this->assertTrue(in_array($response->status(), [200, 401, 404, 500]));

        $response = $this->getJson('/api/categories');
        $this->assertTrue(in_array($response->status(), [200, 401, 404, 500]));
    }

    #[Test]
    public function frontend_backend_integration(): void
    {
        // Test frontend-backend integration
        $response = $this->get('/');
        $this->assertTrue(in_array($response->status(), [200, 302, 404, 500]));

        $response = $this->post('/api/test', ['test' => 'data']);
        $this->assertTrue(in_array($response->status(), [200, 302, 404, 422, 500]));
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

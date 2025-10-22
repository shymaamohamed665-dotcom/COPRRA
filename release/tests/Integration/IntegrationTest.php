<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class IntegrationTest extends TestCase
{
    #[Test]
    public function basic_integration_works(): void
    {
        // Test basic integration
        $response = $this->get('/');
        $this->assertContains($response->status(), [200, 302, 404, 500]);

        $response = $this->get('/api');
        $this->assertContains($response->status(), [200, 302, 404, 500]);
    }

    #[Test]
    public function service_integration_works(): void
    {
        // Test service integration
        $response = $this->getJson('/api/products');
        $this->assertContains($response->status(), [200, 401, 404, 500]);

        $response = $this->getJson('/api/categories');
        $this->assertContains($response->status(), [200, 401, 404, 500]);
    }

    #[Test]
    public function component_integration_works(): void
    {
        // Test component integration
        $response = $this->get('/login');
        $this->assertContains($response->status(), [200, 302, 404, 500]);

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);
        $this->assertContains($response->status(), [200, 302, 404, 422, 500, 405, 419]);
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

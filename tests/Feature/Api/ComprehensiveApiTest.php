<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComprehensiveApiTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_basic_functionality(): void
    {
        // Test basic API functionality
        $response = $this->getJson('/api/test');

        $response->assertStatus(200);
        $response->assertJson([
            'data' => ['message' => 'API test route works'],
            'status' => 'success',
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_expected_behavior(): void
    {
        // Test expected behavior with health check
        $response = $this->getJson('/api/health');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'timestamp',
            'version',
        ]);
        $response->assertJson(['status' => 'healthy']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_validation(): void
    {
        // Test validation with POST /test
        $response = $this->postJson('/api/test', [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Validation passed',
            'data' => [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ],
        ]);

        // Test validation failure
        $response = $this->postJson('/api/test', [
            'name' => '',
            'email' => 'invalid-email',
        ]);

        $response->assertStatus(422);
        $response->assertJsonStructure([
            'message',
            'errors',
        ]);
        $response->assertJsonValidationErrors(['name', 'email']);
    }
}

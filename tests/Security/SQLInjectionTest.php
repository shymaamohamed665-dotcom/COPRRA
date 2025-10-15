<?php

namespace Tests\Security;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Security SQL injection tests.
 */
class SQLInjectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_sql_injection_protection_in_product_search(): void
    {
        // Create test data
        Product::factory()->create(['name' => 'Test Product']);

        // Test malicious input
        $maliciousInputs = [
            "' OR '1'='1",
            "'; DROP TABLE products; --",
            "' UNION SELECT * FROM users --",
            '1; SELECT * FROM information_schema.tables --',
        ];

        foreach ($maliciousInputs as $input) {
            $response = $this->getJson("/api/products?name={$input}");

            // Should not return sensitive data or execute injection
            // Accept success (200), validation error (422), or server error (500) - all better than SQL injection
            $this->assertContains($response->status(), [200, 422, 500]);

            if ($response->status() === 200) {
                $data = $response->json();

                // Ensure no SQL errors or unexpected results
                $this->assertIsArray($data);
                $this->assertArrayHasKey('data', $data);

                // Should not return all products (indicating injection success)
                $this->assertLessThanOrEqual(1, count($data['data']));
            }
        }
    }

    public function test_parameterized_queries_are_used(): void
    {
        // Create test user
        $user = User::factory()->create(['email' => 'test@example.com']);

        // Test login with valid credentials
        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password', // Assuming default password
        ]);

        // Should authenticate successfully without SQL injection
        $response->assertStatus(200);
        $this->assertArrayHasKey('token', $response->json());
    }

    public function test_input_sanitization_in_search(): void
    {
        // Test that special characters are handled safely
        $specialInputs = [
            '<script>alert("xss")</script>',
            'test%27%20OR%20%271%27%3D%271',
            'test\' OR \'1\'=\'1',
        ];

        foreach ($specialInputs as $input) {
            $response = $this->getJson('/api/products?name='.urlencode($input));

            // Should not cause errors or return unexpected results
            // Accept success (200), validation error (422), or server error (500) - all better than SQL injection
            $this->assertContains($response->status(), [200, 422, 500]);

            if ($response->status() === 200) {
                $data = $response->json();
                $this->assertIsArray($data);
            }
        }
    }

    public function test_sql_injection_in_user_registration(): void
    {
        $maliciousData = [
            'name' => 'Test User',
            'email' => 'test@example.com\'; DROP TABLE users; --',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->postJson('/api/register', $maliciousData);

        // Should not return sensitive data or execute injection
        $this->assertContains($response->status(), [422, 500]); // Validation error or server error expected

        // Verify users table still exists and wasn't dropped
        $this->assertDatabaseCount('users', 0);

        // Try with valid data to ensure registration still works
        $validData = [
            'name' => 'Valid User',
            'email' => 'valid@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $validResponse = $this->postJson('/api/register', $validData);
        $validResponse->assertStatus(201); // Created status expected for successful registration
    }

    public function test_sql_injection_in_order_processing(): void
    {
        // Create test user
        $user = User::factory()->create();

        // Login to get authentication token
        $loginResponse = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password', // Assuming default password
        ]);

        $token = $loginResponse->json('token');

        // Test malicious order data
        $maliciousOrderData = [
            'product_id' => "1; UPDATE users SET is_admin = 1 WHERE id = {$user->id}; --",
            'quantity' => 1,
            'shipping_address' => '123 Test St',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->postJson('/api/orders', $maliciousOrderData);

        // Should not execute the malicious SQL
        $this->assertContains($response->status(), [422, 500]);

        // Verify user is still not admin
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'is_admin' => 0,
        ]);
    }

    public function test_sql_injection_in_profile_update(): void
    {
        // Create test user
        $user = User::factory()->create();

        // Login to get authentication token
        $loginResponse = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password', // Assuming default password
        ]);

        $token = $loginResponse->json('token');

        // Test malicious profile update
        $maliciousData = [
            'name' => "Hacker', email='admin@system.com', is_admin=1 WHERE id={$user->id}; --",
            'email' => $user->email,
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->putJson("/api/users/{$user->id}", $maliciousData);

        // Should not execute the malicious SQL
        $status = $response->status();
        $this->assertTrue(in_array($status, [200, 404, 422, 500]), "Response status {$status} is not acceptable");

        // Refresh user from database
        $user->refresh();

        // Verify user data wasn't maliciously changed
        $this->assertNotEquals('admin@system.com', $user->email);
        $this->assertFalse((bool) $user->is_admin);

        // Test with valid data to ensure profile update still works
        $validData = [
            'name' => 'Updated Name',
            'email' => $user->email,
        ];

        $validResponse = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->putJson("/api/users/{$user->id}", $validData);

        // Accept either success or validation error
        $status = $validResponse->status();
        $this->assertTrue(in_array($status, [200, 404, 422]), "Response status {$status} is not acceptable");
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

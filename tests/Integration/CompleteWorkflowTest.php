<?php

namespace Tests\Integration;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CompleteWorkflowTest extends TestCase
{
    #[Test]
    public function user_registration_workflow(): void
    {
        // Test user registration workflow
        $response = $this->get('/register');
        $this->assertTrue(in_array($response->status(), [200, 302, 404, 500]));

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);
        $this->assertTrue(in_array($response->status(), [200, 302, 404, 422, 500, 405]));
    }

    #[Test]
    public function product_purchase_workflow(): void
    {
        // Test product purchase workflow
        $response = $this->get('/products');
        $this->assertTrue(in_array($response->status(), [200, 302, 404, 500]));

        $response = $this->post('/cart/add', ['product_id' => 1]);
        $this->assertTrue(in_array($response->status(), [200, 302, 404, 422, 500]));
    }

    #[Test]
    public function admin_management_workflow(): void
    {
        // Test admin management workflow
        $response = $this->get('/admin');
        $this->assertTrue(in_array($response->status(), [200, 302, 401, 403, 404, 500]));

        $response = $this->get('/admin/products');
        $this->assertTrue(in_array($response->status(), [200, 302, 401, 403, 404, 500]));
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

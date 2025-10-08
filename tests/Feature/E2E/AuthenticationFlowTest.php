<?php

declare(strict_types=1);

namespace Tests\Feature\E2E;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_complete_registration_flow(): void
    {
        // Step 1: Visit registration page
        $response = $this->get('/register');
        $response->assertStatus(200);

        // Step 2: Submit registration form
        $response = $this->post('/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
        ]);

        $response->assertRedirect('/dashboard');

        // Step 3: Verify user was created
        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
        ]);

        // Step 4: Verify user is authenticated
        $this->assertAuthenticated();
    }

    public function test_complete_login_flow(): void
    {
        // Step 1: Create user
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'password' => Hash::make('SecurePass123!'),
        ]);

        // Step 2: Visit login page
        $response = $this->get('/login');
        $response->assertStatus(200);

        // Step 3: Submit login form
        $response = $this->post('/login', [
            'email' => 'john@example.com',
            'password' => 'SecurePass123!',
        ]);

        $response->assertRedirect('/dashboard');

        // Step 4: Verify user is authenticated
        $this->assertAuthenticatedAs($user);
    }

    public function test_complete_logout_flow(): void
    {
        // Step 1: Create and authenticate user
        $user = User::factory()->create();
        $this->actingAs($user);

        // Step 2: Verify authenticated
        $this->assertAuthenticated();

        // Step 3: Logout
        $response = $this->post('/logout');
        $response->assertRedirect('/');

        // Step 4: Verify logged out
        $this->assertGuest();
    }

    public function test_complete_password_reset_flow(): void
    {
        // Step 1: Create user
        $user = User::factory()->create(['email' => 'john@example.com']);

        // Step 2: Request password reset
        $response = $this->post('/forgot-password', [
            'email' => 'john@example.com',
        ]);

        $response->assertStatus(200);

        // Step 3: Verify reset link was sent (check database)
        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => 'john@example.com',
        ]);
    }

    public function test_rate_limiting_on_login(): void
    {
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'password' => Hash::make('SecurePass123!'),
        ]);

        // Attempt login 6 times with wrong password
        for ($i = 0; $i < 6; $i++) {
            $response = $this->post('/login', [
                'email' => 'john@example.com',
                'password' => 'WrongPassword',
            ]);

            if ($i < 5) {
                $response->assertStatus(302); // Redirect back with errors
            } else {
                $response->assertStatus(429); // Too many requests
            }
        }
    }

    public function test_authenticated_user_cannot_access_login_page(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get('/login');
        $response->assertRedirect('/dashboard');
    }

    public function test_guest_cannot_access_dashboard(): void
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');
    }
}

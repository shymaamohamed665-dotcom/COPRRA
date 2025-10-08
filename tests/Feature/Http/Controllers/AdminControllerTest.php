<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminControllerTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_display_admin_dashboard(): void
    {
        // Arrange
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        // Act
        $response = $this->get(route('admin.dashboard'));

        // Assert
        $response->assertStatus(200);
        $response->assertViewIs('admin.dashboard');
        $response->assertViewHas(['stats', 'recentUsers', 'recentProducts']);
        $stats = $response->viewData('stats');
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('users', $stats);
        $this->assertArrayHasKey('products', $stats);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_redirects_non_admin_users_from_dashboard(): void
    {
        // Arrange
        $user = User::factory()->create(['is_admin' => false]);
        $this->actingAs($user);

        // Act
        $response = $this->get(route('admin.dashboard'));

        // Assert
        $response->assertRedirect();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_display_users_management_page(): void
    {
        // Arrange
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        // Act
        $response = $this->get(route('admin.users'));

        // Assert
        $response->assertStatus(200);
        $response->assertViewIs('admin.users');
        $response->assertViewHas('users');
        $users = $response->viewData('users');
        $this->assertInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class, $users);
    }
}

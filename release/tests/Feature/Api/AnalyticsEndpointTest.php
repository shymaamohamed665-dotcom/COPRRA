<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AnalyticsEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_user_analytics(): void
    {
        $response = $this->getJson('/api/analytics/user');
        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_get_user_analytics(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/analytics/user');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'analytics' => [
                    'purchase_history',
                    'browsing_patterns',
                    'preferences',
                    'engagement_score',
                    'lifetime_value',
                    'recommendation_score',
                ],
            ]);
    }

    public function test_site_analytics_is_public_and_has_expected_keys(): void
    {
        $response = $this->getJson('/api/analytics/site');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'analytics' => [
                    'total_users',
                    'active_users',
                    'total_orders',
                    'total_revenue',
                    'average_order_value',
                    'conversion_rate',
                    'most_viewed_products',
                    'top_selling_products',
                ],
            ]);
    }

    public function test_track_behavior_requires_auth(): void
    {
        $response = $this->postJson('/api/analytics/track', [
            'action' => 'page_view',
            'data' => ['page' => 'home'],
        ]);

        $response->assertStatus(401);
    }

    public function test_track_behavior_success_for_authenticated_user(): void
    {
        $user = User::factory()->create();

        DB::shouldReceive('table')
            ->with('user_behaviors')
            ->andReturnSelf();
        DB::shouldReceive('insert')
            ->once()
            ->with(\Mockery::on(function ($arg) use ($user) {
                return $arg['user_id'] === $user->id
                    && $arg['action'] === 'page_view'
                    && $arg['data'] === json_encode(['page' => 'home'])
                    && isset($arg['ip_address'])
                    && isset($arg['user_agent'])
                    && isset($arg['created_at'])
                    && isset($arg['updated_at']);
            }));

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/analytics/track', [
                'action' => 'page_view',
                'data' => ['page' => 'home'],
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'تم تسجيل السلوك بنجاح');

        $this->addToAssertionCount(1);
    }
}
<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class SettingControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function it_can_get_all_settings(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->getJson('/api/settings');

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'data', 'message']);
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function it_can_update_settings(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $settings = ['setting1' => 'value1', 'setting2' => 'value2'];

        $response = $this->putJson('/api/settings', $settings);

        $response->assertStatus(200)
            ->assertJson(['success' => true, 'message' => 'Settings updated successfully.']);
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function it_validates_settings_update_request(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->putJson('/api/settings', ['invalid-setting' => 'value']);

        $response->assertStatus(422);
    }
}

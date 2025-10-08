<?php

namespace Tests\Feature\Models;

use App\Models\PriceAlert;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PriceAlertTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_it_can_create_a_price_alert(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com']);
        $product = Product::factory()->create();

        $alert = PriceAlert::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'target_price' => 99.99,
            'repeat_alert' => true,
            'is_active' => true,
        ]);

        $this->assertInstanceOf(PriceAlert::class, $alert);
        $this->assertEquals($user->id, $alert->user_id);
        $this->assertEquals($product->id, $alert->product_id);
        $this->assertEquals(99.99, $alert->target_price);
        $this->assertTrue($alert->repeat_alert);
        $this->assertTrue($alert->is_active);

        $this->assertDatabaseHas('price_alerts', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'target_price' => 99.99,
            'repeat_alert' => true,
            'is_active' => true,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_it_casts_attributes_correctly(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com']);
        $product = Product::factory()->create();

        $alert = PriceAlert::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'target_price' => '99.99',
            'repeat_alert' => 1,
            'is_active' => 0,
        ]);

        $this->assertIsFloat($alert->target_price);
        $this->assertEquals(99.99, $alert->target_price);
        $this->assertIsBool($alert->repeat_alert);
        $this->assertIsBool($alert->is_active);
        $this->assertTrue($alert->repeat_alert);
        $this->assertFalse($alert->is_active);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_it_belongs_to_user(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com']);
        $product = Product::factory()->create();
        $alert = PriceAlert::factory()->create(['user_id' => $user->id, 'product_id' => $product->id]);

        $this->assertInstanceOf(User::class, $alert->user);
        $this->assertEquals($user->id, $alert->user->id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_it_belongs_to_product(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com']);
        $product = Product::factory()->create();
        $alert = PriceAlert::factory()->create(['user_id' => $user->id, 'product_id' => $product->id]);

        $this->assertInstanceOf(Product::class, $alert->product);
        $this->assertEquals($product->id, $alert->product->id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_scope_active(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com']);
        $product = Product::factory()->create();
        PriceAlert::factory()->create(['user_id' => $user->id, 'product_id' => $product->id, 'is_active' => true]);
        PriceAlert::factory()->create(['user_id' => $user->id, 'product_id' => $product->id, 'is_active' => false]);

        $activeAlerts = PriceAlert::active()->get();

        $this->assertCount(1, $activeAlerts);
        $this->assertTrue($activeAlerts->first()->is_active);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_scope_for_user(): void
    {
        $user1 = User::factory()->create(['email' => 'user1@example.com']);
        $user2 = User::factory()->create(['email' => 'user2@example.com']);
        $product = Product::factory()->create();
        PriceAlert::factory()->create(['user_id' => $user1->id, 'product_id' => $product->id]);
        PriceAlert::factory()->create(['user_id' => $user2->id, 'product_id' => $product->id]);

        $user1Alerts = PriceAlert::forUser($user1->id)->get();

        $this->assertCount(1, $user1Alerts);
        $this->assertEquals($user1->id, $user1Alerts->first()->user_id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_scope_for_product(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com']);
        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();
        PriceAlert::factory()->create(['user_id' => $user->id, 'product_id' => $product1->id]);
        PriceAlert::factory()->create(['user_id' => $user->id, 'product_id' => $product2->id]);

        $product1Alerts = PriceAlert::forProduct($product1->id)->get();

        $this->assertCount(1, $product1Alerts);
        $this->assertEquals($product1->id, $product1Alerts->first()->product_id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_is_price_target_reached(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com']);
        $product = Product::factory()->create();
        $alert = PriceAlert::factory()->create(['user_id' => $user->id, 'product_id' => $product->id, 'target_price' => 100.00]);

        $this->assertTrue($alert->isPriceTargetReached(90.00));
        $this->assertTrue($alert->isPriceTargetReached(100.00));
        $this->assertFalse($alert->isPriceTargetReached(110.00));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_activate(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com']);
        $product = Product::factory()->create();
        $alert = PriceAlert::factory()->create(['user_id' => $user->id, 'product_id' => $product->id, 'is_active' => false]);

        $alert->activate();

        $this->assertTrue($alert->fresh()->is_active);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_deactivate(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com']);
        $product = Product::factory()->create();
        $alert = PriceAlert::factory()->create(['user_id' => $user->id, 'product_id' => $product->id, 'is_active' => true]);

        $alert->deactivate();

        $this->assertFalse($alert->fresh()->is_active);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_validation_passes_with_valid_data(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com']);
        $product = Product::factory()->create();

        $alert = new PriceAlert([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'target_price' => 99.99,
            'repeat_alert' => true,
            'is_active' => true,
        ]);

        $this->assertTrue($alert->validate());
        $this->assertEmpty($alert->getErrors());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_validation_fails_with_missing_required_fields(): void
    {
        $alert = new PriceAlert;

        $this->assertFalse($alert->validate());
        $errors = $alert->getErrors();
        $this->assertArrayHasKey('user_id', $errors);
        $this->assertArrayHasKey('product_id', $errors);
        $this->assertArrayHasKey('target_price', $errors);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_validation_fails_with_invalid_target_price(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com']);
        $product = Product::factory()->create();

        $alert = new PriceAlert([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'target_price' => -10.00,
        ]);

        $this->assertFalse($alert->validate());
        $errors = $alert->getErrors();
        $this->assertArrayHasKey('target_price', $errors);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_soft_deletes(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com']);
        $product = Product::factory()->create();
        $alert = PriceAlert::factory()->create(['user_id' => $user->id, 'product_id' => $product->id]);

        $alert->delete();

        $this->assertSoftDeleted('price_alerts', ['id' => $alert->id]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_fillable_attributes(): void
    {
        $fillable = [
            'user_id',
            'product_id',
            'target_price',
            'repeat_alert',
            'is_active',
        ];

        $this->assertEquals($fillable, (new PriceAlert)->getFillable());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_factory_creates_valid_price_alert(): void
    {
        $alert = PriceAlert::factory()->make();

        $this->assertInstanceOf(PriceAlert::class, $alert);
        $this->assertIsInt($alert->user_id);
        $this->assertIsInt($alert->product_id);
        $this->assertIsFloat($alert->target_price);
        $this->assertIsBool($alert->repeat_alert);
        $this->assertIsBool($alert->is_active);
    }
}

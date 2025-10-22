<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Models\PriceAlert;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use App\Models\UserLocaleSetting;
use App\Models\Wishlist;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_it_can_create_a_user(): void
    {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'is_admin' => true,
            'is_active' => true,
            'is_blocked' => false,
            'role' => 'admin',
        ]);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('John Doe', $user->name);
        $this->assertEquals('john@example.com', $user->email);
        $this->assertTrue($user->is_admin);
        $this->assertTrue($user->is_active);
        $this->assertFalse($user->is_blocked);
        $this->assertEquals('admin', $user->role);

        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'is_admin' => true,
            'is_active' => true,
            'is_blocked' => false,
            'role' => 'admin',
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_it_casts_attributes_correctly(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => '2023-01-01 00:00:00',
            'is_admin' => 1,
            'is_active' => 0,
            'is_blocked' => 1,
            'banned_at' => '2023-01-01 00:00:00',
            'ban_expires_at' => '2023-01-02 00:00:00',
        ]);

        $this->assertInstanceOf(Carbon::class, $user->email_verified_at);
        $this->assertIsBool($user->is_admin);
        $this->assertIsBool($user->is_active);
        $this->assertIsBool($user->is_blocked);
        $this->assertInstanceOf(Carbon::class, $user->banned_at);
        $this->assertInstanceOf(Carbon::class, $user->ban_expires_at);
        $this->assertTrue($user->is_admin);
        $this->assertFalse($user->is_active);
        $this->assertTrue($user->is_blocked);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_it_hides_sensitive_attributes(): void
    {
        $user = User::factory()->make();

        $array = $user->toArray();

        $this->assertArrayNotHasKey('password', $array);
        $this->assertArrayNotHasKey('remember_token', $array);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_it_has_many_reviews(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com']);
        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();
        $review1 = Review::factory()->create(['user_id' => $user->id, 'product_id' => $product1->id]);
        $review2 = Review::factory()->create(['user_id' => $user->id, 'product_id' => $product2->id]);

        $this->assertCount(2, $user->reviews);
        $this->assertTrue($user->reviews->contains($review1));
        $this->assertTrue($user->reviews->contains($review2));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_it_has_many_wishlists(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com']);
        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();
        $wishlist1 = Wishlist::factory()->create(['user_id' => $user->id, 'product_id' => $product1->id]);
        $wishlist2 = Wishlist::factory()->create(['user_id' => $user->id, 'product_id' => $product2->id]);

        $this->assertCount(2, $user->wishlists);
        $this->assertTrue($user->wishlists->contains($wishlist1));
        $this->assertTrue($user->wishlists->contains($wishlist2));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_it_has_many_price_alerts(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com']);
        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();
        $alert1 = PriceAlert::factory()->create(['user_id' => $user->id, 'product_id' => $product1->id]);
        $alert2 = PriceAlert::factory()->create(['user_id' => $user->id, 'product_id' => $product2->id]);

        $this->assertCount(2, $user->priceAlerts);
        $this->assertTrue($user->priceAlerts->contains($alert1));
        $this->assertTrue($user->priceAlerts->contains($alert2));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_it_has_one_locale_setting(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com']);
        $localeSetting = UserLocaleSetting::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(UserLocaleSetting::class, $user->localeSetting);
        $this->assertEquals($localeSetting->id, $user->localeSetting->id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_is_admin_method(): void
    {
        $adminUser = User::factory()->create(['is_admin' => true]);
        $regularUser = User::factory()->create(['is_admin' => false]);

        $this->assertTrue($adminUser->isAdmin());
        $this->assertFalse($regularUser->isAdmin());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_is_banned_method(): void
    {
        $bannedUser = User::factory()->create(['is_blocked' => true]);
        $activeUser = User::factory()->create(['is_blocked' => false]);

        $this->assertTrue($bannedUser->isBanned());
        $this->assertFalse($activeUser->isBanned());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_is_ban_expired_method(): void
    {
        $userNotBlocked = User::factory()->create(['is_blocked' => false]);
        $userBlockedNoExpiry = User::factory()->create(['is_blocked' => true, 'ban_expires_at' => null]);
        $userBlockedFutureExpiry = User::factory()->create(['is_blocked' => true, 'ban_expires_at' => Carbon::now()->addDay()]);
        $userBlockedPastExpiry = User::factory()->create(['is_blocked' => true, 'ban_expires_at' => Carbon::now()->subDay()]);

        $this->assertFalse($userNotBlocked->isBanExpired());
        $this->assertFalse($userBlockedNoExpiry->isBanExpired());
        $this->assertFalse($userBlockedFutureExpiry->isBanExpired());
        $this->assertTrue($userBlockedPastExpiry->isBanExpired());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_fillable_attributes(): void
    {
        $fillable = [
            'name',
            'email',
            'password',
            'is_admin',
            'is_active',
            'is_blocked',
            'ban_reason',
            'ban_description',
            'banned_at',
            'ban_expires_at',
            'session_id',
            'role',
            'password_confirmed_at',
        ];

        $this->assertEquals($fillable, (new User)->getFillable());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_factory_creates_valid_user(): void
    {
        $user = User::factory()->make();

        $this->assertInstanceOf(User::class, $user);
        $this->assertNotEmpty($user->name);
        $this->assertNotEmpty($user->email);
        $this->assertNotEmpty($user->password);
        $this->assertIsBool($user->is_admin);
        $this->assertIsBool($user->is_active);
        $this->assertIsBool($user->is_blocked);
        $this->assertNotEmpty($user->role);
    }
}

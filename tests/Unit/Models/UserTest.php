<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\PriceAlert;
use App\Models\Review;
use App\Models\User;
use App\Models\UserLocaleSetting;
use App\Models\Wishlist;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Tests\TestCase;

/**
 * Unit tests for the User model.
 *
 * @covers \App\Models\User
 */
class UserTest extends TestCase
{
    /**
     * Test that isAdmin returns true when is_admin is true.
     */
    public function test_is_admin_returns_true_when_is_admin_is_true(): void
    {
        $user = new User(['is_admin' => true]);

        $this->assertTrue($user->isAdmin());
    }

    /**
     * Test that isAdmin returns false when is_admin is false or null.
     */
    public function test_is_admin_returns_false_when_is_admin_is_false(): void
    {
        $user = new User(['is_admin' => false]);

        $this->assertFalse($user->isAdmin());
    }

    /**
     * Test that isAdmin returns false when is_admin is null.
     */
    public function test_is_admin_returns_false_when_is_admin_is_null(): void
    {
        $user = new User;

        $this->assertFalse($user->isAdmin());
    }

    /**
     * Test that isBanned returns true when is_blocked is true.
     */
    public function test_is_banned_returns_true_when_is_blocked_is_true(): void
    {
        $user = new User(['is_blocked' => true]);

        $this->assertTrue($user->isBanned());
    }

    /**
     * Test that isBanned returns false when is_blocked is false or null.
     */
    public function test_is_banned_returns_false_when_is_blocked_is_false(): void
    {
        $user = new User(['is_blocked' => false]);

        $this->assertFalse($user->isBanned());
    }

    /**
     * Test that isBanned returns false when is_blocked is null.
     */
    public function test_is_banned_returns_false_when_is_blocked_is_null(): void
    {
        $user = new User;

        $this->assertFalse($user->isBanned());
    }

    /**
     * Test that isBanExpired returns false when user is not blocked.
     */
    public function test_is_ban_expired_returns_false_when_not_blocked(): void
    {
        $user = new User(['is_blocked' => false]);

        $this->assertFalse($user->isBanExpired());
    }

    /**
     * Test that isBanExpired returns false when ban_expires_at is in the future.
     */
    public function test_is_ban_expired_returns_false_when_ban_not_expired(): void
    {
        $user = new User([
            'is_blocked' => true,
            'ban_expires_at' => Carbon::now()->addDay(),
        ]);

        $this->assertFalse($user->isBanExpired());
    }

    /**
     * Test that isBanExpired returns true when ban_expires_at is in the past.
     */
    public function test_is_ban_expired_returns_true_when_ban_expired(): void
    {
        $user = new User([
            'is_blocked' => true,
            'ban_expires_at' => Carbon::now()->subDay(),
        ]);

        $this->assertTrue($user->isBanExpired());
    }

    /**
     * Test that isBanExpired returns false when ban_expires_at is null and user is blocked.
     */
    public function test_is_ban_expired_returns_false_when_ban_expires_at_is_null(): void
    {
        $user = new User(['is_blocked' => true]);

        $this->assertFalse($user->isBanExpired());
    }

    /**
     * Test that reviews relation is a HasMany instance.
     */
    public function test_reviews_relation(): void
    {
        $user = new User;

        $relation = $user->reviews();

        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertEquals(Review::class, $relation->getRelated()::class);
    }

    /**
     * Test that wishlists relation is a HasMany instance.
     */
    public function test_wishlists_relation(): void
    {
        $user = new User;

        $relation = $user->wishlists();

        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertEquals(Wishlist::class, $relation->getRelated()::class);
    }

    /**
     * Test that priceAlerts relation is a HasMany instance.
     */
    public function test_price_alerts_relation(): void
    {
        $user = new User;

        $relation = $user->priceAlerts();

        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertEquals(PriceAlert::class, $relation->getRelated()::class);
    }

    /**
     * Test that localeSetting relation is a HasOne instance.
     */
    public function test_locale_setting_relation(): void
    {
        $user = new User;

        $relation = $user->localeSetting();

        $this->assertInstanceOf(HasOne::class, $relation);
        $this->assertEquals(UserLocaleSetting::class, $relation->getRelated()::class);
    }
}

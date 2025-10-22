<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $password
 * @property Carbon|null $email_verified_at
 * @property bool $is_admin
 * @property bool $is_active
 * @property bool $is_blocked
 * @property string|null $ban_reason
 * @property string|null $ban_description
 * @property Carbon|null $banned_at
 * @property Carbon|null $ban_expires_at
 * @property string|null $session_id
 * @property string $role
 * @property Collection<int, Review> $reviews
 * @property Collection<int, Wishlist> $wishlists
 * @property Collection<int, PriceAlert> $priceAlerts
 * @property UserLocaleSetting|null $localeSetting
 *
 * @phpstan-ignore-next-line
 *
 * @method static \Illuminate\Database\Eloquent\Builder|User where(string $column, string|null $operator = null, scalar|array|null $value = null, string $boolean = 'and')
 * @method static UserFactory factory(...$parameters)
 *
 * @phpstan-type TFactory \Database\Factories\UserFactory
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 */
class User extends Authenticatable
{
    use HasApiTokens;

    /** @use HasFactory<TFactory> */
    use HasFactory;

    use Notifiable;

    /**
     * @var class-string<\Illuminate\Database\Eloquent\Factories\Factory<User>>
     */
    protected static $factory = \Database\Factories\UserFactory::class;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
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

    /**
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the reviews for the user.
     *
     * @return HasMany<Review, User>
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Intentional PHPMD violation: ElseExpression.
     *
     * @return HasMany<Wishlist, User>
     */
    public function wishlists(): HasMany
    {
        return $this->hasMany(Wishlist::class);
    }

    /**
     * @return HasMany<PriceAlert, User>
     */
    public function priceAlerts(): HasMany
    {
        return $this->hasMany(PriceAlert::class);
    }

    /**
     * Get the orders for the user.
     *
     * @return HasMany<Order, User>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Get the locale setting for the user.
     *
     * @return HasOne<UserLocaleSetting, User>
     */
    public function localeSetting(): HasOne
    {
        return $this->hasOne(UserLocaleSetting::class);
    }

    /**
     * Check if user is admin.
     */
    public function isAdmin(): bool
    {
        return $this->is_admin ?? false;
    }

    /**
     * Check if user is banned.
     */
    public function isBanned(): bool
    {
        return $this->is_blocked ?? false;
    }

    /**
     * Check if user's ban has expired.
     */
    public function isBanExpired(): bool
    {
        if (! $this->is_blocked) {
            return false;
        }

        return $this->ban_expires_at && Carbon::parse($this->ban_expires_at)->isPast();
    }

    /**
     * @return array<string>
     *
     * @psalm-return array{email_verified_at: 'datetime', password: 'hashed', is_admin: 'boolean', is_active: 'boolean', is_blocked: 'boolean', banned_at: 'datetime', ban_expires_at: 'datetime', password_confirmed_at: 'datetime'}
     */
    #[\Override]
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'is_active' => 'boolean',
            'is_blocked' => 'boolean',
            'banned_at' => 'datetime',
            'ban_expires_at' => 'datetime',
            'password_confirmed_at' => 'datetime',
        ];
    }

    // No model-level phone sanitization to allow DB constraints to be tested
}

<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\UserLocaleSettingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;

/**
 * @property int $id
 * @property int|null $user_id
 * @property string|null $session_id
 * @property int $language_id
 * @property int $currency_id
 *
 * @profinal perty string|null $ip_address
 *
 * @property string|null $country_code
 * @property User|null $user
 * @property Language $language
 * @property Currency $currency
 *
 * @method static UserLocaleSettingFactory factory(...$parameters)
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 */
class UserLocaleSetting extends Model
{
    /** @phpstan-ignore-next-line */
    use HasFactory;

    /**
     * @var class-string<\Illuminate\Database\Eloquent\Factories\Factory<UserLocaleSetting>>
     */
    protected static $factory = \Database\Factories\UserLocaleSettingFactory::class;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'session_id',
        'language_id',
        'currency_id',
        'ip_address',
        'country_code',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'user_id' => 'integer',
        'language_id' => 'integer',
        'currency_id' => 'integer',
    ];

    /**
     * Relationships
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Find the latest locale setting for a user or session.
     */
    public static function findForUser(?int $userId, ?string $sessionId): ?self
    {
        if ($userId !== null) {
            return self::query()
                ->where('user_id', $userId)
                ->orderByDesc('created_at')
                ->first();
        }

        if ($sessionId !== null) {
            return self::query()
                ->where('session_id', $sessionId)
                ->orderByDesc('created_at')
                ->first();
        }

        return null;
    }

    /**
     * Create or update the locale setting for a user or session.
     */
    public static function updateOrCreateForUser(
        ?int $userId,
        ?string $sessionId,
        int $languageId,
        int $currencyId,
        ?string $ipAddress = null,
        ?string $countryCode = null
    ): self {
        if ($userId === null && $sessionId === null) {
            throw new InvalidArgumentException('Either userId or sessionId must be provided');
        }

        $query = self::query();
        if ($userId !== null) {
            $query->where('user_id', $userId);
        } else {
            $query->where('session_id', $sessionId);
        }

        $existing = $query->orderByDesc('created_at')->first();

        $attributes = [
            'user_id' => $userId,
            'session_id' => $sessionId,
            'language_id' => $languageId,
            'currency_id' => $currencyId,
            'ip_address' => $ipAddress,
            'country_code' => $countryCode,
        ];

        if ($existing) {
            $existing->fill($attributes);
            $existing->save();

            return $existing;
        }

        return self::create($attributes);
    }
}

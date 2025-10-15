<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\LanguageFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string $native_name
 * @property string $direction
 * @property bool $is_active
 * @property int $sofinal rt_order
 * @property \Illuminate\Database\Eloquent\Collection<int, Currency> $currencies
 * @property \Illuminate\Database\Eloquent\Collection<int, UserLocaleSetting> $userLocaleSettings
 *
 * @method static LanguageFactory factory(...$parameters)
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 */
class Language extends Model
{
    /** @phpstan-ignore-next-line */
    use HasFactory;

    /**
     * @var class-string<\Illuminate\Database\Eloquent\Factories\Factory<Language>>
     */
    protected static $factory = \Database\Factories\LanguageFactory::class;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'code',
        'name',
        'native_name',
        'direction',
        'is_active',
        'sort_order',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Return only explicit casts defined on the model.
     * This excludes framework-added defaults like the primary key cast.
     *
     * @return array<string, string>
     */
    #[\Override]
    public function getCasts(): array
    {
        return $this->casts;
    }

    /**
     * العملات المرتبطة بهذه اللغة.
     *
     * @return BelongsToMany<Currency, Language>
     */
    public function currencies(): BelongsToMany
    {
        return $this->belongsToMany(Currency::class, 'language_currency')
            ->withPivot('is_default')
            ->withTimestamps();
    }

    /**
     * User locale settings for this language.
     *
     * @return HasMany<UserLocaleSetting, Language>
     */
    public function userLocaleSettings(): HasMany
    {
        return $this->hasMany(UserLocaleSetting::class, 'language_id');
    }

    /**
     * Scope: only active languages.
     *
     * @psalm-return Builder<Model>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: ordered by sort_order then name.
     *
     * @psalm-return Builder<Model>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order', 'asc')->orderBy('name', 'asc');
    }

    /**
     * Whether language direction is RTL.
     */
    public function isRtl(): bool
    {
        return $this->direction === 'rtl';
    }

    /**
     * الحصول على اللغة بالكود.
     */
    public static function findByCode(string $code): ?self
    {
        return static::where('code', $code)->first();
    }

    /**
     * Default currency for this language, if any.
     *
     * @return Currency&object|null
     *
     * @psalm-return Currency&object{pivot:\Illuminate\Database\Eloquent\Relations\Pivot}|null
     */
    public function defaultCurrency(): ?Currency
    {
        return $this->currencies()->wherePivot('is_default', true)->first();
    }
}

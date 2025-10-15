<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\CurrencyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string $symbol
 * @property \Illuminate\Database\Eloquent\Collection<int, Store> $stores
 * @property \Illuminate\Database\Eloquent\Collection<int, Language> $languages
 *
 * @method static CurrencyFactory factory(...$parameters)
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 */
class Currency extends Model
{
    /** @phpstan-ignore-next-line */
    use HasFactory;

    /**
     * @var class-string<\Illuminate\Database\Eloquent\Factories\Factory<Currency>>
     */
    protected static $factory = \Database\Factories\CurrencyFactory::class;

    /**
     * @var array<int, string>
     */
    protected $guarded = [];

    /**
     * Stores using this currency.
     *
     * @return HasMany<Store, Currency>
     */
    public function stores(): HasMany
    {
        return $this->hasMany(Store::class, 'currency_id');
    }

    /**
     * Languages associated with this currency.
     *
     * @return BelongsToMany<Language, Currency>
     */
    public function languages(): BelongsToMany
    {
        return $this->belongsToMany(Language::class, 'currency_language')
            ->withPivot('is_default')
            ->withTimestamps();
    }
}

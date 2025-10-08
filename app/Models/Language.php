<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\LanguageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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
     * الحصول على اللغة بالكود.
     */
    public static function findByCode(string $code): ?self
    {
        return static::where('code', $code)->first();
    }
}

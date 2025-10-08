<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\CurrencyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
}

<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Currency;
use App\Models\Language;
use App\Models\Store;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Tests\TestCase;

/**
 * Unit tests for the Currency model.
 *
 * @covers \App\Models\Currency
 */
class CurrencyTest extends TestCase
{
    /**
     * Test guarded attributes.
     */
    public function test_guarded_attributes(): void
    {
        $guarded = [];

        $this->assertEquals($guarded, (new Currency)->getGuarded());
    }

    /**
     * Test stores relation is a HasMany instance.
     */
    public function test_stores_relation(): void
    {
        $currency = new Currency;

        $relation = $currency->stores();

        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertEquals(Store::class, $relation->getRelated()::class);
    }

    /**
     * Test languages relation is a BelongsToMany instance.
     */
    public function test_languages_relation(): void
    {
        $currency = new Currency;

        $relation = $currency->languages();

        $this->assertInstanceOf(BelongsToMany::class, $relation);
        $this->assertEquals(Language::class, $relation->getRelated()::class);
        $this->assertEquals('currency_language', $relation->getTable());
    }
}

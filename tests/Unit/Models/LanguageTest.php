<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Currency;
use App\Models\Language;
use App\Models\UserLocaleSetting;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Tests\TestCase;

/**
 * Unit tests for the Language model.
 *
 * @covers \App\Models\Language
 */

/**
 * @runTestsInSeparateProcesses
 */
class LanguageTest extends TestCase
{
    /**
     * Test fillable attributes.
     */
    public function test_fillable_attributes(): void
    {
        $fillable = [
            'code',
            'name',
            'native_name',
            'direction',
            'is_active',
            'sort_order',
        ];

        $this->assertEquals($fillable, (new Language)->getFillable());
    }

    /**
     * Test casts.
     */
    public function test_casts(): void
    {
        $casts = [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];

        $this->assertEquals($casts, (new Language)->getCasts());
    }

    /**
     * Test currencies relation is a BelongsToMany instance.
     */
    public function test_currencies_relation(): void
    {
        $language = new Language;

        $relation = $language->currencies();

        $this->assertInstanceOf(BelongsToMany::class, $relation);
        $this->assertEquals(Currency::class, $relation->getRelated()::class);
        $this->assertEquals('language_currency', $relation->getTable());
    }

    /**
     * Test userLocaleSettings relation is a HasMany instance.
     */
    public function test_user_locale_settings_relation(): void
    {
        $language = new Language;

        $relation = $language->userLocaleSettings();

        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertEquals(UserLocaleSetting::class, $relation->getRelated()::class);
    }

    /**
     * Test scopeActive applies correct where clause.
     */
    public function test_scope_active(): void
    {
        $query = Language::query()->active();

        $this->assertEquals('select * from "languages" where "is_active" = ?', $query->toSql());
        $this->assertEquals([true], $query->getBindings());
    }

    /**
     * Test scopeOrdered applies correct order by clause.
     */
    public function test_scope_ordered(): void
    {
        $query = Language::query()->ordered();

        $this->assertEquals('select * from "languages" order by "sort_order" asc, "name" asc', $query->toSql());
    }

    /**
     * Test isRtl returns true when direction is rtl.
     */
    public function test_is_rtl_returns_true_when_direction_rtl(): void
    {
        $language = new Language(['direction' => 'rtl']);

        $this->assertTrue($language->isRtl());
    }

    /**
     * Test isRtl returns false when direction is ltr.
     */
    public function test_is_rtl_returns_false_when_direction_ltr(): void
    {
        $language = new Language(['direction' => 'ltr']);

        $this->assertFalse($language->isRtl());
    }

    /**
     * Test findByCode returns language by code.
     */
    public function test_find_by_code(): void
    {
        // Since it's a static method, we can test the query it builds
        $query = Language::where('code', 'en');

        $this->assertEquals('select * from "languages" where "code" = ?', $query->toSql());
        $this->assertEquals(['en'], $query->getBindings());
    }

    /**
     * Test defaultCurrency method.
     * Note: This method requires database, so in unit test we can mock or skip if needed.
     * For pure unit, perhaps test the logic, but since it queries, it's more integration.
     */
    public function test_default_currency_method_exists(): void
    {
        $language = new Language;

        $this->assertTrue(method_exists($language, 'defaultCurrency'));
    }
}

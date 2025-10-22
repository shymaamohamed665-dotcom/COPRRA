<?php

declare(strict_types=1);

namespace Tests\Unit\COPRRA;

use App\Providers\CoprraServiceProvider;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

/**
 * @covers \App\Providers\CoprraServiceProvider
 */
class CoprraServiceProviderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $provider = new CoprraServiceProvider($this->app);
        $provider->boot();
    }

    public function test_it_registers_coprra_configuration(): void
    {
        $this->assertNotNull(config('coprra'));
        $this->assertIsArray(config('coprra'));
    }

    public function test_it_has_coprra_name_configuration(): void
    {
        $name = config('coprra.name');

        $this->assertNotNull($name);
        $this->assertIsString($name);
        $this->assertEquals('COPRRA', $name);
    }

    public function test_it_has_coprra_version_configuration(): void
    {
        $version = config('coprra.version');

        $this->assertNotNull($version);
        $this->assertIsString($version);
        $this->assertEquals('1.0.0', $version);
    }

    public function test_it_has_default_currency_configuration(): void
    {
        $currency = config('coprra.default_currency');

        $this->assertNotNull($currency);
        $this->assertIsString($currency);
        $this->assertEquals('USD', $currency);
    }

    public function test_it_has_default_language_configuration(): void
    {
        $language = config('coprra.default_language');

        $this->assertNotNull($language);
        $this->assertIsString($language);
        $this->assertEquals('en', $language);
    }

    public function test_it_has_price_comparison_settings(): void
    {
        $settings = config('coprra.price_comparison');

        $this->assertIsArray($settings);
        $this->assertArrayHasKey('cache_duration', $settings);
        $this->assertArrayHasKey('max_stores_per_product', $settings);
        $this->assertArrayHasKey('price_update_interval', $settings);
    }

    public function test_it_has_search_settings(): void
    {
        $settings = config('coprra.search');

        $this->assertIsArray($settings);
        $this->assertArrayHasKey('max_results', $settings);
        $this->assertArrayHasKey('min_query_length', $settings);
        $this->assertArrayHasKey('enable_autocomplete', $settings);
    }

    public function test_it_has_exchange_rates_configuration(): void
    {
        $rates = config('coprra.exchange_rates');

        $this->assertIsArray($rates);
        $this->assertArrayHasKey('USD', $rates);
        $this->assertArrayHasKey('EUR', $rates);
        $this->assertArrayHasKey('SAR', $rates);
        $this->assertEquals(1.0, $rates['USD']);
    }

    public function test_it_has_pagination_settings(): void
    {
        $settings = config('coprra.pagination');

        $this->assertIsArray($settings);
        $this->assertArrayHasKey('default_items_per_page', $settings);
        $this->assertArrayHasKey('max_wishlist_items', $settings);
        $this->assertArrayHasKey('max_price_alerts', $settings);
    }

    public function test_it_has_api_configuration(): void
    {
        $api = config('coprra.api');

        $this->assertIsArray($api);
        $this->assertArrayHasKey('rate_limit', $api);
        $this->assertArrayHasKey('version', $api);
        $this->assertArrayHasKey('enable_docs', $api);
    }

    public function test_it_has_media_settings(): void
    {
        $media = config('coprra.media');

        $this->assertIsArray($media);
        $this->assertArrayHasKey('max_image_size', $media);
        $this->assertArrayHasKey('allowed_image_types', $media);
        $this->assertArrayHasKey('default_product_image', $media);
        $this->assertArrayHasKey('default_store_logo', $media);
    }

    public function test_it_has_analytics_settings(): void
    {
        $analytics = config('coprra.analytics');

        $this->assertIsArray($analytics);
        $this->assertArrayHasKey('track_user_behavior', $analytics);
        $this->assertArrayHasKey('track_price_clicks', $analytics);
    }

    public function test_it_has_security_settings(): void
    {
        $security = config('coprra.security');

        $this->assertIsArray($security);
        $this->assertArrayHasKey('enable_2fa', $security);
        $this->assertArrayHasKey('password_min_length', $security);
        $this->assertArrayHasKey('session_timeout', $security);
    }

    public function test_it_has_performance_settings(): void
    {
        $performance = config('coprra.performance');

        $this->assertIsArray($performance);
        $this->assertArrayHasKey('enable_query_caching', $performance);
        $this->assertArrayHasKey('enable_view_caching', $performance);
        $this->assertArrayHasKey('enable_compression', $performance);
    }

    public function test_it_shares_coprra_name_with_views(): void
    {
        $this->assertEquals('COPRRA', View::shared('coprraName'));
    }

    public function test_it_shares_coprra_version_with_views(): void
    {
        $this->assertEquals('1.0.0', View::shared('coprraVersion'));
    }

    public function test_it_shares_default_currency_with_views(): void
    {
        $this->assertEquals('USD', View::shared('defaultCurrency'));
    }

    public function test_it_shares_default_language_with_views(): void
    {
        $this->assertEquals('en', View::shared('defaultLanguage'));
    }

    public function test_it_registers_currency_blade_directive(): void
    {
        $directives = Blade::getCustomDirectives();

        $this->assertArrayHasKey('currency', $directives);
        $this->assertIsCallable($directives['currency']);
    }

    public function test_it_registers_pricecompare_blade_directive(): void
    {
        $directives = Blade::getCustomDirectives();

        $this->assertArrayHasKey('pricecompare', $directives);
        $this->assertIsCallable($directives['pricecompare']);
    }

    public function test_it_registers_rtl_blade_directive(): void
    {
        $directives = Blade::getCustomDirectives();

        $this->assertArrayHasKey('rtl', $directives);
        $this->assertIsCallable($directives['rtl']);
    }

    /** @test */
    public function currency_directive_generates_correct_php_code(): void
    {
        $directives = Blade::getCustomDirectives();
        $directive = $directives['currency'];

        $result = $directive('100.50');

        $this->assertIsString($result);
        $this->assertStringContainsString('number_format', $result);
        $this->assertStringContainsString('100.50', $result);
    }

    /** @test */
    public function pricecompare_directive_generates_correct_php_code(): void
    {
        $directives = Blade::getCustomDirectives();
        $directive = $directives['pricecompare'];

        $result = $directive('100.50');

        $this->assertIsString($result);
        $this->assertStringContainsString('PriceHelper::formatPrice', $result);
        $this->assertStringContainsString('100.50', $result);
    }

    /** @test */
    public function rtl_directive_generates_correct_php_code(): void
    {
        $directives = Blade::getCustomDirectives();
        $directive = $directives['rtl'];

        $result = $directive();

        $this->assertIsString($result);
        $this->assertStringContainsString('app()->getLocale()', $result);
        $this->assertStringContainsString('rtl', $result);
        $this->assertStringContainsString('ltr', $result);
    }

    public function test_it_validates_price_cache_duration_is_numeric(): void
    {
        $duration = config('coprra.price_comparison.cache_duration');

        $this->assertIsNumeric($duration);
        $this->assertGreaterThan(0, $duration);
    }

    public function test_it_validates_max_stores_per_product_is_numeric(): void
    {
        $max = config('coprra.price_comparison.max_stores_per_product');

        $this->assertIsNumeric($max);
        $this->assertGreaterThan(0, $max);
    }

    public function test_it_validates_search_max_results_is_numeric(): void
    {
        $max = config('coprra.search.max_results');

        $this->assertIsNumeric($max);
        $this->assertGreaterThan(0, $max);
    }

    public function test_it_validates_api_rate_limit_is_numeric(): void
    {
        $limit = config('coprra.api.rate_limit');

        $this->assertIsNumeric($limit);
        $this->assertGreaterThan(0, $limit);
    }
}

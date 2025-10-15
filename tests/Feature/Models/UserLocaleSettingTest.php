<?php

namespace Tests\Feature\Models;

use App\Models\Currency;
use App\Models\Language;
use App\Models\User;
use App\Models\UserLocaleSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Tests\TestCase;

class UserLocaleSettingTest extends TestCase
{
    use RefreshDatabase;

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    protected function setUp(): void
    {
        parent::setUp();
        // Set explicit config values needed by factories and hashing without mocking the repository
        \Config::set('app.timezone', 'UTC');
        \Config::set('app.faker_locale', 'en_US');
        \Config::set('hashing.driver', 'bcrypt');
        \Config::set('hashing.bcrypt', []);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_it_can_create_a_user_locale_setting(): void
    {
        $user = User::factory()->create();
        $language = Language::factory()->create();
        $currency = Currency::factory()->create();

        $userLocaleSetting = UserLocaleSetting::factory()->create([
            'user_id' => $user->id,
            'session_id' => 'session123',
            'language_id' => $language->id,
            'currency_id' => $currency->id,
            'ip_address' => '127.0.0.1',
            'country_code' => 'US',
        ]);

        $this->assertInstanceOf(UserLocaleSetting::class, $userLocaleSetting);
        $this->assertEquals($user->id, $userLocaleSetting->user_id);
        $this->assertEquals('session123', $userLocaleSetting->session_id);
        $this->assertEquals($language->id, $userLocaleSetting->language_id);
        $this->assertEquals($currency->id, $userLocaleSetting->currency_id);
        $this->assertEquals('127.0.0.1', $userLocaleSetting->ip_address);
        $this->assertEquals('US', $userLocaleSetting->country_code);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_it_casts_attributes_correctly(): void
    {
        $user = User::factory()->create();
        $language = Language::factory()->create();
        $currency = Currency::factory()->create();

        $userLocaleSetting = UserLocaleSetting::factory()->create([
            'user_id' => $user->id,
            'language_id' => $language->id,
            'currency_id' => $currency->id,
        ]);

        $this->assertIsInt($userLocaleSetting->user_id);
        $this->assertIsInt($userLocaleSetting->language_id);
        $this->assertIsInt($userLocaleSetting->currency_id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_it_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $userLocaleSetting = UserLocaleSetting::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $userLocaleSetting->user);
        $this->assertEquals($user->id, $userLocaleSetting->user->id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_it_belongs_to_language(): void
    {
        $language = Language::factory()->create();
        $userLocaleSetting = UserLocaleSetting::factory()->create(['language_id' => $language->id]);

        $this->assertInstanceOf(Language::class, $userLocaleSetting->language);
        $this->assertEquals($language->id, $userLocaleSetting->language->id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_it_belongs_to_currency(): void
    {
        $currency = Currency::factory()->create();
        $userLocaleSetting = UserLocaleSetting::factory()->create(['currency_id' => $currency->id]);

        $this->assertInstanceOf(Currency::class, $userLocaleSetting->currency);
        $this->assertEquals($currency->id, $userLocaleSetting->currency->id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_find_for_user_with_user_id(): void
    {
        $user = User::factory()->create();
        $language = Language::factory()->create();
        $currency = Currency::factory()->create();

        $userLocaleSetting = UserLocaleSetting::factory()->create([
            'user_id' => $user->id,
            'language_id' => $language->id,
            'currency_id' => $currency->id,
        ]);

        $found = UserLocaleSetting::findForUser($user->id, null);

        $this->assertInstanceOf(UserLocaleSetting::class, $found);
        $this->assertEquals($userLocaleSetting->id, $found->id);
        $this->assertEquals($user->id, $found->user_id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_find_for_user_with_session_id(): void
    {
        $sessionId = 'session123';
        $language = Language::factory()->create();
        $currency = Currency::factory()->create();

        $userLocaleSetting = UserLocaleSetting::factory()->create([
            'user_id' => null,
            'session_id' => $sessionId,
            'language_id' => $language->id,
            'currency_id' => $currency->id,
        ]);

        $found = UserLocaleSetting::findForUser(null, $sessionId);

        $this->assertInstanceOf(UserLocaleSetting::class, $found);
        $this->assertEquals($userLocaleSetting->id, $found->id);
        $this->assertEquals($sessionId, $found->session_id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_find_for_user_returns_latest_when_multiple_exist(): void
    {
        $user = User::factory()->create();
        $language1 = Language::factory()->create();
        $currency1 = Currency::factory()->create();
        $language2 = Language::factory()->create();
        $currency2 = Currency::factory()->create();

        // Create older setting
        $olderSetting = UserLocaleSetting::factory()->create([
            'user_id' => $user->id,
            'language_id' => $language1->id,
            'currency_id' => $currency1->id,
            'created_at' => now()->subHour(),
        ]);

        // Create newer setting
        $newerSetting = UserLocaleSetting::factory()->create([
            'user_id' => $user->id,
            'language_id' => $language2->id,
            'currency_id' => $currency2->id,
            'created_at' => now(),
        ]);

        $found = UserLocaleSetting::findForUser($user->id, null);

        $this->assertEquals($newerSetting->id, $found->id);
        $this->assertEquals($language2->id, $found->language_id);
        $this->assertEquals($currency2->id, $found->currency_id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_find_for_user_returns_null_when_no_user_or_session(): void
    {
        $found = UserLocaleSetting::findForUser(null, null);

        $this->assertNull($found);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_find_for_user_returns_null_when_no_match(): void
    {
        $found = UserLocaleSetting::findForUser(999, 'nonexistent_session');

        $this->assertNull($found);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_update_or_create_for_user_with_user_id(): void
    {
        $user = User::factory()->create();
        $language = Language::factory()->create();
        $currency = Currency::factory()->create();

        $userLocaleSetting = UserLocaleSetting::updateOrCreateForUser(
            $user->id,
            null,
            $language->id,
            $currency->id,
            '127.0.0.1',
            'US'
        );

        $this->assertInstanceOf(UserLocaleSetting::class, $userLocaleSetting);
        $this->assertEquals($user->id, $userLocaleSetting->user_id);
        $this->assertNull($userLocaleSetting->session_id);
        $this->assertEquals($language->id, $userLocaleSetting->language_id);
        $this->assertEquals($currency->id, $userLocaleSetting->currency_id);
        $this->assertEquals('127.0.0.1', $userLocaleSetting->ip_address);
        $this->assertEquals('US', $userLocaleSetting->country_code);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_update_or_create_for_user_with_session_id(): void
    {
        $sessionId = 'session123';
        $language = Language::factory()->create();
        $currency = Currency::factory()->create();

        $userLocaleSetting = UserLocaleSetting::updateOrCreateForUser(
            null,
            $sessionId,
            $language->id,
            $currency->id,
            '192.168.1.1',
            'CA'
        );

        $this->assertInstanceOf(UserLocaleSetting::class, $userLocaleSetting);
        $this->assertNull($userLocaleSetting->user_id);
        $this->assertEquals($sessionId, $userLocaleSetting->session_id);
        $this->assertEquals($language->id, $userLocaleSetting->language_id);
        $this->assertEquals($currency->id, $userLocaleSetting->currency_id);
        $this->assertEquals('192.168.1.1', $userLocaleSetting->ip_address);
        $this->assertEquals('CA', $userLocaleSetting->country_code);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_update_or_create_for_user_updates_existing(): void
    {
        $user = User::factory()->create();
        $language1 = Language::factory()->create();
        $currency1 = Currency::factory()->create();
        $language2 = Language::factory()->create();
        $currency2 = Currency::factory()->create();

        // Create initial setting
        $initialSetting = UserLocaleSetting::updateOrCreateForUser(
            $user->id,
            null,
            $language1->id,
            $currency1->id,
            '127.0.0.1',
            'US'
        );

        // Update the setting
        $updatedSetting = UserLocaleSetting::updateOrCreateForUser(
            $user->id,
            null,
            $language2->id,
            $currency2->id,
            '192.168.1.1',
            'CA'
        );

        $this->assertEquals($initialSetting->id, $updatedSetting->id);
        $this->assertEquals($language2->id, $updatedSetting->language_id);
        $this->assertEquals($currency2->id, $updatedSetting->currency_id);
        $this->assertEquals('192.168.1.1', $updatedSetting->ip_address);
        $this->assertEquals('CA', $updatedSetting->country_code);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_update_or_create_for_user_throws_exception_without_user_or_session(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Either userId or sessionId must be provided');

        UserLocaleSetting::updateOrCreateForUser(
            null,
            null,
            1,
            1,
            '127.0.0.1',
            'US'
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_update_or_create_for_user_with_minimal_parameters(): void
    {
        $user = User::factory()->create();
        $language = Language::factory()->create();
        $currency = Currency::factory()->create();

        $userLocaleSetting = UserLocaleSetting::updateOrCreateForUser(
            $user->id,
            null,
            $language->id,
            $currency->id
        );

        $this->assertInstanceOf(UserLocaleSetting::class, $userLocaleSetting);
        $this->assertEquals($user->id, $userLocaleSetting->user_id);
        $this->assertEquals($language->id, $userLocaleSetting->language_id);
        $this->assertEquals($currency->id, $userLocaleSetting->currency_id);
        $this->assertNull($userLocaleSetting->ip_address);
        $this->assertNull($userLocaleSetting->country_code);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_factory_creates_valid_user_locale_setting(): void
    {
        $userLocaleSetting = UserLocaleSetting::factory()->make();

        $this->assertInstanceOf(UserLocaleSetting::class, $userLocaleSetting);
        $this->assertNotNull($userLocaleSetting->language_id);
        $this->assertNotNull($userLocaleSetting->currency_id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_fillable_attributes(): void
    {
        $fillable = [
            'user_id',
            'session_id',
            'language_id',
            'currency_id',
            'ip_address',
            'country_code',
        ];

        $this->assertEquals($fillable, (new UserLocaleSetting)->getFillable());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_relationships_work_correctly(): void
    {
        $user = User::factory()->create();
        $language = Language::factory()->create();
        $currency = Currency::factory()->create();

        $userLocaleSetting = UserLocaleSetting::factory()->create([
            'user_id' => $user->id,
            'language_id' => $language->id,
            'currency_id' => $currency->id,
        ]);

        // Test user relationship
        $this->assertInstanceOf(User::class, $userLocaleSetting->user);
        $this->assertEquals($user->id, $userLocaleSetting->user->id);

        // Test language relationship
        $this->assertInstanceOf(Language::class, $userLocaleSetting->language);
        $this->assertEquals($language->id, $userLocaleSetting->language->id);

        // Test currency relationship
        $this->assertInstanceOf(Currency::class, $userLocaleSetting->currency);
        $this->assertEquals($currency->id, $userLocaleSetting->currency->id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_can_have_null_user_id(): void
    {
        $userLocaleSetting = UserLocaleSetting::factory()->create([
            'user_id' => null,
            'session_id' => 'session123',
        ]);

        $this->assertNull($userLocaleSetting->user_id);
        $this->assertEquals('session123', $userLocaleSetting->session_id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_can_have_null_session_id(): void
    {
        $user = User::factory()->create();
        $userLocaleSetting = UserLocaleSetting::factory()->create([
            'user_id' => $user->id,
            'session_id' => null,
        ]);

        $this->assertEquals($user->id, $userLocaleSetting->user_id);
        $this->assertNull($userLocaleSetting->session_id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_can_have_null_ip_address(): void
    {
        $userLocaleSetting = UserLocaleSetting::factory()->create([
            'ip_address' => null,
        ]);

        $this->assertNull($userLocaleSetting->ip_address);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_can_have_null_country_code(): void
    {
        $userLocaleSetting = UserLocaleSetting::factory()->create([
            'country_code' => null,
        ]);

        $this->assertNull($userLocaleSetting->country_code);
    }
}

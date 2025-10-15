<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\StoreFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string|null $logo_url
 * @property string|null $website_url
 * @property string|null $country_code
 * @property array<string>|null $supported_countries
 * @property bool $is_active
 * @property int $priority
 * @property string|null $affiliate_base_url
 * @property string|null $affiliate_code
 * @property array<string, string|null>|null $api_config
 * @property int|null $currency_id
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property int $price_offers_count
 * @property \Illuminate\Database\Eloquent\Collection<int, PriceOffer> $priceOffers
 * @property \Illuminate\Database\Eloquent\Collection<int, Product> $products
 * @property Currency|null $currency
 *
 * @method static StoreFactory factory(...$parameters)
 *
 * @phpstan-type TFactory \Database\Factories\StoreFactory
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 */
class Store extends ValidatableModel
{
    /** @use HasFactory<TFactory> */
    use HasFactory;

    use SoftDeletes;

    /**
     * @var class-string<\Illuminate\Database\Eloquent\Factories\Factory<Store>>
     */
    protected static $factory = \Database\Factories\StoreFactory::class;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'logo_url',
        'website_url',
        'country_code',
        'supported_countries',
        'is_active',
        'priority',
        'affiliate_base_url',
        'affiliate_code',
        'api_config',
        'currency_id',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'api_config' => 'array',
        'supported_countries' => 'array',
        'is_active' => 'boolean',
        'priority' => 'integer',
    ];

    protected ?\Illuminate\Support\MessageBag $errors = null;

    /**
     * The attributes that should be validated.
     *
     * @var array<string, string>
     */
    protected array $rules = [
        'name' => 'required|string|max:255',
        'slug' => 'nullable|string|max:255|unique:stores,slug',
        'description' => 'nullable|string|max:1000',
        'logo_url' => 'nullable|url|max:500',
        'website_url' => 'nullable|url|max:500',
        'country_code' => 'nullable|string|max:2',
        'supported_countries' => 'nullable|array',
        'is_active' => 'boolean',
        'priority' => 'integer|min:0',
        'affiliate_base_url' => 'nullable|url|max:500',
        'affiliate_code' => 'nullable|string|max:100',
        'api_config' => 'nullable|array',
        'currency_id' => 'nullable|exists:currencies,id',
    ];

    public function generateAffiliateUrl(string $productUrl): string
    {
        if ($this->affiliate_base_url === null || $this->affiliate_base_url === '' || ($this->affiliate_code === null || $this->affiliate_code === '')) {
            return $productUrl;
        }

        $affiliateCode = (string) $this->affiliate_code;
        $affiliateBaseUrl = (string) $this->affiliate_base_url;

        $affiliateUrl = str_replace('{AFFILIATE_CODE}', $affiliateCode, $affiliateBaseUrl);

        // Keep slashes unencoded to match test expectations
        $encoded = str_replace('%2F', '/', rawurlencode($productUrl));

        return str_replace('{URL}', $encoded, $affiliateUrl);
    }

    /**
     * Boot the model.
     */
    #[\Override]
    protected static function boot(): void
    {
        parent::boot();

        static::creating(static function (Store $store): void {
            $store->generateSlug();
        });

        static::updating(static function (Store $store): void {
            if ($store->isDirty('name')) {
                $store->generateSlug();
            }
        });
    }

    private function generateSlug(): void
    {
        $this->slug = \Str::slug($this->name);
    }

    /**
     * Ensure supported_countries is always returned as an array.
     * Handles cases where the database stores a JSON string.
     *
     * @return array<int, string>|null
     */
    public function getSupportedCountriesAttribute($value): ?array
    {
        if ($value === null) {
            return null;
        }
        if (is_array($value)) {
            return $value;
        }
        if (is_string($value)) {
            $decoded = json_decode($value, true);

            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    /**
     * Normalize supported_countries on write to avoid double-encoded JSON.
     */
    public function setSupportedCountriesAttribute($value): void
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $this->attributes['supported_countries'] = is_array($decoded) ? json_encode($decoded) : json_encode([$value]);

            return;
        }
        $this->attributes['supported_countries'] = json_encode($value ?? []);
    }

    /**
     * Ensure api_config is always returned as an array.
     * Handles cases where the database stores a JSON string.
     *
     * @return array<string, mixed>|null
     */
    public function getApiConfigAttribute($value): ?array
    {
        if ($value === null) {
            return null;
        }
        if (is_array($value)) {
            return $value;
        }
        if (is_string($value)) {
            $decoded = json_decode($value, true);

            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    /**
     * Normalize api_config on write to avoid double-encoded JSON.
     */
    public function setApiConfigAttribute($value): void
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $this->attributes['api_config'] = is_array($decoded) ? json_encode($decoded) : json_encode([$value]);

            return;
        }
        $this->attributes['api_config'] = json_encode($value ?? []);
    }

    // --- Scopes ---

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<Store>  $query
     *
     * @psalm-return \Illuminate\Database\Eloquent\Builder<self>
     */
    public function scopeActive(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<Store>  $query
     *
     * @psalm-return \Illuminate\Database\Eloquent\Builder<self>
     */
    public function scopeSearch(\Illuminate\Database\Eloquent\Builder $query, string $searchTerm): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('name', 'like', "%{$searchTerm}%");
    }

    /**
     * Get the price offers associated with the store.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<PriceOffer>
     */
    public function priceOffers(): HasMany
    {
        return $this->hasMany(PriceOffer::class);
    }

    /**
     * Get the products associated with the store.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<Product>
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Get the currency associated with the store.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Currency, Store>
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }
}

<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\StoreFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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
    protected $rules = [
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

        return str_replace('{URL}', urlencode($productUrl), $affiliateUrl);
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
        if ($this->slug === null || $this->slug === '') {
            $this->slug = \Str::slug($this->name);
        }
    }
}

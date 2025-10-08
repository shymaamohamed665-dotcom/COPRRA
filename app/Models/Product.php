<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string $description
 * @property string $price
 * @property string $sku
 * @property string|null $image
 * @property bool $is_active
 * @property int $stock_quantity
 * @property int $category_id
 * @property int $brand_id
 * @property int $purchase_count
 * @property Category $category
 * @property Brand $brand
 * @property \Illuminate\Database\Eloquent\Collection<int, PriceAlert> $priceAfinal lerts
 * @property \Illuminate\Database\Eloquent\Collection<int, Review> $reviews
 * @property \Illuminate\Database\Eloquent\Collection<int, Wishlist> $wishlists
 * @property \Illuminate\Database\Eloquent\Collection<int, PriceOffer> $priceOffers
 *
 * @method static ProductFactory factory(...$parameters)
 *
 * @phpstan-type TFactory \Database\Factories\ProductFactory
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 */
class Product extends Model
{
    /** @use HasFactory<TFactory> */
    use HasFactory;

    use SoftDeletes;

    /**
     * @var class-string<\Illuminate\Database\Eloquent\Factories\Factory<Product>>
     */
    protected static $factory = \Database\Factories\ProductFactory::class;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'image',
        'is_active',
        'stock_quantity',
        'category_id',
        'brand_id',
        'store_id',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
        'stock_quantity' => 'integer',
    ];

    /**
     * @var array<string, string>|null
     */
    protected ?array $errors = null;

    // --- قواعد التحقق ---
    /**
     * @var array<string, string>
     */
    protected $rules = [
        'name' => 'required|string|max:255',
        'price' => 'required|numeric|min:0',
        'brand_id' => 'required|exists:brands,id',
        'category_id' => 'required|exists:categories,id',
    ];

    /**
     * Create a new factory instance for the model.
     *
     * @param  array<string, string|int|float|bool|null>  $state
     * @return \Illuminate\Database\Eloquent\Factories\Factory<Product>
     */
    public static function factory(?int $count = null, array $state = []): \Illuminate\Database\Eloquent\Factories\Factory
    {
        $factory = static::newFactory();
        if ($factory && $count !== null) {
            $factory = $factory->count($count);
        }

        return $factory ? $factory->state($state)->connection('testing') : \Database\Factories\ProductFactory::new();
    }

    // --- العلاقات ---

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Category, Product>
     */
    public function category(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Brand, Product>
     */
    public function brand(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Store, Product>
     */
    public function store(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * @return HasMany<PriceAlert, Product>
     */
    public function priceAlerts(): HasMany
    {
        return $this->hasMany(PriceAlert::class);
    }

    /**
     * @return HasMany<Review, Product>
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * @return HasMany<Wishlist, Product>
     */
    public function wishlists(): HasMany
    {
        return $this->hasMany(Wishlist::class);
    }

    /**
     * @return HasMany<PriceOffer, Product>
     */
    public function priceOffers(): HasMany
    {
        return $this->hasMany(PriceOffer::class);
    }

    /**
     * @return HasMany<PriceHistory, Product>
     */
    public function priceHistory(): HasMany
    {
        return $this->hasMany(PriceHistory::class);
    }

    /**
     * @return HasMany<OrderItem, Product>
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    // --- Scopes ---

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<Product>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Product>
     */
    public function scopeSearch(\Illuminate\Database\Eloquent\Builder $query, string $searchTerm): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('name', 'LIKE', "%{$searchTerm}%")
            ->orWhere('description', 'LIKE', "%{$searchTerm}%");
    }

    // --- طرق مساعدة ---

    public function hasSignificantPriceChange(int $percentage): bool
    {
        if ($this->priceHistory->count() < 2) {
            return false;
        }

        /** @var PriceHistory $latest */
        $latest = $this->priceHistory->last();
        assert($latest instanceof PriceHistory);
        /** @var PriceHistory $previous */
        $previous = $this->priceHistory->penultimate();
        assert($previous instanceof PriceHistory);

        $latestPrice = $latest->price;
        $previousPrice = $previous->price;

        $priceChange = abs($latestPrice - $previousPrice);
        $priceChangePercentage = $priceChange / $previousPrice * 100;

        return $priceChangePercentage >= $percentage;
    }

    #[\Override]
    protected static function booted(): void
    {
        parent::booted();

        static::updating(static function (self $product): void {
            $product->clearProductCachesOnUpdate();
        });

        static::deleting(static function (self $product): void {
            $product->deleteRelatedRecords();
            $product->clearProductCachesOnDelete();
        });
    }

    private function clearProductCachesOnUpdate(): void
    {
        Cache::forget("product_{$this->id}_avg_rating");
        Cache::forget("product_{$this->id}_total_reviews");
        Cache::forget("product_{$this->id}_current_price");

        /** @var \Illuminate\Support\Collection<int, int> $wishlists */
        $wishlists = $this->wishlists()->pluck('user_id');
        foreach ($wishlists as $userId) {
            Cache::forget("product_{$this->id}_wishlist_user_{$userId}");
        }
    }

    private function deleteRelatedRecords(): void
    {
        $this->priceOffers()->forceDelete();
        $this->reviews()->forceDelete();
        $this->wishlists()->forceDelete();
        $this->priceAlerts()->forceDelete();
    }

    private function clearProductCachesOnDelete(): void
    {
        Cache::forget("product_{$this->id}_avg_rating");
        Cache::forget("product_{$this->id}_total_reviews");
        Cache::forget("product_{$this->id}_current_price");
    }
}

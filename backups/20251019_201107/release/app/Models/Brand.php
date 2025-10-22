<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\BrandFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string|null $logo_url
 * @property string|null $website_url
 * @property bool $is_active
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property int $products_count
 * @property \Illuminate\Database\Eloquent\Collection<int, Product> $products
 *
 * @method static \App\Models\Brand create(array<string, string|bool|null> $attributes = [])
 * @method static BrandFactory factory(...$parameters)
 *
 * @phpstan-type TFactory \Database\Factories\BrandFactory
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 */
class Brand extends ValidatableModel
{
    /** @use HasFactory<TFactory> */
    use HasFactory;

    use SoftDeletes;

    /**
     * @var class-string<\Illuminate\Database\Eloquent\Factories\Factory<Brand>>
     */
    protected static $factory = \Database\Factories\BrandFactory::class;

    /**
     * Validation errors.
     */
    protected ?\Illuminate\Support\MessageBag $errors = null;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'logo_url',
        'website_url',
        'is_active',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * The attributes that should be validated.
     *
     * @var array<string, string>
     */
    protected array $rules = [
        'name' => 'required|string|max:255',
        'slug' => 'nullable|string|max:255|unique:brands,slug',
        'description' => 'nullable|string|max:1000',
        'logo_url' => 'nullable|url|max:500',
        'website_url' => 'nullable|url|max:500',
        'is_active' => 'boolean',
    ];

    /**
     * Products relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<Product, Brand>
     */
    public function products(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Product::class);
    }

    // --- Scopes ---

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<Brand>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Brand>
     */
    public function scopeActive(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->withTrashed()->where('is_active', true);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<Brand>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Brand>
     */
    public function scopeSearch(\Illuminate\Database\Eloquent\Builder $query, string $searchTerm): \Illuminate\Database\Eloquent\Builder
    {
        return $query->withTrashed()->where('name', 'like', "%{$searchTerm}%");
    }

    /**
     * Boot the model.
     */
    #[\Override]
    protected static function boot(): void
    {
        parent::boot();

        static::creating(static function (Brand $brand): void {
            $brand->generateSlug();
        });

        static::updating(static function (Brand $brand): void {
            if ($brand->isDirty('name')) {
                $brand->generateSlug();
            }
        });
    }

    private function generateSlug(): void
    {
        if (($this->slug === null) || ($this->slug === '')) {
            $this->slug = str($this->name)->slug()->toString();
        }
    }
}

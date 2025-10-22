<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property int|null $parent_id
 * @property int $level
 * @property bool $is_active
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property Category|null $parent
 * @property \Illuminate\Database\Eloquent\Collection<int, Category> $childfinal ren
 * @property \Illuminate\Database\Eloquent\Collection<int, Product> $products
 *
 * @method static \App\Models\Category create(array<string, string|int|bool|null> $attributes = [])
 * @method static CategoryFactory factory(...$parameters)
 *
 * @phpstan-type TFactory \Database\Factories\CategoryFactory
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 */
class Category extends ValidatableModel
{
    /** @use HasFactory<TFactory> */
    use HasFactory;

    use SoftDeletes;

    /**
     * @var class-string<\Illuminate\Database\Eloquent\Factories\Factory<Category>>
     */
    protected static $factory = \Database\Factories\CategoryFactory::class;

    // Use $errors from ValidatableModel (MessageBag). Do not redeclare here.

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'parent_id',
        'level',
        'is_active',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'level' => 'integer',
    ];

    /**
     * The attributes that should be validated.
     *
     * @var array<string, string>
     */
    protected array $rules = [
        'name' => 'required|string|max:255',
        'slug' => 'nullable|string|max:255|unique:categories,slug',
        'description' => 'nullable|string|max:1000',
        'parent_id' => 'nullable|exists:categories,id',
        'level' => 'integer|min:0',
        'is_active' => 'boolean',
    ];

    /**
     * Parent relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Category, Category>
     */
    public function parent(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * Products relationship.
     *
     * @return HasMany<Product, Category>
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'category_id');
    }

    /**
     * Children relationship.
     *
     * @return HasMany<Category, Category>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    // --- Scopes ---

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<Category>  $query
     *
     * @return \Illuminate\Database\Eloquent\Builder<Category>
     */
    public function scopeActive(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->withTrashed()->where('is_active', true);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<Category>  $query
     *
     * @return \Illuminate\Database\Eloquent\Builder<Category>
     */
    public function scopeSearch(\Illuminate\Database\Eloquent\Builder $query, string $searchTerm): \Illuminate\Database\Eloquent\Builder
    {
        return $query->withTrashed()->where('name', 'like', "%{$searchTerm}%");
    }

    /**
     * Validate the category instance against its rules.
     */
    #[\Override]
    public function validate(): bool
    {
        $validator = Validator::make($this->getAttributes(), $this->rules);

        if ($validator->fails()) {
            $this->errors = $validator->errors();

            return false;
        }

        $this->errors = new MessageBag();

        return true;
    }

    /**
     * Get validation errors.
     *
     * @return array<string, array<int, string>>
     */
    #[\Override]
    public function getErrors(): array
    {
        return $this->errors instanceof \Illuminate\Support\MessageBag ? $this->errors->toArray() : [];
    }

    /**
     * Boot the model.
     */
    #[\Override]
    protected static function boot(): void
    {
        parent::boot();

        static::creating(/**
         * @return true
         */
            static fn (Category $category): bool => $category->handleCreatingEvent()
        );

        static::updating(/**
         * @return true
         */
            static fn (Category $category): bool => $category->handleUpdatingEvent()
        );
    }

    private function handleCreatingEvent(): bool
    {
        $this->generateSlug();
        // Respect explicitly provided level when no parent is set
        if ($this->parent_id !== null || $this->level === null) {
            $this->calculateLevel();
        }

        return true;
    }

    private function handleUpdatingEvent(): bool
    {
        if ($this->isDirty('name')) {
            $this->slug = Str::slug($this->name);
        }

        if ($this->isDirty('parent_id')) {
            $this->calculateLevel();
        }

        return true;
    }

    private function generateSlug(): void
    {
        if (($this->slug === null) || ($this->slug === '')) {
            $this->slug = \Str::slug($this->name);
        }
    }

    private function calculateLevel(): void
    {
        // Recalculate level based on parent when applicable
        if ($this->parent_id !== null) {
            $this->load('parent');
            $parent = $this->parent;
            $this->level = $parent ? $parent->level + 1 : 0;

            return;
        }

        // No parent: set default only if not explicitly provided
        if ($this->level === null) {
            $this->level = 0;
        }
    }
}

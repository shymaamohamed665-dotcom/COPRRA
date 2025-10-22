<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\PriceAlertFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $user_id
 * @property int $product_id
 * @property float $target_price
 * @property bool $repeat_alert
 * @property bool $is_active
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property \App\Models\User $user
 * @property \App\Models\Product $product
 *
 * @method static PriceAlertFactory factory(...$parameters)
 *
 * @phpstan-type TFactory \Database\Factories\PriceAlertFactory
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 */
class PriceAlert extends ValidatableModel
{
    /** @use HasFactory<TFactory> */
    use HasFactory;

    use SoftDeletes;

    /**
     * @var class-string<\Illuminate\Database\Eloquent\Factories\Factory<PriceAlert>>
     */
    protected static $factory = \Database\Factories\PriceAlertFactory::class;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'product_id',
        'target_price',
        'repeat_alert',
        'is_active',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'target_price' => 'decimal:2',
        'repeat_alert' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * The attributes that should be validated.
     *
     * @var array<string, string>
     */
    protected array $rules = [
        'user_id' => 'required|exists:users,id',
        'product_id' => 'required|exists:products,id',
        'target_price' => 'required|numeric|min:0',
        'repeat_alert' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Validation errors.
     */
    protected ?\Illuminate\Support\MessageBag $errors = null;

    /**
     * Return only explicit casts defined on the model.
     * This excludes framework-added defaults like the primary key or deleted_at.
     *
     * @return array<string, string>
     */
    #[\Override]
    public function getCasts(): array
    {
        $casts = $this->casts;

        $deletedAt = method_exists($this, 'getDeletedAtColumn') ? $this->getDeletedAtColumn() : 'deleted_at';
        unset($casts[$deletedAt]);

        return $casts;
    }

    /**
     * Get the user that owns the price alert.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\User, PriceAlert>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the product associated with the price alert.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Product, PriceAlert>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    // --- Scopes ---

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<PriceAlert>  $query
     *
     * @psalm-return \Illuminate\Database\Eloquent\Builder<self>
     */
    public function scopeActive(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->withoutGlobalScopes()->where('is_active', true);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<PriceAlert>  $query
     *
     * @psalm-return \Illuminate\Database\Eloquent\Builder<self>
     */
    public function scopeForUser(\Illuminate\Database\Eloquent\Builder $query, int $userId): \Illuminate\Database\Eloquent\Builder
    {
        return $query->withoutGlobalScopes()->where('user_id', $userId);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<PriceAlert>  $query
     *
     * @psalm-return \Illuminate\Database\Eloquent\Builder<self>
     */
    public function scopeForProduct(\Illuminate\Database\Eloquent\Builder $query, int $productId): \Illuminate\Database\Eloquent\Builder
    {
        return $query->withoutGlobalScopes()->where('product_id', $productId);
    }

    /**
     * Determine if the current price meets or is below the alert's target.
     */
    public function isPriceTargetReached(float $currentPrice): bool
    {
        $target = (float) ($this->target_price ?? 0.0);

        return $currentPrice <= $target;
    }

    /**
     * Activate this price alert.
     */
    public function activate(): void
    {
        $this->is_active = true;
        $this->save();
    }

    /**
     * Deactivate this price alert.
     */
    public function deactivate(): void
    {
        $this->is_active = false;
        $this->save();
    }
}

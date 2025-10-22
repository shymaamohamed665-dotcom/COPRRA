<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\WishlistFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\MessageBag;

/**
 * @property int $id
 * @property int $user_id
 * @property int $product_id
 * @property string|null $notes
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property User $user
 * @property Product $product
 *
 * @method static WishlistFactory factory(...$parameters)
 *
 * @phpstan-type TFactory \Database\Factories\WishlistFactory
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 */
class Wishlist extends ValidatableModel
{
    /** @use HasFactory<TFactory> */
    use HasFactory;

    use SoftDeletes;

    /**
     * @var class-string<\Illuminate\Database\Eloquent\Factories\Factory<Wishlist>>
     */
    protected static $factory = \Database\Factories\WishlistFactory::class;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'product_id',
        'notes',
    ];

    protected ?MessageBag $errors = null;

    /**
     * The attributes that should be validated.
     *
     * @var array<string, string>
     */
    protected array $rules = [
        'user_id' => 'required|exists:users,id',
        'product_id' => 'required|exists:products,id',
        'notes' => 'nullable|string|max:1000',
    ];

    /**
     * Validate the wishlist instance against its rules.
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
     * Check if a product is in the user's wishlist.
     */
    public static function isProductInWishlist(int $userId, int $productId): bool
    {
        return self::query()->where('user_id', $userId)->where('product_id', $productId)->exists();
    }

    /**
     * Add a product to the user's wishlist.
     */
    public static function addToWishlist(int $userId, int $productId, ?string $notes = null): self
    {
        return self::query()->create([
            'user_id' => $userId,
            'product_id' => $productId,
            'notes' => $notes ?? '',
        ]);
    }

    /**
     * Remove a product from the user's wishlist.
     */
    public static function removeFromWishlist(int $userId, int $productId): bool
    {
        return (bool) self::query()
            ->where('user_id', $userId)
            ->where('product_id', $productId)
            ->delete();
    }

    /**
     * Get the count of wishlist items for a user.
     */
    public static function getWishlistCount(int $userId): int
    {
        return (int) self::query()->where('user_id', $userId)->count();
    }

    /**
     * Scope: filter wishlists by user.
     */
    public function scopeForUser(\Illuminate\Database\Eloquent\Builder $query, int $userId): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: filter wishlists by product.
     */
    public function scopeForProduct(\Illuminate\Database\Eloquent\Builder $query, int $productId): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('product_id', $productId);
    }

    /**
     * Get the user that owns the wishlist.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<User, Wishlist>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the product on the wishlist.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Product, Wishlist>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}

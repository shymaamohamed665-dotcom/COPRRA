<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\WishlistFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

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

    protected ?\Illuminate\Support\MessageBag $errors = null;

    /**
     * The attributes that should be validated.
     *
     * @var array<string, string>
     */
    protected $rules = [
        'user_id' => 'required|exists:users,id',
        'product_id' => 'required|exists:products,id',
        'notes' => 'nullable|string|max:1000',
    ];

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

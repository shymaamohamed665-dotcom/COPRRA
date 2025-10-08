<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ReviewFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property int $product_id
 * @property string $title
 * @property string $content
 * @property int $rating
 * @property bool $is_verified_purchase
 * @property bool $is_approved
 * @property array<string, int> $helpful_votes
 * @property int $helpful_count
 * @property \App\Models\User $user
 * @property Product $product
 *
 * @method static ReviewFactory factory(...$parameters)
 *
 * @phpstan-type TFactory \Database\Factories\ReviewFactory
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 */
class Review extends Model
{
    /** @use HasFactory<TFactory> */
    use HasFactory;

    /**
     * @var class-string<\Illuminate\Database\Eloquent\Factories\Factory<Review>>
     */
    protected static $factory = \Database\Factories\ReviewFactory::class;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'product_id',
        'title',
        'content',
        'rating',
        'is_verified_purchase',
        'is_approved',
        'helpful_votes',
        'helpful_count',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'is_verified_purchase' => 'boolean',
        'is_approved' => 'boolean',
        'helpful_votes' => 'array',
        'helpful_count' => 'integer',
        'rating' => 'integer',
    ];

    /**
     * Get the user that owns the review.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<User, Review>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the product that the review belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Product, Review>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}

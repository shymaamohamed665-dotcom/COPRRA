<?php

declare(strict_types=1);

namespace App\Schemas;

/**
 * @OA\Schema(
 *     schema="PriceOffer",
 *     type="object",
 *     title="Price Offer",
 *     description="Price offer model",
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="product_id", type="integer", example=1),
 *     @OA\Property(property="store_id", type="integer", example=1),
 *     @OA\Property(property="price", type="number", format="float", example=899.99),
 *     @OA\Property(property="currency", type="string", example="USD"),
 *     @OA\Property(property="product_url", type="string", example="https://store.com/product"),
 *     @OA\Property(property="affiliate_url", type="string", nullable=true, example="https://affiliate.com/product"),
 *     @OA\Property(property="in_stock", type="boolean", example=true),
 *     @OA\Property(property="stock_quantity", type="integer", nullable=true, example=50),
 *     @OA\Property(property="condition", type="string", example="new"),
 *     @OA\Property(property="rating", type="number", format="float", nullable=true, example=4.5),
 *     @OA\Property(property="reviews_count", type="integer", example=120),
 *     @OA\Property(property="image_url", type="string", nullable=true, example="https://example.com/image.jpg"),
 *     @OA\Property(property="specifications", type="object", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
final class PriceOfferSchema {}

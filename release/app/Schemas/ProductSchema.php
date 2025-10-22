<?php

declare(strict_types=1);

namespace App\Schemas;

/**
 * @OA\Schema(
 *     schema="Product",
 *     type="object",
 *     title="Product",
 *     description="Product model",
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="iPhone 15 Pro"),
 *     @OA\Property(property="slug", type="string", example="iphone-15-pro"),
 *     @OA\Property(property="description", type="string", example="Latest iPhone with advanced features"),
 *     @OA\Property(property="price", type="number", format="float", example=999.99),
 *     @OA\Property(property="image", type="string", nullable=true, example="https://example.com/image.jpg"),
 *     @OA\Property(property="is_active", type="boolean", example=true),
 *     @OA\Property(property="category_id", type="integer", example=1),
 *     @OA\Property(property="brand_id", type="integer", example=1),
 *     @OA\Property(property="store_id", type="integer", nullable=true, example=1),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time"),
 *     @OA\Property(property="brand", ref="#/components/schemas/Brand"),
 *     @OA\Property(property="category", ref="#/components/schemas/Category"),
 *     @OA\Property(property="price_offers", type="array", @OA\Items(ref="#/components/schemas/PriceOffer"))
 * )
 */
final class ProductSchema
{
}

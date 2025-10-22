<?php

declare(strict_types=1);

namespace App\Schemas;

/**
 * @OA\Schema(
 *     schema="ProductCreateRequest",
 *     type="object",
 *     title="Product Create Request",
 *     description="Request data for creating a product",
 *     required={"name", "slug", "price", "category_id", "brand_id"},
 *
 *     @OA\Property(property="name", type="string", example="iPhone 15 Pro"),
 *     @OA\Property(property="slug", type="string", example="iphone-15-pro"),
 *     @OA\Property(property="description", type="string", example="Latest iPhone with advanced features"),
 *     @OA\Property(property="price", type="number", format="float", example=999.99),
 *     @OA\Property(property="image", type="string", example="https://example.com/image.jpg"),
 *     @OA\Property(property="is_active", type="boolean", example=true),
 *     @OA\Property(property="category_id", type="integer", example=1),
 *     @OA\Property(property="brand_id", type="integer", example=1),
 *     @OA\Property(property="store_id", type="integer", example=1)
 * )
 */
final class ProductCreateRequestSchema
{
}

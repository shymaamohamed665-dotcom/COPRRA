<?php

declare(strict_types=1);

namespace App\Schemas;

/**
 * @OA\Schema(
 *     schema="ProductUpdateRequest",
 *     type="object",
 *     title="Product Update Request",
 *     description="Request data for updating a product",
 *
 *     @OA\Property(property="name", type="string", example="iPhone 15 Pro Max"),
 *     @OA\Property(property="slug", type="string", example="iphone-15-pro-max"),
 *     @OA\Property(property="description", type="string", example="Updated description"),
 *     @OA\Property(property="price", type="number", format="float", example=1099.99),
 *     @OA\Property(property="image", type="string", example="https://example.com/new-image.jpg"),
 *     @OA\Property(property="is_active", type="boolean", example=false),
 *     @OA\Property(property="category_id", type="integer", example=2),
 *     @OA\Property(property="brand_id", type="integer", example=1),
 *     @OA\Property(property="store_id", type="integer", example=2)
 * )
 */
final class ProductUpdateRequestSchema {}

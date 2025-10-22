<?php

declare(strict_types=1);

namespace App\Schemas;

/**
 * @OA\Schema(
 *     schema="ProductDetail",
 *     type="object",
 *     title="Product Detail",
 *     description="Detailed product information",
 *     allOf={
 *         @OA\Schema(ref="#/components/schemas/Product"),
 *         @OA\Schema(
 *
 *             @OA\Property(property="reviews", type="array", @OA\Items(ref="#/components/schemas/Review")),
 *         )
 *     }
 * )
 */
final class ProductDetailSchema {}

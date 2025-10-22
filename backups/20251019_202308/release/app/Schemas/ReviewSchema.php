<?php

declare(strict_types=1);

namespace App\Schemas;

/**
 * @OA\Schema(
 *     schema="Review",
 *     type="object",
 *     title="Review",
 *     description="Product review information",
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="user_id", type="integer", example=1),
 *     @OA\Property(property="product_id", type="integer", example=1),
 *     @OA\Property(property="rating", type="integer", minimum=1, maximum=5, example=4),
 *     @OA\Property(property="comment", type="string", example="Great product!"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2023-01-01T00:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2023-01-01T00:00:00Z")
 * )
 */
final class ReviewSchema {}

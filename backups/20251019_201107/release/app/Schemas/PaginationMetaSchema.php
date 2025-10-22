<?php

declare(strict_types=1);

namespace App\Schemas;

/**
 * @OA\Schema(
 *     schema="PaginationMeta",
 *     type="object",
 *     title="Pagination Meta",
 *     description="Pagination metadata for paginated responses",
 *
 *     @OA\Property(property="current_page", type="integer", example=1),
 *     @OA\Property(property="last_page", type="integer", example=10),
 *     @OA\Property(property="per_page", type="integer", example=15),
 *     @OA\Property(property="total", type="integer", example=150),
 *     @OA\Property(property="from", type="integer", example=1),
 *     @OA\Property(property="to", type="integer", example=15)
 * )
 */
final class PaginationMetaSchema {}

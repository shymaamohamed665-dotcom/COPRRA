<?php

declare(strict_types=1);

namespace App\Schemas;

/**
 * @OA\Schema(
 *     schema="PaginationLinks",
 *     type="object",
 *     title="Pagination Links",
 *     description="Pagination links for paginated responses",
 *
 *     @OA\Property(property="first", type="string", example="http://api.example.com/products?page=1"),
 *     @OA\Property(property="last", type="string", example="http://api.example.com/products?page=10"),
 *     @OA\Property(property="prev", type="string", nullable=true, example="http://api.example.com/products?page=1"),
 *     @OA\Property(property="next", type="string", nullable=true, example="http://api.example.com/products?page=3")
 * )
 */
final class PaginationLinksSchema {}

<?php

declare(strict_types=1);

namespace App\Schemas;

/**
 * @OA\Schema(
 *     schema="Brand",
 *     type="object",
 *     title="Brand",
 *     description="Brand model",
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Apple"),
 *     @OA\Property(property="slug", type="string", example="apple"),
 *     @OA\Property(property="logo", type="string", nullable=true, example="https://example.com/logo.png"),
 *     @OA\Property(property="is_active", type="boolean", example=true),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
final class BrandSchema
{
}

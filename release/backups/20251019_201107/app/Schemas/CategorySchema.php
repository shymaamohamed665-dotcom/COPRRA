<?php

declare(strict_types=1);

namespace App\Schemas;

/**
 * @OA\Schema(
 *     schema="Category",
 *     type="object",
 *     title="Category",
 *     description="Category model",
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Smartphones"),
 *     @OA\Property(property="slug", type="string", example="smartphones"),
 *     @OA\Property(property="description", type="string", nullable=true, example="Mobile phones and accessories"),
 *     @OA\Property(property="is_active", type="boolean", example=true),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
final class CategorySchema
{
}

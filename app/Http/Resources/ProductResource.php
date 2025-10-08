<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Product
 */
class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    #[\Override]
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'price' => $this->price,
            'compare_price' => $this->compare_price ?? null,
            'cost_price' => $this->cost_price ?? null,
            'barcode' => $this->barcode ?? null,
            'quantity' => $this->stock_quantity,
            'is_featured' => $this->is_featured ?? null,
            'images' => $this->images ?? [],
            'rating' => $this->rating ?? null,
            'reviews_count' => $this->reviews_count,
            'category' => $this->whenLoaded('category', fn () => class_exists(CategoryResource::class) ? new CategoryResource($this->category) : $this->category),
        ];
    }
}

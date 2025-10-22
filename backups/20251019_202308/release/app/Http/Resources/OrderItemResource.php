<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\OrderItem
 */
class OrderItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<mixed|null>
     *
     * @psalm-return array{id: mixed, product_id: mixed, quantity: mixed, price: mixed|null, subtotal: mixed|null}
     */
    #[\Override]
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'quantity' => $this->quantity,
            'price' => is_object($this->resource) && isset($this->resource->price) ? $this->resource->price : null,
            'subtotal' => is_object($this->resource) && isset($this->resource->subtotal) ? $this->resource->subtotal : null,
        ];
    }
}

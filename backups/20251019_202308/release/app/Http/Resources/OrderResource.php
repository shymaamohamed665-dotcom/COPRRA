<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Order
 */
class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<\Illuminate\Http\Resources\MissingValue|array|mixed|null>
     *
     * @psalm-return array{id: mixed, order_number: mixed, user_id: mixed, status: array{value: mixed, label: mixed, color: mixed}, total_amount: mixed, subtotal: mixed, tax_amount: mixed, shipping_amount: mixed, discount_amount: mixed, currency: mixed, shipping_address: mixed, billing_address: mixed, notes: mixed, created_at: mixed|null, updated_at: mixed|null, shipped_at: \Illuminate\Http\Resources\MissingValue|mixed, delivered_at: \Illuminate\Http\Resources\MissingValue|mixed}
     */
    #[\Override]
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'user_id' => $this->user_id,
            'status' => [
                'value' => $this->status_enum->value,
                'label' => $this->status_enum->label(),
                'color' => $this->status_enum->color(),
            ],
            'total_amount' => $this->total_amount,
            'subtotal' => $this->subtotal,
            'tax_amount' => $this->tax_amount,
            'shipping_amount' => $this->shipping_amount,
            'discount_amount' => $this->discount_amount,
            'currency' => $this->currency,
            'shipping_address' => $this->shipping_address,
            'billing_address' => $this->billing_address,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            // Add null coalescing and type checks
            'shipped_at' => $this->whenNotNull($this->shipped_at?->toIso8601String()),
            'delivered_at' => $this->whenNotNull($this->delivered_at?->toIso8601String()),
        ];
    }
}

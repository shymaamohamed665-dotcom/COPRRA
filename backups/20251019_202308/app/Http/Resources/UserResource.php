<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\User
 *
 * @property string|null $avatar
 * @property string|null $phone
 */
class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<int|mixed|string|null>
     *
     * @psalm-return array{id: int, name: string, email: string, role: string, avatar: string|null, phone: string|null, email_verified_at: string|null, created_at: mixed|null, updated_at: mixed|null}
     *
     * @SuppressWarnings("UnusedFormalParameter")
     */
    #[\Override]
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'avatar' => $this->avatar,
            'phone' => $this->phone,
            'email_verified_at' => $this->email_verified_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

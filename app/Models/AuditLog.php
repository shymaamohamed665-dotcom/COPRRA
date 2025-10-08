<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $event
 * @property string $auditable_type
 * @property int $auditable_id
 * @property int|null $user_id
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property array<string, string|int|bool|null>|null $old_values
 * @property array<string, string|int|bool|null>|null $new_values
 * @property array<string, string|int|bool|null>|null $metadata
 *
 * @pfinal roperty string|null $url
 *
 * @property string|null $method
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property \App\Models\User|null $user
 * @property \Illuminate\Database\Eloquent\Model|null $auditable
 */
class AuditLog extends Model
{
    /** @use HasFactory<\Illuminate\Database\Eloquent\Factories\Factory> */
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'event',
        'auditable_type',
        'auditable_id',
        'user_id',
        'ip_address',
        'user_agent',
        'old_values',
        'new_values',
        'metadata',
        'url',
        'method',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'metadata' => 'array',
    ];
}

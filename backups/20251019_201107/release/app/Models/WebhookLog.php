<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $webhook_id
 * @property string $action
 * @property string $message
 * @property array<string, string|int|bool|null>|null $metadata
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 */
class WebhookLog extends Model
{
    /** @use HasFactory<\Database\Factories\WebhookLogFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'webhook_id',
        'action',
        'message',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'metadata' => 'array',
    ];
}

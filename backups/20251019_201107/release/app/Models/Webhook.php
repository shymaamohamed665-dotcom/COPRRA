<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $store_identifier
 * @property string $event_type
 * @property string $product_identifier
 * @property int|null $product_id
 * @property array<string, string|int|float|bool|null> $payload
 * @property string|null $signature
 * @property string $status
 * @property string|null $error_message
 * @property \Carbon\Carbon|null $processed_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Webhook extends Model
{
    /** @use HasFactory<\Database\Factories\WebhookFactory> */
    use HasFactory;

    /**
     * Event types.
     */
    public const EVENT_PRICE_UPDATE = 'price_update';

    public const EVENT_STOCK_UPDATE = 'stock_update';

    public const EVENT_PRODUCT_UPDATE = 'product_update';

    /**
     * Status values.
     */
    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'store_identifier',
        'event_type',
        'product_identifier',
        'product_id',
        'payload',
        'signature',
        'status',
        'error_message',
        'processed_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'payload' => 'array',
        'processed_at' => 'datetime',
    ];

    /**
     * Get the logs for the webhook.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<WebhookLog, Webhook>
     */
    public function logs(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(WebhookLog::class);
    }

    /**
     * Mark webhook as processing.
     */
    public function markAsProcessing(): void
    {
        $this->update(['status' => self::STATUS_PROCESSING]);
    }

    /**
     * Mark webhook as completed.
     */
    public function markAsCompleted(): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'processed_at' => now(),
        ]);
    }

    /**
     * Mark webhook as failed.
     */
    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'error_message' => $errorMessage,
            'processed_at' => now(),
        ]);
    }

    /**
     * Add log entry.
     *
     * @param  array<string, string|int|bool|null>|null  $metadata
     */
    public function addLog(string $action, string $message, ?array $metadata = null): void
    {
        $this->logs()->create([
            'action' => $action,
            'message' => $message,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Scope a query to only include pending webhooks.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<Webhook>  $query
     *
     * @psalm-return \Illuminate\Database\Eloquent\Builder<self>
     */
    public function scopePending(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope a query to filter webhooks by status.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<Webhook>  $query
     *
     * @psalm-return \Illuminate\Database\Eloquent\Builder<self>
     */
    public function scopeStatus(\Illuminate\Database\Eloquent\Builder $query, string $status): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to filter webhooks by store.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<Webhook>  $query
     *
     * @psalm-return \Illuminate\Database\Eloquent\Builder<self>
     */
    public function scopeStore(\Illuminate\Database\Eloquent\Builder $query, string $storeIdentifier): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('store_identifier', $storeIdentifier);
    }

    /**
     * Scope a query to filter webhooks by event type.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<Webhook>  $query
     *
     * @psalm-return \Illuminate\Database\Eloquent\Builder<self>
     */
    public function scopeEventType(\Illuminate\Database\Eloquent\Builder $query, string $eventType): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('event_type', $eventType);
    }
}

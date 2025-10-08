<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\NotificationStatus;
use Carbon\Carbon;
use Database\Factories\NotificationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $user_id
 * @property string $type
 * @property string $title
 * @property string $message
 * @property array<string, string|int|array<string, string|int>> $data
 * @property Carbon|null $read_at
 * @property Carbon|null $sent_at
 * @property int $priority
 * @property string $channel
 * @property string $status
 * @property array<string, string|int|bool|null>|null $metadata
 * @property array<string> $tags
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property \App\Models\User $user
 *
 * @use HasFactory<NotificationFactory>
 */
class Notification extends Model
{
    /** @use HasFactory<NotificationFactory> */
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'custom_notifications';

    /**
     * @var class-string<\Illuminate\Database\Eloquent\Factories\Factory<Notification>>
     */
    protected static $factory = \Database\Factories\NotificationFactory::class;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'data',
        'read_at',
        'sent_at',
        'priority',
        'channel',
        'status',
        'metadata',
        'tags',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
        'sent_at' => 'datetime',
        'priority' => 'integer',
        'metadata' => 'array',
        'tags' => 'array',
        'status' => NotificationStatus::class,
    ];

    /**
     * @var array<int, string>
     */
    protected $hidden = [
        'data',
    ];

    /**
     * Mark the notification as read.
     */
    public function markAsRead(): bool
    {
        return $this->update(['read_at' => now()]);
    }

    /**
     * Check if the notification is unread.
     */
    public function isUnread(): bool
    {
        return is_null($this->read_at);
    }

    /**
     * Check if the notification is pending.
     */
    public function isPending(): bool
    {
        return is_null($this->attributes['sent_at'] ?? null) && ($this->attributes['status'] ?? '') === 'pending';
    }

    /**
     * Check if the notification is failed.
     */
    public function isFailed(): bool
    {
        return ($this->attributes['status'] ?? '') === 'failed';
    }

    /**
     * Get the notification data with fallback values.
     *
     * @param  array<string, string|int>|int|string|null  $default
     */
    public function getData(?string $key = null, array|string|int|null $default = null): mixed
    {
        if ($key === null) {
            return $this->data ?? [];
        }

        return data_get($this->data, $key, $default);
    }

    /**
     * Update notification data.
     *
     * @param  array<string, string|int>|int  $value
     *
     * @psalm-param array<string, string|int>|int $value
     */
    public function updateData(string $key, array|int $value): bool
    {
        $data = $this->data ?? [];
        data_set($data, $key, $value);

        return $this->update(['data' => $data]);
    }

    /**
     * Get the notification URL if available.
     */
    public function getUrl(): ?string
    {
        $url = $this->getData('url');

        return is_string($url) ? $url : null;
    }

    /**
     * Get the notification expiration date.
     */
    public function getExpirationDate(): ?Carbon
    {
        $expirationDays = $this->getData('expiration_days');

        if ($expirationDays && is_numeric($expirationDays)) {
            return $this->created_at ? $this->created_at->addDays((int) $expirationDays) : now()->addDays((int) $expirationDays);
        }

        return null;
    }

    /**
     * Get the notification retry count.
     */
    public function getRetryCount(): int
    {
        $retryCount = $this->getData('retry_count', 0);

        return is_numeric($retryCount) ? (int) $retryCount : 0;
    }

    /**
     * Get the notification tags.
     *
     * @return array<string>
     */
    public function getTags(): array
    {
        $tags = $this->getData('tags', []);

        return $this->processTags($tags);
    }

    /**
     * Update notification tags.
     *
     * @param  array<string>  $tags
     */
    public function updateTags(array $tags): bool
    {
        return $this->updateData('tags', $tags);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): NotificationFactory
    {
        return NotificationFactory::new();
    }

    /**
     * Process the notification tags.
     *
     * @param  array<string>  $tags
     * @return array<string>
     */
    private function processTags(array $tags): array
    {
        return is_array($tags) ? array_map(static fn ($tag) => is_string($tag) ? $tag : 'tag', $tags) : [];
    }
}

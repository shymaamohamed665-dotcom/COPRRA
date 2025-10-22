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
     * @var array<string, mixed>
     */
    protected $casts = [
        'id' => 'int',
        'old_values' => 'array',
        'new_values' => 'array',
        'metadata' => 'array',
    ];

    /**
     * User who performed the action.
     */
    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The related model that was acted upon.
     */
    public function auditable(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope: filter by event name.
     *
     * @psalm-return \Illuminate\Database\Eloquent\Builder<Model>
     */
    public function scopeEvent(\Illuminate\Database\Eloquent\Builder $query, string $event): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('event', $event);
    }

    /**
     * Scope: filter by user id.
     *
     * @psalm-return \Illuminate\Database\Eloquent\Builder<Model>
     */
    public function scopeForUser(\Illuminate\Database\Eloquent\Builder $query, int $userId): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: filter by auditable model class.
     *
     * @psalm-return \Illuminate\Database\Eloquent\Builder<Model>
     */
    public function scopeForModel(\Illuminate\Database\Eloquent\Builder $query, string $modelClass): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('auditable_type', $modelClass);
    }

    /**
     * Scope: filter by created_at date range.
     *
     * @psalm-return \Illuminate\Database\Eloquent\Builder<Model>
     */
    public function scopeDateRange(\Illuminate\Database\Eloquent\Builder $query, string $startDate, string $endDate): \Illuminate\Database\Eloquent\Builder
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Accessor: formatted event, e.g. "user_created" -> "User created".
     */
    public function getFormattedEventAttribute(): string
    {
        $event = (string) ($this->attributes['event'] ?? '');
        $event = str_replace('_', ' ', $event);

        return ucfirst($event);
    }

    /**
     * Accessor: summary of changes from old_values to new_values.
     */
    public function getChangesSummaryAttribute(): string
    {
        /** @var mixed $old */
        $old = $this->getAttribute('old_values');
        /** @var mixed $new */
        $new = $this->getAttribute('new_values');

        if (! is_array($old) || ! is_array($new)) {
            return 'No changes recorded';
        }

        $keys = array_intersect(array_keys($old), array_keys($new));
        $parts = [];

        foreach ($keys as $key) {
            $oldVal = $old[$key] ?? null;
            $newVal = $new[$key] ?? null;

            if ($oldVal === $newVal) {
                continue;
            }

            $format = static function ($value): string {
                if ($value === null) {
                    return 'null';
                }
                if ($value === true) {
                    return '1';
                }
                if ($value === false) {
                    return '0';
                }

                return (string) $value;
            };

            $parts[] = sprintf('%s: %s → %s', (string) $key, $format($oldVal), $format($newVal));
        }

        return implode(', ', $parts);
    }

    /**
     * Accessor: normalize old_values to array and restore enums where applicable.
     *
     * @return array<string, mixed>|null
     */
    public function getOldValuesAttribute($value): ?array
    {
        $arr = $this->normalizeAttributeToArray($value);

        return $arr === null ? null : $this->restoreEnumsInAttributes($arr);
    }

    /**
     * Accessor: normalize new_values to array and restore enums where applicable.
     *
     * @return array<string, mixed>|null
     */
    public function getNewValuesAttribute($value): ?array
    {
        $arr = $this->normalizeAttributeToArray($value);

        return $arr === null ? null : $this->restoreEnumsInAttributes($arr);
    }

    /**
     * Ensure attribute value is an array when stored as JSON or array-like.
     *
     * @return array<string, mixed>|null
     */
    private function normalizeAttributeToArray(mixed $value): ?array
    {
        if ($value === null) {
            return null;
        }
        if (is_array($value)) {
            return $value;
        }
        if ($value instanceof \ArrayObject) {
            return $value->getArrayCopy();
        }
        if ($value instanceof \Illuminate\Support\Collection) {
            return $value->toArray();
        }
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                return $decoded;
            }
            // If the string represents an empty array, return []
            if (trim($value) === '[]') {
                return [];
            }

            return null;
        }

        return null;
    }

    /**
     * Helper: restore known enum attributes to their Enum instances.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function restoreEnumsInAttributes(array $attributes): array
    {
        try {
            if (array_key_exists('role', $attributes) && is_string($attributes['role'])) {
                $attributes['role'] = \App\Enums\UserRole::from($attributes['role']);
            }
        } catch (\Throwable $e) {
            // Fail gracefully if enum restoration is not possible
        }

        return $attributes;
    }
}

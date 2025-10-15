<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuditService
{
    /**
     * Log an audit event.
     *
     * @param  array<string, string|int|float|bool|null>|null  $oldValues
     * @param  array<string, string|int|float|bool|null>|null  $newValues
     * @param  array<string, string|int|float|bool|null>|null  $metadata
     */
    public function log(
        string $event,
        Model $model,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?array $metadata = null,
        ?Request $request = null
    ): void {
        $user = Auth::user();
        $request ??= request();

        AuditLog::create([
            'event' => $event,
            'auditable_type' => $model::class,
            'auditable_id' => $model->getKey(),
            'user_id' => $user?->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'metadata' => $metadata,
            'url' => $request->fullUrl(),
            'method' => $request->method(),
        ]);
    }

    /**
     * Log model creation.
     *
     * @param  array<string, string|int|float|bool|null>|null  $metadata
     */
    public function logCreated(Model $model, ?array $metadata = null): void
    {
        $this->log('created', $model, null, $model->getAttributes(), $metadata);
    }

    /**
     * Log model update.
     *
     * @param  array<string, string|int|float|bool|null>  $oldValues
     * @param  array<string, string|int|float|bool|null>|null  $metadata
     */
    public function logUpdated(Model $model, array $oldValues, ?array $metadata = null): void
    {
        $this->log('updated', $model, $oldValues, $model->getChanges(), $metadata);
    }

    /**
     * Log model deletion.
     *
     * @param  array<string, string|int|float|bool|null>|null  $metadata
     */
    public function logDeleted(Model $model, ?array $metadata = null): void
    {
        $this->log('deleted', $model, $model->getAttributes(), null, $metadata);
    }

    /**
     * Log sensitive operations.
     *
     * @param  array<string, string|int|float|bool|null>|null  $metadata
     */
    public function logSensitiveOperation(
        string $operation,
        Model $model,
        ?array $metadata = null
    ): void {
        $this->log($operation, $model, null, null, $metadata);
    }

    /**
     * Log a viewed event for a model.
     */
    public function logViewed(Model $model, ?array $metadata = null): void
    {
        $this->log('viewed', $model, null, null, $metadata);
    }

    /**
     * Log authentication-related events (e.g., login, logout).
     * If userId is null, use the currently authenticated user.
     */
    public function logAuthEvent(string $event, ?int $userId = null, ?Request $request = null): void
    {
        $targetUser = $userId !== null ? User::query()->select('id')->find($userId) : Auth::user();
        if ($targetUser instanceof User) {
            $this->log($event, $targetUser, null, null, null, $request);
        }
    }

    /**
     * Log an API access event performed by the authenticated user against a target user.
     * Metadata includes endpoint, method, and any additional provided info.
     *
     * @param  array<string, int|string|float|bool|null>  $metadata
     */
    public function logApiAccess(string $endpoint, string $method, int $targetUserId, array $metadata = [], ?Request $request = null): void
    {
        $targetUser = User::query()->select('id')->find($targetUserId);
        if (! ($targetUser instanceof User)) {
            return;
        }

        $combinedMeta = array_merge([
            'endpoint' => $endpoint,
            'method' => $method,
        ], $metadata);

        $this->log('api_access', $targetUser, null, null, $combinedMeta, $request);
    }

    /**
     * Get logs for a specific model ordered by most recent.
     */
    public function getModelLogs(Model $model): \Illuminate\Support\Collection
    {
        return AuditLog::query()
            ->forModel($model::class)
            ->where('auditable_id', $model->getKey())
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Get logs for a specific user ordered by most recent.
     */
    public function getUserLogs(int $userId): \Illuminate\Support\Collection
    {
        return AuditLog::query()
            ->forUser($userId)
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Get logs for a specific event name ordered by most recent.
     */
    public function getEventLogs(string $event): \Illuminate\Support\Collection
    {
        return AuditLog::query()
            ->event($event)
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Delete logs older than the given number of days.
     */
    public function cleanOldLogs(int $days): int
    {
        return (int) AuditLog::query()
            ->where('created_at', '<', now()->subDays($days))
            ->delete();
    }
}

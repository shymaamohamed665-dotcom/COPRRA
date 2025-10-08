<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

final class AuditService
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
}

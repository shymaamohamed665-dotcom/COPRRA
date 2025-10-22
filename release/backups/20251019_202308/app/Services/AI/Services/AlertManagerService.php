<?php

declare(strict_types=1);

namespace App\Services\AI\Services;

/**
 * AlertManagerService
 * خدمة مبسطة لإدارة التنبيهات.
 */
final class AlertManagerService
{
    /**
     * إنشاء تنبيه افتراضي.
     */
    public function createDefaultAlert(): array
    {
        return ['type' => 'info', 'message' => 'Alert created'];
    }
}

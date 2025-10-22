<?php

declare(strict_types=1);

namespace App\Services\AI\Services;

/**
 * HealthScoreService
 * خدمة مبسطة لحساب/إدارة درجات الصحة الخاصة بأنظمة المراقبة.
 */
final class HealthScoreService
{
    /**
     * إرجاع قيمة صحة افتراضية.
     */
    public function getDefaultScore(): int
    {
        return 100;
    }
}

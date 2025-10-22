<?php

declare(strict_types=1);

namespace App\Services\AI\Services;

/**
 * RuleExecutorService
 * خدمة مبسطة لتنفيذ قواعد/سياسات المراقبة.
 */
final class RuleExecutorService
{
    /**
     * تنفيذ قاعدة افتراضية وإرجاع النجاح.
     */
    public function execute(string $ruleId, array $context = []): bool
    {
        return true;
    }
}

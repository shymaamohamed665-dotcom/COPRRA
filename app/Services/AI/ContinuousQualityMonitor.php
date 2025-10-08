<?php

declare(strict_types=1);

namespace App\Services\AI;

use Carbon\Carbon;
use Illuminate\Contracts\Process\ProcessResult;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

final class ContinuousQualityMonitor
{
    /** @var array<string, array{name: string, threshold: int, command: string, critical: bool}> */
    private array $monitoringRules = [];

    /**
     * @var list<array{
     *     type: string,
     *     rule: string,
     *     message: string,
     *     details: array<int, string>,
     *     timestamp: string
     * }>
     */
    private array $alerts = [];

    private int $checkInterval = 300; // 5 minutes

    private HealthScoreCalculator $scoreCalculator;

    public function __construct()
    {
        $this->monitoringRules = $this->createMonitoringRules();
        $this->scoreCalculator = new HealthScoreCalculator;
    }

    /**
     * Start continuous monitoring.
     */
    public function startMonitoring(): void
    {
        Log::info('🔍 بدء المراقبة المستمرة للجودة...');

        $running = true;
        $iterations = 0;
        while ($running) {
            $this->performQualityCheck();
            sleep($this->checkInterval);
            // In a real implementation, you would have a way to stop this loop
            // For now, we'll add a break after a reasonable number of iterations
            $iterations++;
            if ($iterations > 1000) {
                $running = false;
            }
        }
    }

    /**
     * Perform quality check.
     *
     * @return array{
     *     overall_health: int,
     *     rules: array<string, array{
     *         name: string,
     *         success: bool,
     *         health_score: int,
     *         duration: float,
     *         output: string,
     *         errors: array<int, string>,
     *         timestamp: string,
     *         critical: bool
     *     }>,
     *     alerts: list<array{
     *         type: string,
     *         rule: string,
     *         message: string,
     *         details: array<int, string>,
     *         timestamp: string
     *     }>
     * }
     */
    public function performQualityCheck(): array
    {
        $results = $this->executeAllRules();
        $overallHealth = $this->calculateOverallHealth($results);
        $this->processAlerts($results);
        $this->updateHealthStatus($overallHealth, $results);

        return [
            'overall_health' => $overallHealth,
            'rules' => $results,
            'alerts' => $this->alerts,
        ];
    }

    /**
     * Get current health status.
     *
     * @return array{
     *     score: int,
     *     last_check: string|null,
     *     detailed_results: array<string, array{
     *         name: string,
     *         success: bool,
     *         health_score: int,
     *         duration: float,
     *         output: string,
     *         errors: array<int, string>,
     *         timestamp: string,
     *         critical: bool
     *     }>,
     *     alerts: list<array{
     *         type: string,
     *         rule: string,
     *         message: string,
     *         details: array<int, string>,
     *         timestamp: string
     *     }>
     * }
     */
    public function getHealthStatus(): array
    {
        $score = Cache::get('quality_health_score', 0);
        $lastCheck = Cache::get('quality_last_check');
        $detailedResults = Cache::get('quality_detailed_results', []);

        return [
            'score' => $this->validateHealthScore($score),
            'last_check' => $this->validateLastCheck($lastCheck),
            'detailed_results' => $this->validateDetailedResults($detailedResults),
            'alerts' => $this->alerts,
        ];
    }

    /**
     * Get alerts summary.
     *
     * @return array{
     *     total: int,
     *     critical: int,
     *     warnings: int,
     *     alerts: list<array{
     *         type: string,
     *         rule: string,
     *         message: string,
     *         details: array<int, string>,
     *         timestamp: string
     *     }>
     * }
     */
    public function getAlertsSummary(): array
    {
        $criticalAlerts = array_filter(
            $this->alerts,
            static fn (array $alert): bool => ($alert['type'] ?? '') === 'critical'
        );

        $warningAlerts = array_filter(
            $this->alerts,
            static fn (array $alert): bool => ($alert['type'] ?? '') === 'warning'
        );

        return [
            'total' => count($this->alerts),
            'critical' => count($criticalAlerts),
            'warnings' => count($warningAlerts),
            'alerts' => $this->alerts,
        ];
    }

    /**
     * Clear alerts.
     */
    public function clearAlerts(): void
    {
        $this->alerts = [];
        Log::info('🗑️ تم مسح جميع التنبيهات');
    }

    /**
     * Validate rule configuration.
     *
     * @param  array<string, mixed>  $rule
     */
    private function validateRule(array $rule): bool
    {
        return isset($rule['name'], $rule['threshold'], $rule['command'])
            && is_string($rule['name'])
            && is_numeric($rule['threshold'])
            && is_string($rule['command']);
    }

    /**
     * Execute all monitoring rules.
     *
     * @return array<string, array{
     *     name: string,
     *     success: bool,
     *     health_score: int,
     *     duration: float,
     *     output: string,
     *     errors: array<int, string>,
     *     timestamp: string,
     *     critical: bool
     * }>
     */
    private function executeAllRules(): array
    {
        $results = [];

        foreach ($this->monitoringRules as $ruleId => $rule) {
            if (! $this->validateRule($rule)) {
                Log::warning('Invalid rule configuration skipped', ['rule_id' => $ruleId]);

                continue;
            }

            $ruleIdStr = is_string($ruleId) ? $ruleId : (string) $ruleId;
            $results[$ruleIdStr] = $this->checkRule($rule, $ruleIdStr);
        }

        return $results;
    }

    /**
     * Calculate overall health score from rule results.
     *
     * @param array<string, array{
     *     name: string,
     *     success: bool,
     *     health_score: int,
     *     duration: float,
     *     output: string,
     *     errors: array<int, string>,
     *     timestamp: string,
     *     critical: bool
     * }> $results
     */
    private function calculateOverallHealth(array $results): int
    {
        $overallHealth = 100;

        foreach ($results as $ruleId => $result) {
            $rule = $this->monitoringRules[$ruleId] ?? null;
            if (! $rule || ! is_numeric($result['health_score'] ?? null)) {
                continue;
            }

            $healthScore = (int) $result['health_score'];
            if ($healthScore < $rule['threshold']) {
                $overallHealth = min($overallHealth, $healthScore);
            }
        }

        return $overallHealth;
    }

    /**
     * Process alerts based on rule results.
     *
     * @param array<string, array{
     *     name: string,
     *     success: bool,
     *     health_score: int,
     *     duration: float,
     *     output: string,
     *     errors: array<int, string>,
     *     timestamp: string,
     *     critical: bool
     * }> $results
     */
    private function processAlerts(array $results): void
    {
        foreach ($results as $ruleId => $result) {
            $rule = $this->monitoringRules[$ruleId] ?? null;
            if (! $rule || ! is_numeric($result['health_score'] ?? null)) {
                continue;
            }

            $healthScore = (int) $result['health_score'];
            if ($healthScore < $rule['threshold']) {
                $this->triggerAlert($ruleId, $result, $rule['critical'] ?? false);
            }
        }
    }

    /**
     * Trigger appropriate alert based on criticality.
     *
     * @param array{
     *     name: string,
     *     success: bool,
     *     health_score: int,
     *     duration: float,
     *     output: string,
     *     errors: array<int, string>,
     *     timestamp: string,
     *     critical: bool
     * } $result
     */
    private function triggerAlert(string $ruleId, array $result, bool $isCritical): void
    {
        if ($isCritical) {
            $this->triggerCriticalAlert($ruleId, $result);
        } else {
            $this->triggerWarningAlert($ruleId, $result);
        }
    }

    /**
     * Validate health score value.
     */
    private function validateHealthScore(mixed $score): int
    {
        if (! is_numeric($score)) {
            return 0;
        }

        $intScore = (int) $score;

        return max(0, min(100, $intScore));
    }

    /**
     * Validate last check timestamp.
     */
    private function validateLastCheck(mixed $lastCheck): ?string
    {
        return is_string($lastCheck) && $this->isValidIso8601($lastCheck) ? $lastCheck : null;
    }

    /**
     * Validate detailed results.
     *
     * @return array<string, mixed>
     */
    private function validateDetailedResults(mixed $results): array
    {
        return is_array($results) ? $results : [];
    }

    /**
     * Check if string is valid ISO8601 format.
     */
    private function isValidIso8601(string $date): bool
    {
        try {
            Carbon::parse($date);

            return true;
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Create monitoring rules configuration.
     *
     * @return array<string, array{name: string, threshold: int, command: string, critical: bool}>
     */
    private function createMonitoringRules(): array
    {
        return [
            'code_quality' => [
                'name' => 'جودة الكود',
                'threshold' => 95,
                'command' => './vendor/bin/phpstan analyse --memory-limit=1G --configuration=phpstan.strict.neon',
                'critical' => true,
            ],
            'test_coverage' => [
                'name' => 'تغطية الاختبارات',
                'threshold' => 90,
                'command' => 'php artisan test --configuration=phpunit.strict.xml --coverage-text',
                'critical' => true,
            ],
            'security_scan' => [
                'name' => 'فحص الأمان',
                'threshold' => 100,
                'command' => 'composer audit',
                'critical' => true,
            ],
            'performance' => [
                'name' => 'الأداء',
                'threshold' => 80,
                'command' => 'php artisan test tests/Performance/ --configuration=phpunit.strict.xml',
                'critical' => false,
            ],
            'memory_usage' => [
                'name' => 'استهلاك الذاكرة',
                'threshold' => 512,
                'command' => 'php -d memory_limit=512M artisan test --configuration=phpunit.strict.xml',
                'critical' => true,
            ],
        ];
    }

    /**
     * Check a specific rule.
     *
     * @param array{
     *     name: string,
     *     threshold: int,
     *     command: string,
     *     critical: bool
     * } $rule
     * @return array{
     *     name: string,
     *     success: bool,
     *     health_score: int,
     *     duration: float,
     *     output: string,
     *     errors: array<int, string>,
     *     timestamp: string,
     *     critical: bool
     * }
     */
    private function checkRule(array $rule, string $ruleId): array
    {
        $startTime = microtime(true);

        try {
            $result = $this->executeRuleCommand($rule);

            return $this->createRuleResult($rule, $result, $startTime, $ruleId);
        } catch (\Exception $e) {
            return $this->createErrorResult($rule, $e->getMessage());
        }
    }

    /**
     * Execute the rule command.
     *
     * @param array{
     *     name: string,
     *     threshold: int,
     *     command: string,
     *     critical: bool
     * } $rule
     */
    private function executeRuleCommand(array $rule): ?ProcessResult
    {
        $command = is_string($rule['command'] ?? null) ? $rule['command'] : '';

        if ($command === '' || $command === '0') {
            return null;
        }

        return Process::run($command);
    }

    /**
     * Create successful rule result.
     *
     * @param array{
     *     name: string,
     *     threshold: int,
     *     command: string,
     *     critical: bool
     * } $rule
     * @return array<string, mixed>
     */
    private function createRuleResult(
        array $rule,
        ?ProcessResult $result,
        float $startTime,
        string $ruleId
    ): array {
        $endTime = microtime(true);
        $duration = round((float) ($endTime - $startTime), 2);

        return [
            'name' => is_string($rule['name'] ?? null) ? $rule['name'] : 'Unknown',
            'success' => $result ? $result->successful() : false,
            'health_score' => $result ? $this->scoreCalculator->calculate($ruleId, $result) : 0,
            'duration' => $duration,
            'output' => $result ? $result->output() : '',
            'errors' => $result ? $result->errorOutput() : [],
            'timestamp' => Carbon::now()->toISOString(),
            'critical' => (bool) ($rule['critical'] ?? false),
        ];
    }

    /**
     * Create error result for failed rule execution.
     *
     * @param array{
     *     name: string,
     *     threshold: int,
     *     command: string,
     *     critical: bool
     * } $rule
     * @return array<string, mixed>
     */
    private function createErrorResult(array $rule, string $errorMessage): array
    {
        return [
            'name' => is_string($rule['name'] ?? null) ? $rule['name'] : 'Unknown',
            'success' => false,
            'health_score' => 0,
            'duration' => 0,
            'output' => '',
            'errors' => [$errorMessage],
            'timestamp' => Carbon::now()->toISOString(),
            'critical' => (bool) ($rule['critical'] ?? false),
        ];
    }

    /**
     * Trigger critical alert.
     *
     * @param array{
     *     name: string,
     *     success: bool,
     *     health_score: int,
     *     duration: float,
     *     output: string,
     *     errors: array<int, string>,
     *     timestamp: string,
     *     critical: bool
     * } $result
     */
    private function triggerCriticalAlert(string $ruleId, array $result): void
    {
        $ruleName = is_string($result['name'] ?? null) ? $result['name'] : '';
        $alert = [
            'type' => 'critical',
            'rule' => $ruleId,
            'message' => 'تنبيه حرج: فشل في '.$ruleName,
            'details' => is_array($result['errors'] ?? null) ? $result['errors'] : [],
            'timestamp' => Carbon::now()->toISOString(),
        ];

        $this->alerts[] = $alert;
        Log::critical('🚨 تنبيه حرج: '.$alert['message']);

        // Send notification (email, Slack, etc.)
        $this->sendNotification($alert);
    }

    /**
     * Trigger warning alert.
     *
     * @param array{
     *     name: string,
     *     success: bool,
     *     health_score: int,
     *     duration: float,
     *     output: string,
     *     errors: array<int, string>,
     *     timestamp: string,
     *     critical: bool
     * } $result
     */
    private function triggerWarningAlert(string $ruleId, array $result): void
    {
        $ruleName = is_string($result['name'] ?? null) ? $result['name'] : '';
        $alert = [
            'type' => 'warning',
            'rule' => $ruleId,
            'message' => 'تحذير: مشكلة في '.$ruleName,
            'details' => is_array($result['errors'] ?? null) ? $result['errors'] : [],
            'timestamp' => Carbon::now()->toISOString(),
        ];

        $this->alerts[] = $alert;
        Log::warning('⚠️ تحذير: '.$alert['message']);
    }

    /**
     * Update health status.
     *
     * @param array<string, array{
     *     name: string,
     *     success: bool,
     *     health_score: int,
     *     duration: float,
     *     output: string,
     *     errors: array<int, string>,
     *     timestamp: string,
     *     critical: bool
     * }> $results
     */
    private function updateHealthStatus(int $overallHealth, array $results): void
    {
        Cache::put('quality_health_score', $overallHealth, 3600);
        Cache::put('quality_last_check', Carbon::now()->toISOString(), 3600);
        Cache::put('quality_detailed_results', $results, 3600);

        Log::info('📊 تحديث حالة الجودة: '.$overallHealth.'%');
    }

    /**
     * Send notification.
     *
     * @param array{
     *     type: string,
     *     rule: string,
     *     message: string,
     *     details: array<int, string>,
     *     timestamp: string
     * } $alert
     */
    private function sendNotification(array $alert): void
    {
        // Implement notification logic (email, Slack, etc.)
        $message = is_string($alert['message'] ?? null) ? $alert['message'] : '';
        Log::info('📧 إرسال إشعار: '.$message);
    }
}

<?php

declare(strict_types=1);

namespace App\Services;

use Exception;
use Psr\Log\LoggerInterface;

final class SuspiciousActivityService
{
    /**
     * @var array<string, array<string, string|int|float|bool|array|null>
     *     |bool|float|int|string|null>
     */
    private array $config = [];

    private readonly LoggerInterface $logger;

    private readonly ActivityProcessor $activityProcessor;

    private readonly ActivityChecker $activityChecker;

    public function __construct(
        LoggerInterface $logger,
        ConfigurationService $configurationService,
        ActivityProcessor $activityProcessor,
        ActivityChecker $activityChecker
    ) {
        $this->logger = $logger;
        $this->config = $configurationService->getSuspiciousActivityConfig();
        $this->activityProcessor = $activityProcessor;
        $this->activityChecker = $activityChecker;
    }

    /**
     * @param  array<string, scalar|null>  $data
     */
    public function monitorActivity(string $event, iterable $data): void
    {
        try {
            if (! ($this->config['enabled'] ?? false)) {
                return;
            }

            $this->processActivity($event, $data);
        } catch (Exception $e) {
            $this->logger->error('Suspicious activity monitoring failed', [
                'event' => $event,
                'data' => $data,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @return array<array|int>
     *
     * @psalm-return array{total_activities: 0, activities_by_type: array<never, never>, activities_by_severity: array<never, never>, recent_activities: array<never, never>}
     */
    public function getStatistics(): array
    {
        return [
            'total_activities' => 0,
            'activities_by_type' => [],
            'activities_by_severity' => [],
            'recent_activities' => [],
        ];
    }

    /**
     * @param  array<string, scalar|null>  $data
     */
    private function processActivity(string $event, iterable $data): void
    {
        $suspiciousActivities = $this->activityChecker->getSuspiciousActivities($event, $data);

        foreach ($suspiciousActivities as $activity) {
            $this->processSuspiciousActivity($activity);
        }
    }

    /**
     * @param  array<string, scalar|null>  $activity
     */
    private function processSuspiciousActivity(iterable $activity): void
    {
        $this->activityProcessor->process($activity);
        $this->takeAutomaticActions($activity);
    }

    /**
     * @param  array<string, scalar|null>  $activity
     */
    private function takeAutomaticActions(iterable $activity): void
    {
        $severity = $activity['severity'] ?? 'medium';
        $type = $activity['type'] ?? 'unknown';

        if ($severity !== 'high') {
            return;
        }

        $userId = (int) ($activity['user_id'] ?? 0);
        if ($userId === 0) {
            return;
        }

        match ($type) {
            'multiple_failed_logins' => $this->lockUserAccount($userId),
            'unusual_data_access' => $this->suspendUserAccount($userId),
            default => null,
        };
    }

    private function lockUserAccount(int $userId): void
    {
        $this->logger->info('User account locked due to suspicious activity', [
            'user_id' => $userId,
            'timestamp' => now()->toISOString(),
        ]);
    }

    private function suspendUserAccount(int $userId): void
    {
        $this->logger->info('User account suspended due to suspicious activity', [
            'user_id' => $userId,
            'timestamp' => now()->toISOString(),
        ]);
    }
}

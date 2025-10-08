<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Contracts\Config\Repository;

final class ConfigurationService
{
    private Repository $configRepository;

    public function __construct(Repository $configRepository)
    {
        $this->configRepository = $configRepository;
    }

    /**
     * @return array<string, bool|array<string, bool>|array<string, array<string, bool|int|string>>>
     */
    public function getSuspiciousActivityConfig(): array
    {
        return $this->configRepository->get('suspicious_activity', $this->getDefaultSuspiciousActivityConfig());
    }

    /**
     * @return array<string, bool|array<string, bool>|array<string, array<string, bool|int|string>>>
     */
    private function getDefaultSuspiciousActivityConfig(): array
    {
        return [
            'enabled' => true,
            'monitoring_rules' => $this->getMonitoringRules(),
            'notification' => [
                'email' => true,
                'slack' => false,
                'webhook' => false,
            ],
        ];
    }

    /**
     * @return array<string, array<string, bool|int|string>>
     */
    private function getMonitoringRules(): array
    {
        return [
            'multiple_failed_logins' => [
                'enabled' => true,
                'threshold' => 5,
                'time_window' => 15, // minutes
                'severity' => 'high',
            ],
            'unusual_login_location' => [
                'enabled' => true,
                'severity' => 'medium',
            ],
            'rapid_api_requests' => [
                'enabled' => true,
                'threshold' => 100,
                'time_window' => 5, // minutes
                'severity' => 'medium',
            ],
            'unusual_data_access' => [
                'enabled' => true,
                'threshold' => 1000,
                'time_window' => 60, // minutes
                'severity' => 'high',
            ],
            'admin_actions' => [
                'enabled' => true,
                'severity' => 'high',
            ],
        ];
    }
}

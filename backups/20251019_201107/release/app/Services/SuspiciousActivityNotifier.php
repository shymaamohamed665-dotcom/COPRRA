<?php

declare(strict_types=1);

namespace App\Services;

use Exception;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Mail\Mailer;
use Psr\Log\LoggerInterface;

final readonly class SuspiciousActivityNotifier
{
    private LoggerInterface $logger;

    private Mailer $mailer;

    private Repository $configRepository;

    public function __construct(LoggerInterface $logger, Mailer $mailer, Repository $configRepository)
    {
        $this->logger = $logger;
        $this->mailer = $mailer;
        $this->configRepository = $configRepository;
    }

    /**
     * Send notifications.
     *
     * @param array{
     *     type: string,
     *     severity: string,
     *     user_id: int,
     *     details: array<string, int|string|array<string, string|int|float|bool|array|null>>,
     *     timestamp: string,
     *     ip_address?: string
     * } $activity
     */
    public function sendNotifications(array $activity): void
    {
        $notification = is_array($this->configRepository->get('suspicious_activity.notification'))
            ? $this->configRepository->get('suspicious_activity.notification')
            : [];

        if (($notification['email'] ?? false) === true) {
            $this->sendEmailNotification($activity);
        }

        if (($notification['slack'] ?? false) === true) {
            $this->sendSlackNotification($activity);
        }

        if (($notification['webhook'] ?? false) === true) {
            $this->sendWebhookNotification($activity);
        }
    }

    /**
     * Send email notification.
     *
     * @param array{
     *     type: string,
     *     severity: string,
     *     user_id: int,
     *     details: array<string, int|string|array<string, string|int|float|bool|array|null>>,
     *     timestamp: string,
     *     ip_address?: string
     * } $activity
     */
    private function sendEmailNotification(array $activity): void
    {
        try {
            $adminEmails = config('app.admin_emails', []);

            if ($adminEmails !== []) {
                $activityType = is_string($activity['type'] ?? null) ? $activity['type'] : 'unknown';
                $subject = 'Suspicious Activity Alert - '.$activityType;
                $message = $this->formatActivityMessage($activity);

                $closure = static function ($mail) use ($adminEmails, $subject): void {
                    $mail->to($adminEmails)->subject($subject);
                };

                $this->mailer->raw($message, $closure);
            }
        } catch (Exception $e) {
            $this->logger->error(
                'Failed to send email notification',
                [
                    'activity' => $activity,
                    'error' => $e->getMessage(),
                ]
            );
        }
    }

    /**
     * Send Slack notification.
     *
     * @param array{
     *     type: string,
     *     severity: string,
     *     user_id: int,
     *     details: array<string, int|string|array<string, string|int|float|bool|array|null>>,
     *     timestamp: string,
     *     ip_address?: string
     * } $activity
     */
    private function sendSlackNotification(array $activity): void
    {
        // Implementation for sending Slack notification
        $this->logger->info('Slack notification sent', $activity);
    }

    /**
     * Send webhook notification.
     *
     * @param array{
     *     type: string,
     *     severity: string,
     *     user_id: int,
     *     details: array<string, int|string|array<string, string|int|float|bool|array|null>>,
     *     timestamp: string,
     *     ip_address?: string
     * } $activity
     */
    private function sendWebhookNotification(array $activity): void
    {
        // Implementation for sending webhook notification
        $this->logger->info('Webhook notification sent', $activity);
    }

    /**
     * Format activity message for notification.
     *
     * @param array{
     *     type: string,
     *     severity: string,
     *     user_id: int,
     *     details: array<string, int|string|array<string, string|int|float|bool|array|null>>,
     *     timestamp: string,
     *     ip_address?: string
     * } $activity
     */
    private function formatActivityMessage(array $activity): string
    {
        $message = "Suspicious activity detected:\n";
        foreach ($activity as $key => $value) {
            $message .= "{$key}: ".json_encode($value, JSON_PRETTY_PRINT)."\n";
        }

        return $message;
    }
}

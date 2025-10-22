<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\WebhookService;
use Illuminate\Http\JsonResponse;

class WebhookController extends Controller
{
    public function __construct(
        private readonly WebhookService $webhookService
    ) {
    }

    /**
     * @param  array<string, string|int|float|bool|array<string, string|int|float|bool>|null>  $payload
     */
    public function handleWebhook(
        string $storeIdentifier,
        string $eventType,
        array $payload,
        ?string $signature = null
    ): JsonResponse {
        $webhook = $this->webhookService->handleWebhook(
            $storeIdentifier,
            $eventType,
            $payload,
            $signature
        );

        return response()->json(['status' => 'received', 'webhook_id' => $webhook->id]);
    }
}

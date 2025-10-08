<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use App\Models\Webhook;
use Exception;
use Illuminate\Contracts\Bus\Dispatcher;
use Psr\Log\LoggerInterface;

final class WebhookService
{
    private CacheService $cacheService;

    private LoggerInterface $logger;

    private Dispatcher $dispatcher;

    private Webhook $webhook;

    private Product $product;

    public function __construct(
        CacheService $cacheService,
        LoggerInterface $logger,
        Dispatcher $dispatcher,
        Webhook $webhook,
        Product $product
    ) {
        $this->cacheService = $cacheService;
        $this->logger = $logger;
        $this->dispatcher = $dispatcher;
        $this->webhook = $webhook;
        $this->product = $product;
    }

    /**
     * Handle incoming webhook.
     *
     * @param  array<string, scalar|array|null>  $payload
     */
    public function handleWebhook(
        string $storeIdentifier,
        string $eventType,
        array $payload,
        ?string $signature = null
    ): Webhook {
        // Create webhook record
        $webhook = $this->webhook->create([
            'store_identifier' => $storeIdentifier,
            'event_type' => $eventType,
            'product_identifier' => $payload['product_identifier'] ?? '',
            'payload' => $payload,
            'signature' => $signature,
            'status' => Webhook::STATUS_PENDING,
        ]);

        $webhook->addLog('received', 'Webhook received from store', [
            'store' => $storeIdentifier,
            'event' => $eventType,
        ]);

        // Process webhook asynchronously
        $this->dispatcher->dispatch(function () use ($webhook): void {
            $this->processWebhook($webhook);
        })->afterResponse();

        return $webhook;
    }

    /**
     * Process webhook.
     */
    public function processWebhook(Webhook $webhook): void
    {
        try {
            $this->prepareWebhookForProcessing($webhook);
            $this->processWebhookEvent($webhook);
            $this->finalizeWebhookProcessing($webhook);
        } catch (Exception $e) {
            $this->handleWebhookProcessingError($webhook, $e);
        }
    }

    private function prepareWebhookForProcessing(Webhook $webhook): void
    {
        $webhook->markAsProcessing();
        $webhook->addLog('processing', 'Started processing webhook');

        if ($webhook->signature && ! $this->verifySignature($webhook)) {
            throw new Exception('Invalid webhook signature');
        }
    }

    private function processWebhookEvent(Webhook $webhook): void
    {
        $product = $this->findOrCreateProduct($webhook);

        if ($product) {
            $webhook->update(['product_id' => $product->id]);
        }

        match ($webhook->event_type) {
            Webhook::EVENT_PRICE_UPDATE => $this->handlePriceUpdate($webhook, $product),
            Webhook::EVENT_STOCK_UPDATE => $this->handleStockUpdate($webhook, $product),
            Webhook::EVENT_PRODUCT_UPDATE => $this->handleProductUpdate($webhook, $product),
            default => throw new Exception("Unknown event type: {$webhook->event_type}"),
        };
    }

    private function finalizeWebhookProcessing(Webhook $webhook): void
    {
        $webhook->markAsCompleted();
        $webhook->addLog('completed', 'Webhook processed successfully');
    }

    private function handleWebhookProcessingError(Webhook $webhook, Exception $e): void
    {
        $webhook->markAsFailed($e->getMessage());
        $webhook->addLog('failed', 'Webhook processing failed', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        $this->logger->error('Webhook processing failed', [
            'webhook_id' => $webhook->id,
            'error' => $e->getMessage(),
        ]);
    }

    /**
     * Handle price update event.
     */
    private function handlePriceUpdate(Webhook $webhook, ?Product $product): void
    {
        if (! $product) {
            throw new Exception('Product not found for price update');
        }

        $payload = $webhook->payload;
        /** @var array<string, scalar|array|null> $payload */
        /** @var string|int|float|null $priceValue */
        $priceValue = $payload['price'] ?? null;
        $newPrice = is_numeric($priceValue) ? (float) $priceValue : null;

        if (! $newPrice) {
            throw new Exception('Price not provided in payload');
        }

        $this->updateProductPrice(
            $product,
            $webhook->store_identifier,
            $newPrice,
            $payload['currency'] ?? 'USD'
        );

        // Invalidate cache
        $this->cacheService->invalidateProduct($product->id);

        $newPriceStr = strval($newPrice);
        $webhook->addLog('price_updated', 'Price updated to '.$newPriceStr, [
            'new_price' => $newPrice,
        ]);
    }

    private function updateProductPrice(
        Product $product,
        string $storeIdentifier,
        float $newPrice,
        string $currency
    ): void {
        /** @var array<string, array{
         *     price: float,
         *     currency: string,
         *     updated_at: string
         * }> $storePrices */
        $storePrices = $product->store_prices ?? [];
        if (! is_array($storePrices)) {
            $storePrices = [];
        }
        $storePrices[$storeIdentifier] = [
            'price' => $newPrice,
            'currency' => $currency,
            'updated_at' => now()->toIso8601String(),
        ];

        $product->update(['store_prices' => $storePrices]);
    }

    /**
     * Handle stock update event.
     */
    private function handleStockUpdate(Webhook $webhook, ?Product $product): void
    {
        if (! $product) {
            throw new Exception('Product not found for stock update');
        }

        $payload = $webhook->payload;
        $inStock = $payload['in_stock'] ?? null;

        if ($inStock === null) {
            throw new Exception('Stock status not provided in payload');
        }

        /** @var string|int|null $quantityValue */
        $quantityValue = $payload['quantity'] ?? null;
        $quantity = is_numeric($quantityValue) ? (int) $quantityValue : null;

        // Update product stock for this store
        $storeStock = $product->store_stock ?? [];
        if (! is_array($storeStock)) {
            $storeStock = [];
        }

        $storeStock[$webhook->store_identifier] = [
            'in_stock' => $inStock,
            'quantity' => $quantity,
            'updated_at' => now()->toIso8601String(),
        ];

        $product->update(['store_stock' => $storeStock]);

        // Invalidate cache
        $this->cacheService->invalidateProduct($product->id);

        $webhook->addLog('stock_updated', 'Stock status updated', [
            'in_stock' => $inStock,
            'quantity' => $quantity,
        ]);
    }

    /**
     * Handle product update event.
     */
    private function handleProductUpdate(Webhook $webhook, ?Product $product): void
    {
        if (! $product) {
            throw new Exception('Product not found for product update');
        }

        $payload = $webhook->payload;
        $updates = $this->getProductUpdatesFromPayload($payload);

        if ($updates !== []) {
            $product->update($updates);

            // Invalidate cache
            $this->cacheService->invalidateProduct($product->id);

            $webhook->addLog('product_updated', 'Product details updated', $updates);
        }
    }

    /**
     * @param  iterable<string, scalar|array|null>  $payload
     * @return array<string, scalar|array|null>
     */
    private function getProductUpdatesFromPayload(iterable $payload): array
    {
        $updates = [];

        if (isset($payload['title']) && is_string($payload['title'])) {
            $updates['name'] = $payload['name'];
        }

        if (isset($payload['description'])) {
            $updates['description'] = $payload['description'];
        }

        if (isset($payload['image_url'])) {
            $updates['image_url'] = $payload['image_url'];
        }

        return $updates;
    }

    /**
     * Find or create product from webhook.
     */
    private function findOrCreateProduct(Webhook $webhook): ?Product
    {
        $productIdentifier = $webhook->product_identifier;

        // Try to find existing product by store mapping
        $product = $this->product->whereJsonContains(
            "store_mappings->{$webhook->store_identifier}",
            $productIdentifier
        )->first();

        if (
            ! $product &&
            isset($webhook->payload['create_if_not_exists']) &&
            $webhook->payload['create_if_not_exists']
        ) {
            // Create new product
            $product = $this->product->create([
                'name' => $webhook->payload['name'] ?? 'Unknown Product',
                'description' => $webhook->payload['description'] ?? null,
                'image_url' => $webhook->payload['image_url'] ?? null,
                'store_mappings' => [
                    $webhook->store_identifier => $productIdentifier,
                ],
            ]);

            $webhook->addLog('product_created', 'New product created', [
                'product_id' => $product->id,
            ]);
        }

        return $product;
    }

    /**
     * Verify webhook signature.
     */
    private function verifySignature(Webhook $webhook): bool
    {
        $secret = config("services.{$webhook->store_identifier}.webhook_secret");

        if (! $secret) {
            return true; // No secret configured, skip verification
        }

        $payload = $webhook->payload;
        if (! is_array($payload)) {
            return false; // Invalid payload format
        }

        $payloadJson = json_encode($payload);
        if ($payloadJson === false) {
            return false; // Invalid payload
        }

        $secretStr = strval($secret);
        $expectedSignature = hash_hmac('sha256', $payloadJson, $secretStr);

        return hash_equals((string) $webhook->signature, $expectedSignature);
    }
}

<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PriceOffer;
use App\Models\Product;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final readonly class FinancialTransactionService
{
    public function __construct(private AuditService $auditService) {}

    public function updateProductPrice(Product $product, float $newPrice, ?string $reason = null): bool
    {
        /** @var bool $result */
        $result = DB::transaction(function () use ($product, $newPrice, $reason): bool {
            $oldPrice = (float) $product->price;
            $this->validatePrice($newPrice);

            $product->update(['price' => $newPrice]);

            $this->logPriceUpdate($product, $oldPrice, $newPrice, $reason);

            $this->checkPriceAlerts();

            return true;
        });

        return $result;
    }

    /**
     * @psalm-param array{product_id:int|string, new_price:numeric-string|float, price?:float, is_available?:bool, expires_at?:string|null, status?:string} $offerData
     */
    public function createPriceOffer(array $offerData): PriceOffer
    {
        /** @var PriceOffer $priceOffer */
        $priceOffer = DB::transaction(function () use ($offerData): PriceOffer {
            $this->validateOfferData($offerData);

            // Map new_price to actual persisted price column
            if (isset($offerData['new_price'])) {
                $offerData['price'] = (float) $offerData['new_price'];
                unset($offerData['new_price']);
            }

            // Default new offers to available unless explicitly provided
            $offerData['is_available'] = isset($offerData['is_available']) ? $offerData['is_available'] : true;

            $offerData['status'] = 'active';
            $newOffer = PriceOffer::query()->create($offerData);

            $this->logOfferCreation($newOffer);

            $this->updateProductPriceFromOffer($newOffer);

            return $newOffer;
        });

        return $priceOffer;
    }

    /**
     * @param  array<string, mixed>  $updateData
     */
    public function updatePriceOffer(PriceOffer $priceOffer, array $updateData): PriceOffer
    {
        /** @var PriceOffer $updated */
        $updated = DB::transaction(function () use ($priceOffer, $updateData): PriceOffer {
            $this->validateOfferUpdateData($updateData);

            // Map new_price to actual persisted price column on updates
            if (isset($updateData['new_price'])) {
                $updateData['price'] = (float) $updateData['new_price'];
                unset($updateData['new_price']);
            }

            $oldData = $priceOffer->toArray();
            $priceOffer->update($updateData);

            $this->logOfferUpdate($priceOffer, $oldData);

            $this->updateProductPriceFromOffer($priceOffer);

            return $priceOffer;
        });

        return $updated;
    }

    public function deletePriceOffer(PriceOffer $priceOffer): bool
    {
        /** @var bool $deleted */
        $deleted = DB::transaction(function () use ($priceOffer): bool {
            $priceOffer->delete();

            $this->logOfferDeletion($priceOffer);

            return true;
        });

        return $deleted;
    }

    private function validatePrice(float $price): void
    {
        if ($price < 0) {
            throw new Exception('Price cannot be negative');
        }

        if ($price > 1000000) {
            throw new Exception('Price exceeds maximum allowed value');
        }
    }

    private function logPriceUpdate(Product $product, float $oldPrice, float $newPrice, ?string $reason): void
    {
        $this->auditService->logUpdated($product, ['price' => $oldPrice], [
            'reason' => $reason,
            'old_price' => $oldPrice,
            'new_price' => $newPrice,
            'price_change' => $newPrice - $oldPrice,
            'percentage_change' => $oldPrice > 0 ? ($newPrice - $oldPrice) / $oldPrice * 100 : 0,
        ]);

        Log::info('Product price updated successfully', [
            'product_id' => $product->id,
            'old_price' => $oldPrice,
            'new_price' => $newPrice,
            'reason' => $reason,
        ]);
    }

    private function validateOfferData(array $offerData): void
    {
        if (! isset($offerData['product_id']) || ! isset($offerData['new_price'])) {
            throw new Exception('Missing required offer data');
        }

        if (! is_numeric($offerData['new_price']) || $offerData['new_price'] < 0) {
            throw new Exception('Invalid price for offer');
        }

        if (isset($offerData['expires_at']) && ! strtotime($offerData['expires_at'])) {
            throw new Exception('Invalid expiration date for offer');
        }
    }

    private function logOfferCreation(PriceOffer $priceOffer): void
    {
        $this->auditService->logCreated($priceOffer);

        Log::info('Price offer created successfully', ['offer_id' => $priceOffer->id]);
    }

    private function validateOfferUpdateData(array $updateData): void
    {
        if (isset($updateData['new_price']) && (! is_numeric($updateData['new_price']) || $updateData['new_price'] < 0)) {
            throw new Exception('Invalid price for offer');
        }

        if (isset($updateData['expires_at']) && ! strtotime($updateData['expires_at'])) {
            throw new Exception('Invalid expiration date for offer');
        }
    }

    private function logOfferUpdate(PriceOffer $priceOffer, array $oldData): void
    {
        $this->auditService->logUpdated($priceOffer, $oldData);

        Log::info('Price offer updated successfully', ['offer_id' => $priceOffer->id]);
    }

    private function logOfferDeletion(PriceOffer $priceOffer): void
    {
        $this->auditService->logDeleted($priceOffer);

        Log::info('Price offer deleted successfully', ['offer_id' => $priceOffer->id]);
    }

    private function updateProductPriceFromOffer(PriceOffer $priceOffer): void
    {
        $product = $priceOffer->product;
        if (! $product) {
            return;
        }

        $lowestOffer = PriceOffer::where('product_id', $product->id)
            ->where('is_available', true)
            ->orderBy('price')
            ->first();

        if ($lowestOffer && $product->price !== $lowestOffer->price) {
            $this->updateProductPrice($product, (float) $lowestOffer->price, 'Updated from price offer');
        }
    }

    private function checkPriceAlerts(): void
    {
        // This would integrate with the notification system
        // to send alerts when price drops below target
    }
}

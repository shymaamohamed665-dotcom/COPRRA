<?php

declare(strict_types=1);

namespace App\Services;

/**
 * ShippingService
 * خدمة مبسطة للتعامل مع الشحن.
 */
final class ShippingService
{
    /**
     * إرجاع تكلفة شحن افتراضية.
     */
    public function getDefaultShippingCost(): float
    {
        return 0.0;
    }
}

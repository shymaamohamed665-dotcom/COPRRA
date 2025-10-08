<?php

namespace Tests\Unit\Recommendations;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class BestDealRecommendationTest extends TestCase
{
    #[Test]
    #[CoversNothing]
    public function it_identifies_best_deals(): void
    {
        $products = [
            ['id' => 1, 'name' => 'iPhone 15', 'price' => 999.00, 'original_price' => 1199.00, 'discount' => 200.00],
            ['id' => 2, 'name' => 'Samsung Galaxy S24', 'price' => 899.00, 'original_price' => 999.00, 'discount' => 100.00],
            ['id' => 3, 'name' => 'Google Pixel 8', 'price' => 699.00, 'original_price' => 799.00, 'discount' => 100.00],
            ['id' => 4, 'name' => 'OnePlus 12', 'price' => 599.00, 'original_price' => 699.00, 'discount' => 100.00],
        ];

        $bestDeals = $this->getBestDeals($products, 2);
        $this->assertCount(2, $bestDeals);
        $this->assertEquals(1, $bestDeals[0]['id']); // iPhone 15 has highest discount
    }

    #[Test]
    #[CoversNothing]
    public function it_calculates_deal_score(): void
    {
        $product = [
            'id' => 1,
            'name' => 'iPhone 15',
            'price' => 999.00,
            'original_price' => 1199.00,
            'discount' => 200.00,
            'rating' => 4.5,
            'reviews_count' => 1000,
        ];

        $dealScore = $this->calculateDealScore($product);
        $this->assertGreaterThan(0, $dealScore);
        $this->assertLessThanOrEqual(100, $dealScore);
    }

    #[Test]
    #[CoversNothing]
    public function it_identifies_percentage_discounts(): void
    {
        $products = [
            ['id' => 1, 'name' => 'iPhone 15', 'price' => 800.00, 'original_price' => 1000.00],
            ['id' => 2, 'name' => 'Samsung Galaxy S24', 'price' => 700.00, 'original_price' => 1000.00],
            ['id' => 3, 'name' => 'Google Pixel 8', 'price' => 600.00, 'original_price' => 800.00],
        ];

        $percentageDiscounts = $this->getPercentageDiscounts($products);
        $this->assertCount(3, $percentageDiscounts);
        $this->assertEquals(30.0, $percentageDiscounts[0]['discount_percentage']); // 30% discount
    }

    #[Test]
    #[CoversNothing]
    public function it_identifies_absolute_discounts(): void
    {
        $products = [
            ['id' => 1, 'name' => 'iPhone 15', 'price' => 800.00, 'original_price' => 1000.00],
            ['id' => 2, 'name' => 'Samsung Galaxy S24', 'price' => 700.00, 'original_price' => 1000.00],
            ['id' => 3, 'name' => 'Google Pixel 8', 'price' => 600.00, 'original_price' => 800.00],
        ];

        $absoluteDiscounts = $this->getAbsoluteDiscounts($products);
        $this->assertCount(3, $absoluteDiscounts);
        $this->assertEquals(300.00, $absoluteDiscounts[0]['discount_amount']); // $300 discount
    }

    #[Test]
    #[CoversNothing]
    public function it_identifies_value_for_money_deals(): void
    {
        $products = [
            ['id' => 1, 'name' => 'iPhone 15', 'price' => 999.00, 'rating' => 4.5, 'features' => 10],
            ['id' => 2, 'name' => 'Samsung Galaxy S24', 'price' => 899.00, 'rating' => 4.3, 'features' => 8],
            ['id' => 3, 'name' => 'Google Pixel 8', 'price' => 699.00, 'rating' => 4.4, 'features' => 9],
        ];

        $valueDeals = $this->getValueForMoneyDeals($products);
        $this->assertCount(3, $valueDeals);
        $this->assertEquals(3, $valueDeals[0]['id']); // Google Pixel 8 has best value
    }

    #[Test]
    #[CoversNothing]
    public function it_identifies_limited_time_deals(): void
    {
        $deals = [
            ['id' => 1, 'name' => 'Flash Sale', 'discount' => 30.0, 'end_date' => '2024-12-31', 'is_limited' => true],
            ['id' => 2, 'name' => 'Regular Sale', 'discount' => 20.0, 'end_date' => '2025-12-31', 'is_limited' => false],
            ['id' => 3, 'name' => 'Weekend Special', 'discount' => 25.0, 'end_date' => '2024-12-25', 'is_limited' => true],
        ];

        $limitedDeals = $this->getLimitedTimeDeals($deals, '2024-12-20');
        $this->assertCount(2, $limitedDeals);
        $this->assertEquals(1, $limitedDeals[0]['id']); // Highest discount
    }

    #[Test]
    #[CoversNothing]
    public function it_identifies_bulk_deals(): void
    {
        $products = [
            ['id' => 1, 'name' => 'iPhone 15', 'price' => 999.00, 'bulk_discount' => 0.0],
            ['id' => 2, 'name' => 'Samsung Galaxy S24', 'price' => 899.00, 'bulk_discount' => 10.0],
            ['id' => 3, 'name' => 'Google Pixel 8', 'price' => 699.00, 'bulk_discount' => 15.0],
        ];

        $bulkDeals = $this->getBulkDeals($products);
        $this->assertCount(2, $bulkDeals);
        $this->assertEquals(3, $bulkDeals[0]['id']); // Highest bulk discount
    }

    #[Test]
    #[CoversNothing]
    public function it_identifies_bundle_deals(): void
    {
        $bundles = [
            ['id' => 1, 'name' => 'Phone + Case', 'price' => 1099.00, 'individual_price' => 1199.00, 'savings' => 100.00],
            ['id' => 2, 'name' => 'Phone + Headphones', 'price' => 1299.00, 'individual_price' => 1399.00, 'savings' => 100.00],
            ['id' => 3, 'name' => 'Phone + Case + Headphones', 'price' => 1399.00, 'individual_price' => 1599.00, 'savings' => 200.00],
        ];

        $bundleDeals = $this->getBundleDeals($bundles);
        $this->assertCount(3, $bundleDeals);
        $this->assertEquals(3, $bundleDeals[0]['id']); // Highest savings
    }

    #[Test]
    #[CoversNothing]
    public function it_identifies_cashback_deals(): void
    {
        $products = [
            ['id' => 1, 'name' => 'iPhone 15', 'price' => 999.00, 'cashback' => 50.00],
            ['id' => 2, 'name' => 'Samsung Galaxy S24', 'price' => 899.00, 'cashback' => 100.00],
            ['id' => 3, 'name' => 'Google Pixel 8', 'price' => 699.00, 'cashback' => 75.00],
        ];

        $cashbackDeals = $this->getCashbackDeals($products);
        $this->assertCount(3, $cashbackDeals);
        $this->assertEquals(2, $cashbackDeals[0]['id']); // Highest cashback
    }

    #[Test]
    #[CoversNothing]
    public function it_identifies_free_shipping_deals(): void
    {
        $products = [
            ['id' => 1, 'name' => 'iPhone 15', 'price' => 999.00, 'shipping_cost' => 0.00, 'free_shipping' => true],
            ['id' => 2, 'name' => 'Samsung Galaxy S24', 'price' => 899.00, 'shipping_cost' => 15.00, 'free_shipping' => false],
            ['id' => 3, 'name' => 'Google Pixel 8', 'price' => 699.00, 'shipping_cost' => 0.00, 'free_shipping' => true],
        ];

        $freeShippingDeals = $this->getFreeShippingDeals($products);
        $this->assertCount(2, $freeShippingDeals);
        $this->assertEquals(1, $freeShippingDeals[0]['id']);
    }

    #[Test]
    #[CoversNothing]
    public function it_identifies_clearance_deals(): void
    {
        $products = [
            ['id' => 1, 'name' => 'Old Model Phone', 'price' => 499.00, 'original_price' => 999.00, 'is_clearance' => true],
            ['id' => 2, 'name' => 'New Model Phone', 'price' => 999.00, 'original_price' => 999.00, 'is_clearance' => false],
            ['id' => 3, 'name' => 'Discontinued Item', 'price' => 299.00, 'original_price' => 599.00, 'is_clearance' => true],
        ];

        $clearanceDeals = $this->getClearanceDeals($products);
        $this->assertCount(2, $clearanceDeals);
        $this->assertEquals(3, $clearanceDeals[0]['id']); // Highest discount
    }

    #[Test]
    #[CoversNothing]
    public function it_identifies_seasonal_deals(): void
    {
        $products = [
            ['id' => 1, 'name' => 'Winter Jacket', 'price' => 199.00, 'original_price' => 299.00, 'season' => 'winter'],
            ['id' => 2, 'name' => 'Summer Dress', 'price' => 99.00, 'original_price' => 149.00, 'season' => 'summer'],
            ['id' => 3, 'name' => 'Sunglasses', 'price' => 79.00, 'original_price' => 129.00, 'season' => 'summer'],
        ];

        $seasonalDeals = $this->getSeasonalDeals($products, 'winter');
        $this->assertCount(1, $seasonalDeals);
        $this->assertEquals(1, $seasonalDeals[0]['id']);
    }

    #[Test]
    #[CoversNothing]
    public function it_identifies_best_overall_deals(): void
    {
        $products = [
            ['id' => 1, 'name' => 'iPhone 15', 'price' => 999.00, 'original_price' => 1199.00, 'rating' => 4.5, 'reviews_count' => 1000],
            ['id' => 2, 'name' => 'Samsung Galaxy S24', 'price' => 899.00, 'original_price' => 999.00, 'rating' => 4.3, 'reviews_count' => 800],
            ['id' => 3, 'name' => 'Google Pixel 8', 'price' => 699.00, 'original_price' => 799.00, 'rating' => 4.4, 'reviews_count' => 600],
        ];

        $bestOverallDeals = $this->getBestOverallDeals($products, 2);
        $this->assertCount(2, $bestOverallDeals);
        $this->assertEquals(1, $bestOverallDeals[0]['id']); // Best overall deal
    }

    /**
     * @param  array<int, array<string, mixed>>  $products
     * @return array<int, array<string, mixed>>
     */
    /**
     * @param  array<int, array<string, mixed>>  $products
     * @return array<int, array<string, mixed>>
     */
    private function getBestDeals(array $products, int $limit): array
    {
        // Calculate deal score for each product
        foreach ($products as &$product) {
            $product['deal_score'] = $this->calculateDealScore($product);
        }

        // Sort by deal score descending
        usort($products, function ($a, $b) {
            return $b['deal_score'] <=> $a['deal_score'];
        });

        return array_slice($products, 0, $limit);
    }

    /**
     * @param  array<string, mixed>  $product
     */
    private function calculateDealScore(array $product): float
    {
        $price = $product['price'] ?? 0;
        $originalPrice = $product['original_price'] ?? $price;
        $discount = $product['discount'] ?? 0;
        $rating = $product['rating'] ?? 0;
        $reviewsCount = $product['reviews_count'] ?? 0;

        // Calculate discount percentage
        $originalPriceFloat = is_numeric($originalPrice) ? (float) $originalPrice : 0.0;
        $priceFloat = is_numeric($price) ? (float) $price : 0.0;
        $discountPercentage = $originalPriceFloat > 0 ? (($originalPriceFloat - $priceFloat) / $originalPriceFloat) * 100 : 0;

        // Calculate deal score (0-100)
        $ratingFloat = is_numeric($rating) ? (float) $rating : 0.0;
        $reviewsCountFloat = is_numeric($reviewsCount) ? (float) $reviewsCount : 0.0;
        $score = ($discountPercentage * 0.4) + ($ratingFloat * 10 * 0.3) + (min($reviewsCountFloat / 100, 10) * 0.3);

        return min(100, max(0, $score));
    }

    /**
     * @param  array<int, array<string, mixed>>  $products
     * @return array<int, array<string, mixed>>
     */
    private function getPercentageDiscounts(array $products): array
    {
        $discounts = [];

        foreach ($products as $product) {
            $price = $product['price'] ?? 0;
            $originalPrice = $product['original_price'] ?? $price;

            $priceFloat = is_numeric($price) ? (float) $price : 0.0;
            $originalPriceFloat = is_numeric($originalPrice) ? (float) $originalPrice : 0.0;

            if ($originalPriceFloat > 0) {
                $discountPercentage = (($originalPriceFloat - $priceFloat) / $originalPriceFloat) * 100;
                $discounts[] = [
                    'product_id' => $product['id'],
                    'name' => $product['name'],
                    'discount_percentage' => round($discountPercentage, 2),
                ];
            }
        }

        // Sort by discount percentage descending
        usort($discounts, function ($a, $b) {
            return $b['discount_percentage'] <=> $a['discount_percentage'];
        });

        return $discounts;
    }

    /**
     * @param  array<int, array<string, mixed>>  $products
     * @return array<int, array<string, mixed>>
     */
    private function getAbsoluteDiscounts(array $products): array
    {
        $discounts = [];

        foreach ($products as $product) {
            $price = $product['price'] ?? 0;
            $originalPrice = $product['original_price'] ?? $price;

            $priceFloat = is_numeric($price) ? (float) $price : 0.0;
            $originalPriceFloat = is_numeric($originalPrice) ? (float) $originalPrice : 0.0;
            $discountAmount = $originalPriceFloat - $priceFloat;

            $discounts[] = [
                'product_id' => $product['id'],
                'name' => $product['name'],
                'discount_amount' => $discountAmount,
            ];
        }

        // Sort by discount amount descending
        usort($discounts, function ($a, $b) {
            return $b['discount_amount'] <=> $a['discount_amount'];
        });

        return $discounts;
    }

    /**
     * @param  array<int, array<string, mixed>>  $products
     * @return array<int, array<string, mixed>>
     */
    private function getValueForMoneyDeals(array $products): array
    {
        $valueDeals = [];

        foreach ($products as $product) {
            $price = $product['price'] ?? 0;
            $rating = $product['rating'] ?? 0;
            $features = $product['features'] ?? 0;

            $priceFloat = is_numeric($price) ? (float) $price : 0.0;
            $ratingFloat = is_numeric($rating) ? (float) $rating : 0.0;
            $featuresFloat = is_numeric($features) ? (float) $features : 0.0;

            // Calculate value score (rating and features per dollar)
            $valueScore = $priceFloat > 0 ? (($ratingFloat * $featuresFloat) / $priceFloat) * 100 : 0;

            $valueDeals[] = [
                'id' => $product['id'],
                'product_id' => $product['id'],
                'name' => $product['name'],
                'value_score' => $valueScore,
            ];
        }

        // Sort by value score descending
        usort($valueDeals, function ($a, $b) {
            return $b['value_score'] <=> $a['value_score'];
        });

        return $valueDeals;
    }

    /**
     * @param  array<int, array<string, mixed>>  $deals
     * @return array<int, array<string, mixed>>
     */
    private function getLimitedTimeDeals(array $deals, string $currentDate): array
    {
        $limitedDeals = array_filter($deals, function ($deal) use ($currentDate) {
            $endDate = $deal['end_date'] ?? '';

            return $deal['is_limited'] === true && is_string($endDate) && strtotime($endDate) >= strtotime($currentDate);
        });

        // Sort by discount descending
        usort($limitedDeals, function ($a, $b) {
            return $b['discount'] <=> $a['discount'];
        });

        return $limitedDeals;
    }

    /**
     * @param  array<int, array<string, mixed>>  $products
     * @return array<int, array<string, mixed>>
     */
    private function getBulkDeals(array $products): array
    {
        $bulkDeals = array_filter($products, function ($product) {
            return ($product['bulk_discount'] ?? 0) > 0;
        });

        // Sort by bulk discount descending
        usort($bulkDeals, function ($a, $b) {
            return $b['bulk_discount'] <=> $a['bulk_discount'];
        });

        return $bulkDeals;
    }

    /**
     * @param  array<int, array<string, mixed>>  $bundles
     * @return array<int, array<string, mixed>>
     */
    private function getBundleDeals(array $bundles): array
    {
        // Sort by savings descending
        usort($bundles, function ($a, $b) {
            return $b['savings'] <=> $a['savings'];
        });

        return $bundles;
    }

    /**
     * @param  array<int, array<string, mixed>>  $products
     * @return array<int, array<string, mixed>>
     */
    private function getCashbackDeals(array $products): array
    {
        $cashbackDeals = array_filter($products, function ($product) {
            return ($product['cashback'] ?? 0) > 0;
        });

        // Sort by cashback amount descending
        usort($cashbackDeals, function ($a, $b) {
            return $b['cashback'] <=> $a['cashback'];
        });

        return $cashbackDeals;
    }

    /**
     * @param  array<int, array<string, mixed>>  $products
     * @return array<int, array<string, mixed>>
     */
    private function getFreeShippingDeals(array $products): array
    {
        $freeShippingDeals = array_filter($products, function ($product) {
            return $product['free_shipping'] === true;
        });

        // Sort by price descending (most expensive first)
        usort($freeShippingDeals, function ($a, $b) {
            return $b['price'] <=> $a['price'];
        });

        return $freeShippingDeals;
    }

    /**
     * @param  array<int, array<string, mixed>>  $products
     * @return array<int, array<string, mixed>>
     */
    private function getClearanceDeals(array $products): array
    {
        $clearanceDeals = array_filter($products, function ($product) {
            return $product['is_clearance'] === true;
        });

        // Sort by discount percentage descending
        usort($clearanceDeals, function ($a, $b) {
            $originalPriceA = is_numeric($a['original_price']) ? (float) $a['original_price'] : 0.0;
            $priceA = is_numeric($a['price']) ? (float) $a['price'] : 0.0;
            $originalPriceB = is_numeric($b['original_price']) ? (float) $b['original_price'] : 0.0;
            $priceB = is_numeric($b['price']) ? (float) $b['price'] : 0.0;

            $discountA = $originalPriceA > 0 ? (($originalPriceA - $priceA) / $originalPriceA) * 100 : 0;
            $discountB = $originalPriceB > 0 ? (($originalPriceB - $priceB) / $originalPriceB) * 100 : 0;

            return $discountB <=> $discountA;
        });

        return $clearanceDeals;
    }

    /**
     * @param  array<int, array<string, mixed>>  $products
     * @return array<int, array<string, mixed>>
     */

    /**
     * @param  array<int, array<string, mixed>>  $products
     * @return array<int, array<string, mixed>>
     */
    private function getSeasonalDeals(array $products, string $season): array
    {
        $seasonalDeals = array_filter($products, function ($product) use ($season) {
            return $product['season'] === $season;
        });

        // Sort by discount percentage descending
        usort($seasonalDeals, function ($a, $b) {
            $originalPriceA = is_numeric($a['original_price']) ? (float) $a['original_price'] : 0.0;
            $priceA = is_numeric($a['price']) ? (float) $a['price'] : 0.0;
            $originalPriceB = is_numeric($b['original_price']) ? (float) $b['original_price'] : 0.0;
            $priceB = is_numeric($b['price']) ? (float) $b['price'] : 0.0;

            $discountA = $originalPriceA > 0 ? (($originalPriceA - $priceA) / $originalPriceA) * 100 : 0;
            $discountB = $originalPriceB > 0 ? (($originalPriceB - $priceB) / $originalPriceB) * 100 : 0;

            return $discountB <=> $discountA;
        });

        return $seasonalDeals;
    }

    /**
     * @param  array<int, array<string, mixed>>  $products
     * @return array<int, array<string, mixed>>
     */

    /**
     * @param  array<int, array<string, mixed>>  $products
     * @return array<int, array<string, mixed>>
     */
    private function getBestOverallDeals(array $products, int $limit): array
    {
        // Calculate overall deal score for each product
        foreach ($products as &$product) {
            $product['overall_score'] = $this->calculateDealScore($product);
        }

        // Sort by overall score descending
        usort($products, function ($a, $b) {
            return $b['overall_score'] <=> $a['overall_score'];
        });

        return array_slice($products, 0, $limit);
    }
}

<?php

namespace Tests\Unit\Recommendations;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class TrendingProductTest extends TestCase
{
    #[Test]
    public function it_identifies_trending_products(): void
    {
        $products = [
            ['id' => 1, 'name' => 'iPhone 15', 'views' => 1000, 'purchases' => 50],
            ['id' => 2, 'name' => 'Samsung Galaxy S24', 'views' => 800, 'purchases' => 30],
            ['id' => 3, 'name' => 'Google Pixel 8', 'views' => 600, 'purchases' => 20],
            ['id' => 4, 'name' => 'OnePlus 12', 'views' => 400, 'purchases' => 10],
        ];

        $trendingProducts = $this->getTrendingProducts($products);
        $this->assertCount(2, $trendingProducts);
        $this->assertEquals(1, $trendingProducts[0]['id']); // iPhone 15 should be first
    }

    #[Test]
    public function it_calculates_trending_score(): void
    {
        $product = [
            'id' => 1,
            'name' => 'iPhone 15',
            'views' => 1000,
            'purchases' => 50,
            'recent_views' => 200,
            'recent_purchases' => 15,
        ];

        $score = $this->calculateTrendingScore($product);
        $this->assertGreaterThan(0, $score);
        $this->assertLessThanOrEqual(100, $score);
    }

    #[Test]
    public function it_identifies_rising_trends(): void
    {
        $products = [
            ['id' => 1, 'name' => 'iPhone 15', 'current_week_views' => 100, 'previous_week_views' => 50],
            ['id' => 2, 'name' => 'Samsung Galaxy S24', 'current_week_views' => 80, 'previous_week_views' => 90],
            ['id' => 3, 'name' => 'Google Pixel 8', 'current_week_views' => 60, 'previous_week_views' => 30],
        ];

        $risingTrends = $this->getRisingTrends($products);
        $this->assertCount(2, $risingTrends);
        $this->assertEquals(1, $risingTrends[0]['id']); // iPhone 15 should be first
    }

    #[Test]
    public function it_identifies_falling_trends(): void
    {
        $products = [
            ['id' => 1, 'name' => 'iPhone 15', 'current_week_views' => 50, 'previous_week_views' => 100],
            ['id' => 2, 'name' => 'Samsung Galaxy S24', 'current_week_views' => 90, 'previous_week_views' => 80],
            ['id' => 3, 'name' => 'Google Pixel 8', 'current_week_views' => 30, 'previous_week_views' => 60],
        ];

        $fallingTrends = $this->getFallingTrends($products);
        $this->assertCount(2, $fallingTrends);
        $this->assertEquals(1, $fallingTrends[0]['id']); // iPhone 15 should be first
    }

    #[Test]
    public function it_identifies_seasonal_trends(): void
    {
        $products = [
            ['id' => 1, 'name' => 'Winter Jacket', 'season' => 'winter', 'current_season_views' => 500],
            ['id' => 2, 'name' => 'Summer Dress', 'season' => 'summer', 'current_season_views' => 50],
            ['id' => 3, 'name' => 'Sunglasses', 'season' => 'summer', 'current_season_views' => 300],
        ];

        $seasonalTrends = $this->getSeasonalTrends($products, 'winter');
        $this->assertCount(1, $seasonalTrends);
        $this->assertEquals(1, $seasonalTrends[0]['id']);
    }

    #[Test]
    public function it_identifies_category_trends(): void
    {
        $products = [
            ['id' => 1, 'name' => 'iPhone 15', 'category' => 'Smartphones', 'views' => 1000],
            ['id' => 2, 'name' => 'Samsung Galaxy S24', 'category' => 'Smartphones', 'views' => 800],
            ['id' => 3, 'name' => 'MacBook Pro', 'category' => 'Laptops', 'views' => 600],
            ['id' => 4, 'name' => 'Dell XPS', 'category' => 'Laptops', 'views' => 400],
        ];

        $categoryTrends = $this->getCategoryTrends($products);
        $this->assertCount(2, $categoryTrends);
        $this->assertEquals('Smartphones', $categoryTrends[0]['category']);
    }

    #[Test]
    public function it_identifies_brand_trends(): void
    {
        $products = [
            ['id' => 1, 'name' => 'iPhone 15', 'brand' => 'Apple', 'views' => 1000],
            ['id' => 2, 'name' => 'MacBook Pro', 'brand' => 'Apple', 'views' => 800],
            ['id' => 3, 'name' => 'Samsung Galaxy S24', 'brand' => 'Samsung', 'views' => 600],
            ['id' => 4, 'name' => 'Samsung TV', 'brand' => 'Samsung', 'views' => 400],
        ];

        $brandTrends = $this->getBrandTrends($products);
        $this->assertCount(2, $brandTrends);
        $this->assertEquals('Apple', $brandTrends[0]['brand']);
    }

    #[Test]
    public function it_identifies_price_range_trends(): void
    {
        $products = [
            ['id' => 1, 'name' => 'iPhone 15', 'price' => 999.00, 'views' => 1000],
            ['id' => 2, 'name' => 'Samsung Galaxy S24', 'price' => 899.00, 'views' => 800],
            ['id' => 3, 'name' => 'Budget Phone', 'price' => 199.00, 'views' => 600],
            ['id' => 4, 'name' => 'Luxury Phone', 'price' => 1999.00, 'views' => 200],
        ];

        $priceRangeTrends = $this->getPriceRangeTrends($products);
        $this->assertCount(4, $priceRangeTrends);
        $this->assertEquals('mid-range', $priceRangeTrends[0]['range']);
    }

    #[Test]
    public function it_identifies_time_based_trends(): void
    {
        $products = [
            ['id' => 1, 'name' => 'iPhone 15', 'views_today' => 100, 'views_yesterday' => 50],
            ['id' => 2, 'name' => 'Samsung Galaxy S24', 'views_today' => 80, 'views_yesterday' => 90],
            ['id' => 3, 'name' => 'Google Pixel 8', 'views_today' => 60, 'views_yesterday' => 30],
        ];

        $timeBasedTrends = $this->getTimeBasedTrends($products);
        $this->assertCount(2, $timeBasedTrends);
        $this->assertEquals(1, $timeBasedTrends[0]['id']);
    }

    #[Test]
    public function it_identifies_geographic_trends(): void
    {
        $products = [
            ['id' => 1, 'name' => 'iPhone 15', 'region' => 'US', 'views' => 1000],
            ['id' => 2, 'name' => 'Samsung Galaxy S24', 'region' => 'EU', 'views' => 800],
            ['id' => 3, 'name' => 'Google Pixel 8', 'region' => 'US', 'views' => 600],
            ['id' => 4, 'name' => 'OnePlus 12', 'region' => 'Asia', 'views' => 400],
        ];

        $geographicTrends = $this->getGeographicTrends($products);
        $this->assertCount(3, $geographicTrends);
        $this->assertEquals('US', $geographicTrends[0]['region']);
    }

    #[Test]
    public function it_identifies_demographic_trends(): void
    {
        $products = [
            ['id' => 1, 'name' => 'iPhone 15', 'age_group' => '18-25', 'views' => 1000],
            ['id' => 2, 'name' => 'Samsung Galaxy S24', 'age_group' => '26-35', 'views' => 800],
            ['id' => 3, 'name' => 'Google Pixel 8', 'age_group' => '18-25', 'views' => 600],
            ['id' => 4, 'name' => 'OnePlus 12', 'age_group' => '36-45', 'views' => 400],
        ];

        $demographicTrends = $this->getDemographicTrends($products);
        $this->assertCount(3, $demographicTrends);
        $this->assertEquals('18-25', $demographicTrends[0]['age_group']);
    }

    /**
     * @param  array<int, array<string, mixed>>  $products
     * @return array<int, array<string, mixed>>
     */
    private function getTrendingProducts(array $products, int $limit = 2): array
    {
        // Calculate trending score for each product
        foreach ($products as &$product) {
            $product['trending_score'] = $this->calculateTrendingScore($product);
        }

        // Sort by trending score descending
        usort($products, function ($a, $b) {
            return $b['trending_score'] <=> $a['trending_score'];
        });

        return array_slice($products, 0, $limit);
    }

    /**
     * @param  array<string, mixed>  $product
     */
    private function calculateTrendingScore(array $product): float
    {
        $views = $product['views'] ?? 0;
        $purchases = $product['purchases'] ?? 0;
        $recentViews = $product['recent_views'] ?? 0;
        $recentPurchases = $product['recent_purchases'] ?? 0;

        // Weight recent activity more heavily
        $score = (is_numeric($views) ? (float) $views : 0.0) * 0.3 +
                 (is_numeric($purchases) ? (float) $purchases : 0.0) * 0.4 +
                 (is_numeric($recentViews) ? (float) $recentViews : 0.0) * 0.2 +
                 (is_numeric($recentPurchases) ? (float) $recentPurchases : 0.0) * 0.1;

        // Normalize to 0-100 scale
        return min(100, max(0, $score / 10));
    }

    /**
     * @param  array<int, array<string, mixed>>  $products
     * @return array<int, array<string, mixed>>
     */
    private function getRisingTrends(array $products): array
    {
        $risingTrends = [];

        foreach ($products as $product) {
            $currentViews = $product['current_week_views'] ?? 0;
            $previousViews = $product['previous_week_views'] ?? 0;

            if ($previousViews > 0) {
                $currentViewsFloat = is_numeric($currentViews) ? (float) $currentViews : 0.0;
                $previousViewsFloat = is_numeric($previousViews) ? (float) $previousViews : 0.0;
                $growthRate = (($currentViewsFloat - $previousViewsFloat) / $previousViewsFloat) * 100;
                if ($growthRate > 0) {
                    $product['growth_rate'] = $growthRate;
                    $risingTrends[] = $product;
                }
            }
        }

        // Sort by growth rate descending
        usort($risingTrends, function ($a, $b) {
            return $b['growth_rate'] <=> $a['growth_rate'];
        });

        return $risingTrends;
    }

    /**
     * @param  array<int, array<string, mixed>>  $products
     * @return array<int, array<string, mixed>>
     */
    private function getFallingTrends(array $products): array
    {
        $fallingTrends = [];

        foreach ($products as $product) {
            $currentViews = $product['current_week_views'] ?? 0;
            $previousViews = $product['previous_week_views'] ?? 0;

            if ($previousViews > 0) {
                $currentViewsFloat = is_numeric($currentViews) ? (float) $currentViews : 0.0;
                $previousViewsFloat = is_numeric($previousViews) ? (float) $previousViews : 0.0;
                $declineRate = (($previousViewsFloat - $currentViewsFloat) / $previousViewsFloat) * 100;
                if ($declineRate > 0) {
                    $product['decline_rate'] = $declineRate;
                    $fallingTrends[] = $product;
                }
            }
        }

        // Sort by decline rate descending
        usort($fallingTrends, function ($a, $b) {
            return $b['decline_rate'] <=> $a['decline_rate'];
        });

        return $fallingTrends;
    }

    /**
     * @param  array<int, array<string, mixed>>  $products
     * @return array<int, array<string, mixed>>
     */
    private function getSeasonalTrends(array $products, string $currentSeason): array
    {
        $seasonalTrends = [];

        foreach ($products as $product) {
            if (($product['season'] ?? '') === $currentSeason) {
                $seasonalTrends[] = $product;
            }
        }

        // Sort by current season views descending
        usort($seasonalTrends, function ($a, $b) {
            return ($b['current_season_views'] ?? 0) <=> ($a['current_season_views'] ?? 0);
        });

        return $seasonalTrends;
    }

    /**
     * @param  array<int, array<string, mixed>>  $products
     * @return array<int, array<string, mixed>>
     */
    private function getCategoryTrends(array $products): array
    {
        $categoryStats = [];

        foreach ($products as $product) {
            $category = $product['category'] ?? 'Unknown';
            if (is_string($category)) {
                if (! isset($categoryStats[$category])) {
                    $categoryStats[$category] = [
                        'category' => $category,
                        'total_views' => 0,
                        'product_count' => 0,
                    ];
                }
                $views = $product['views'] ?? 0;
                $categoryStats[$category]['total_views'] += is_numeric($views) ? (float) $views : 0.0;
                $categoryStats[$category]['product_count']++;
            }
        }

        // Sort by total views descending
        usort($categoryStats, function ($a, $b) {
            return $b['total_views'] <=> $a['total_views'];
        });

        return $categoryStats;
    }

    /**
     * @param  array<int, array<string, mixed>>  $products
     * @return array<int, array<string, mixed>>
     */
    private function getBrandTrends(array $products): array
    {
        $brandStats = [];

        foreach ($products as $product) {
            $brand = $product['brand'] ?? 'Unknown';
            if (is_string($brand)) {
                if (! isset($brandStats[$brand])) {
                    $brandStats[$brand] = [
                        'brand' => $brand,
                        'total_views' => 0,
                        'product_count' => 0,
                    ];
                }
                $views = $product['views'] ?? 0;
                $brandStats[$brand]['total_views'] += is_numeric($views) ? (float) $views : 0.0;
                $brandStats[$brand]['product_count']++;
            }
        }

        // Sort by total views descending
        usort($brandStats, function ($a, $b) {
            return $b['total_views'] <=> $a['total_views'];
        });

        return $brandStats;
    }

    /**
     * @param  array<int, array<string, mixed>>  $products
     * @return array<int, array<string, mixed>>
     */
    private function getPriceRangeTrends(array $products): array
    {
        $priceRanges = [
            'budget' => ['min' => 0, 'max' => 299],
            'mid-range' => ['min' => 300, 'max' => 999],
            'premium' => ['min' => 1000, 'max' => 1999],
            'luxury' => ['min' => 2000, 'max' => PHP_FLOAT_MAX],
        ];

        $rangeStats = [];

        foreach ($priceRanges as $range => $bounds) {
            $rangeStats[$range] = [
                'range' => $range,
                'total_views' => 0,
                'product_count' => 0,
            ];

            foreach ($products as $product) {
                $price = $product['price'] ?? 0;
                if (is_numeric($price) && $price >= $bounds['min'] && $price <= $bounds['max']) {
                    $views = $product['views'] ?? 0;
                    $rangeStats[$range]['total_views'] += is_numeric($views) ? (float) $views : 0.0;
                    $rangeStats[$range]['product_count']++;
                }
            }
        }

        // Sort by total views descending
        usort($rangeStats, function ($a, $b) {
            return $b['total_views'] <=> $a['total_views'];
        });

        return $rangeStats;
    }

    /**
     * @param  array<int, array<string, mixed>>  $products
     * @return array<int, array<string, mixed>>
     */
    private function getTimeBasedTrends(array $products): array
    {
        $timeBasedTrends = [];

        foreach ($products as $product) {
            $todayViews = $product['views_today'] ?? 0;
            $yesterdayViews = $product['views_yesterday'] ?? 0;

            if ($yesterdayViews > 0) {
                $todayViewsFloat = is_numeric($todayViews) ? (float) $todayViews : 0.0;
                $yesterdayViewsFloat = is_numeric($yesterdayViews) ? (float) $yesterdayViews : 0.0;
                $growthRate = (($todayViewsFloat - $yesterdayViewsFloat) / $yesterdayViewsFloat) * 100;
                if ($growthRate > 0) {
                    $product['growth_rate'] = $growthRate;
                    $timeBasedTrends[] = $product;
                }
            }
        }

        // Sort by growth rate descending
        usort($timeBasedTrends, function ($a, $b) {
            return $b['growth_rate'] <=> $a['growth_rate'];
        });

        return $timeBasedTrends;
    }

    /**
     * @param  array<int, array<string, mixed>>  $products
     * @return array<int, array<string, mixed>>
     */
    private function getGeographicTrends(array $products): array
    {
        $regionStats = [];

        foreach ($products as $product) {
            $region = $product['region'] ?? 'Unknown';
            if (is_string($region)) {
                if (! isset($regionStats[$region])) {
                    $regionStats[$region] = [
                        'region' => $region,
                        'total_views' => 0,
                        'product_count' => 0,
                    ];
                }
                $views = $product['views'] ?? 0;
                $regionStats[$region]['total_views'] += is_numeric($views) ? (float) $views : 0.0;
                $regionStats[$region]['product_count']++;
            }
        }

        // Sort by total views descending
        usort($regionStats, function ($a, $b) {
            return $b['total_views'] <=> $a['total_views'];
        });

        return $regionStats;
    }

    /**
     * @param  array<int, array<string, mixed>>  $products
     * @return array<int, array<string, mixed>>
     */
    private function getDemographicTrends(array $products): array
    {
        $demographicStats = [];

        foreach ($products as $product) {
            $ageGroup = $product['age_group'] ?? 'Unknown';
            if (is_string($ageGroup)) {
                if (! isset($demographicStats[$ageGroup])) {
                    $demographicStats[$ageGroup] = [
                        'age_group' => $ageGroup,
                        'total_views' => 0,
                        'product_count' => 0,
                    ];
                }
                $views = $product['views'] ?? 0;
                $demographicStats[$ageGroup]['total_views'] += is_numeric($views) ? (float) $views : 0.0;
                $demographicStats[$ageGroup]['product_count']++;
            }
        }

        // Sort by total views descending
        usort($demographicStats, function ($a, $b) {
            return $b['total_views'] <=> $a['total_views'];
        });

        return $demographicStats;
    }
}

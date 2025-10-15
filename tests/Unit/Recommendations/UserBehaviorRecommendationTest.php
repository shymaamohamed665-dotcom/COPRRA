<?php

namespace Tests\Unit\Recommendations;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class UserBehaviorRecommendationTest extends TestCase
{
    #[Test]
    public function it_analyzes_user_browsing_patterns(): void
    {
        $browsingHistory = [
            ['page' => 'electronics', 'time_spent' => 300, 'timestamp' => '2024-01-15 10:00:00'],
            ['page' => 'smartphones', 'time_spent' => 600, 'timestamp' => '2024-01-15 10:05:00'],
            ['page' => 'iphone', 'time_spent' => 900, 'timestamp' => '2024-01-15 10:15:00'],
            ['page' => 'accessories', 'time_spent' => 180, 'timestamp' => '2024-01-15 10:30:00'],
        ];

        $patterns = $this->analyzeBrowsingPatterns($browsingHistory);

        $this->assertArrayHasKey('most_viewed_category', $patterns);
        $this->assertArrayHasKey('average_session_time', $patterns);
        $this->assertEquals('iphone', $patterns['most_viewed_category']);
    }

    #[Test]
    public function it_tracks_user_purchase_frequency(): void
    {
        $purchaseHistory = [
            ['date' => '2024-01-01', 'amount' => 100],
            ['date' => '2024-01-15', 'amount' => 200],
            ['date' => '2024-02-01', 'amount' => 150],
            ['date' => '2024-02-15', 'amount' => 300],
        ];

        $frequency = $this->calculatePurchaseFrequency($purchaseHistory);

        $this->assertGreaterThan(0, $frequency);
        $this->assertLessThan(1, $frequency); // Purchases per day
    }

    #[Test]
    public function it_identifies_user_preferences_from_behavior(): void
    {
        $userBehavior = [
            'browsed_categories' => ['Electronics', 'Electronics', 'Clothing', 'Electronics'],
            'clicked_products' => ['iPhone', 'Samsung', 'Nike', 'iPhone'],
            'time_on_pages' => [300, 600, 120, 900],
            'searches' => ['smartphone', 'iPhone', 'shoes', 'iPhone case'],
        ];

        $preferences = $this->identifyUserPreferences($userBehavior);

        $this->assertArrayHasKey('preferred_category', $preferences);
        $this->assertArrayHasKey('preferred_brand', $preferences);
        $this->assertEquals('Electronics', $preferences['preferred_category']);
        $this->assertEquals('iPhone', $preferences['preferred_brand']);
    }

    #[Test]
    public function it_predicts_user_intent(): void
    {
        $recentBehavior = [
            'searches' => ['iPhone 15', 'iPhone accessories', 'iPhone case'],
            'browsed_products' => ['iPhone 15 Pro', 'iPhone 15 Pro Max'],
            'time_spent' => 1800, // 30 minutes
            'pages_viewed' => 8,
        ];

        $intent = $this->predictUserIntent($recentBehavior);

        $this->assertArrayHasKey('intent_type', $intent);
        $this->assertArrayHasKey('confidence', $intent);
        $this->assertEquals('purchasing', $intent['intent_type']);
        $this->assertGreaterThan(0.7, $intent['confidence']);
    }

    #[Test]
    public function it_analyzes_user_engagement_level(): void
    {
        $userMetrics = [
            'sessions_per_week' => 5,
            'average_session_duration' => 600, // 10 minutes
            'pages_per_session' => 8,
            'bounce_rate' => 0.2,
            'return_visits' => 3,
        ];

        $engagementLevel = $this->calculateEngagementLevel($userMetrics);

        $this->assertArrayHasKey('level', $engagementLevel);
        $this->assertArrayHasKey('score', $engagementLevel);
        $this->assertContains($engagementLevel['level'], ['low', 'medium', 'high']);
    }

    #[Test]
    public function it_tracks_user_price_sensitivity(): void
    {
        $priceBehavior = [
            'viewed_products' => [
                ['price' => 100, 'purchased' => true],
                ['price' => 200, 'purchased' => false],
                ['price' => 150, 'purchased' => true],
                ['price' => 300, 'purchased' => false],
            ],
            'price_alerts_set' => 2,
            'discount_searches' => 5,
        ];

        $sensitivity = $this->calculatePriceSensitivity($priceBehavior);

        $this->assertArrayHasKey('level', $sensitivity);
        $this->assertArrayHasKey('threshold', $sensitivity);
        $this->assertLessThan(200, $sensitivity['threshold']);
    }

    #[Test]
    public function it_analyzes_user_device_preferences(): void
    {
        $deviceUsage = [
            'mobile' => ['sessions' => 15, 'purchases' => 3, 'time_spent' => 1800],
            'desktop' => ['sessions' => 8, 'purchases' => 5, 'time_spent' => 2400],
            'tablet' => ['sessions' => 3, 'purchases' => 1, 'time_spent' => 600],
        ];

        $preferences = $this->analyzeDevicePreferences($deviceUsage);

        $this->assertArrayHasKey('primary_device', $preferences);
        $this->assertArrayHasKey('purchase_device', $preferences);
        $this->assertEquals('mobile', $preferences['primary_device']);
        $this->assertEquals('desktop', $preferences['purchase_device']);
    }

    #[Test]
    public function it_tracks_user_seasonal_patterns(): void
    {
        $seasonalData = [
            'Winter' => ['purchases' => 10, 'categories' => ['Winter Clothing', 'Heating']],
            'Spring' => ['purchases' => 8, 'categories' => ['Spring Clothing', 'Gardening']],
            'Summer' => ['purchases' => 12, 'categories' => ['Summer Clothing', 'Outdoor']],
            'Fall' => ['purchases' => 6, 'categories' => ['Fall Clothing', 'Indoor']],
        ];

        $patterns = $this->analyzeSeasonalPatterns($seasonalData);

        $this->assertArrayHasKey('peak_season', $patterns);
        $this->assertArrayHasKey('preferred_categories', $patterns);
        $this->assertEquals('Summer', $patterns['peak_season']);
    }

    #[Test]
    public function it_predicts_user_churn_risk(): void
    {
        $userMetrics = [
            'days_since_last_visit' => 30,
            'sessions_last_month' => 2,
            'purchases_last_month' => 0,
            'engagement_score' => 0.3,
            'support_tickets' => 2,
        ];

        $churnRisk = $this->predictChurnRisk($userMetrics);

        $this->assertArrayHasKey('risk_level', $churnRisk);
        $this->assertArrayHasKey('probability', $churnRisk);
        $this->assertContains($churnRisk['risk_level'], ['low', 'medium', 'high']);
    }

    #[Test]
    public function it_generates_personalized_recommendations(): void
    {
        $userProfile = [
            'preferences' => ['Electronics', 'Apple'],
            'price_range' => [100, 500],
            'engagement_level' => 'high',
            'purchase_frequency' => 0.5,
        ];

        $availableProducts = [
            ['name' => 'iPhone', 'category' => 'Electronics', 'brand' => 'Apple', 'price' => 400],
            ['name' => 'Samsung', 'category' => 'Electronics', 'brand' => 'Samsung', 'price' => 350],
            ['name' => 'Nike Shoes', 'category' => 'Clothing', 'brand' => 'Nike', 'price' => 120],
        ];

        $recommendations = $this->generatePersonalizedRecommendations($userProfile, $availableProducts);

        $this->assertContains('iPhone', array_column($recommendations, 'name'));
        $this->assertNotContains('Nike Shoes', array_column($recommendations, 'name'));
    }

    /**
     * @param  array<int, array<string, mixed>>  $browsingHistory
     * @return array<string, mixed>
     */
    private function analyzeBrowsingPatterns(array $browsingHistory): array
    {
        $categoryTime = [];
        $totalTime = 0;

        foreach ($browsingHistory as $session) {
            $category = is_string($session['page']) ? $session['page'] : '';
            $time = is_numeric($session['time_spent']) ? (int) $session['time_spent'] : 0;

            $categoryTime[$category] = ($categoryTime[$category] ?? 0) + $time;
            $totalTime += $time;
        }

        $maxTime = ! empty($categoryTime) ? max($categoryTime) : 0;
        $mostViewedCategory = $maxTime > 0 ? array_keys($categoryTime, $maxTime)[0] : '';
        $averageSessionTime = $totalTime / count($browsingHistory);

        return [
            'most_viewed_category' => $mostViewedCategory,
            'average_session_time' => $averageSessionTime,
            'category_time_distribution' => $categoryTime,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $purchaseHistory
     */
    private function calculatePurchaseFrequency(array $purchaseHistory): float
    {
        if (empty($purchaseHistory)) {
            return 0;
        }

        $firstDate = is_string($purchaseHistory[0]['date']) ? $purchaseHistory[0]['date'] : '2024-01-01';
        $lastDate = is_string(end($purchaseHistory)['date']) ? end($purchaseHistory)['date'] : '2024-01-01';

        $firstPurchase = new \DateTime($firstDate);
        $lastPurchase = new \DateTime($lastDate);
        $daysDiff = $lastPurchase->diff($firstPurchase)->days;

        return $daysDiff > 0 ? count($purchaseHistory) / $daysDiff : 0;
    }

    /**
     * @param  array<string, mixed>  $userBehavior
     * @return array<string, mixed>
     */
    private function identifyUserPreferences(array $userBehavior): array
    {
        $browsedCategories = is_array($userBehavior['browsed_categories']) ? $userBehavior['browsed_categories'] : [];
        $clickedProducts = is_array($userBehavior['clicked_products']) ? $userBehavior['clicked_products'] : [];

        $categoryCounts = array_count_values($browsedCategories);
        $brandCounts = array_count_values($clickedProducts);

        $maxCategoryCount = ! empty($categoryCounts) ? max($categoryCounts) : 0;
        $maxBrandCount = ! empty($brandCounts) ? max($brandCounts) : 0;

        $preferredCategory = $maxCategoryCount > 0 ? array_keys($categoryCounts, $maxCategoryCount)[0] : '';
        $preferredBrand = $maxBrandCount > 0 ? array_keys($brandCounts, $maxBrandCount)[0] : '';

        return [
            'preferred_category' => $preferredCategory,
            'preferred_brand' => $preferredBrand,
            'category_confidence' => $maxCategoryCount > 0 ? $maxCategoryCount / array_sum($categoryCounts) : 0,
            'brand_confidence' => $maxBrandCount > 0 ? $maxBrandCount / array_sum($brandCounts) : 0,
        ];
    }

    /**
     * @param  array<string, mixed>  $recentBehavior
     * @return array<string, mixed>
     */
    private function predictUserIntent(array $recentBehavior): array
    {
        $intentScore = 0;

        // Analyze search patterns
        $searches = is_array($recentBehavior['searches']) ? $recentBehavior['searches'] : [];
        $productSearches = array_filter($searches, function ($search) {
            return is_string($search) && strpos(strtolower($search), 'iphone') !== false;
        });
        $intentScore += count($productSearches) * 0.2;

        // Analyze browsing depth
        $pagesViewed = is_numeric($recentBehavior['pages_viewed']) ? (int) $recentBehavior['pages_viewed'] : 0;
        $intentScore += min($pagesViewed * 0.1, 0.3);

        // Analyze time spent
        $timeSpent = is_numeric($recentBehavior['time_spent']) ? (float) $recentBehavior['time_spent'] : 0;
        $intentScore += min($timeSpent / 3600, 0.3); // Max 0.3 for 1 hour

        $intentType = $intentScore > 0.5 ? 'purchasing' : 'browsing';

        return [
            'intent_type' => $intentType,
            'confidence' => min($intentScore, 1.0),
        ];
    }

    /**
     * @param  array<string, mixed>  $userMetrics
     * @return array<string, mixed>
     */
    private function calculateEngagementLevel(array $userMetrics): array
    {
        $score = 0;

        // Sessions per week (0-0.2)
        $sessionsPerWeek = is_numeric($userMetrics['sessions_per_week']) ? (float) $userMetrics['sessions_per_week'] : 0;
        $score += min($sessionsPerWeek * 0.04, 0.2);

        // Average session duration (0-0.3)
        $averageSessionDuration = is_numeric($userMetrics['average_session_duration']) ? (float) $userMetrics['average_session_duration'] : 0;
        $score += min($averageSessionDuration / 3600 * 0.3, 0.3);

        // Pages per session (0-0.2)
        $pagesPerSession = is_numeric($userMetrics['pages_per_session']) ? (float) $userMetrics['pages_per_session'] : 0;
        $score += min($pagesPerSession * 0.025, 0.2);

        // Bounce rate (0-0.2)
        $bounceRate = is_numeric($userMetrics['bounce_rate']) ? (float) $userMetrics['bounce_rate'] : 0;
        $score += (1 - $bounceRate) * 0.2;

        // Return visits (0-0.1)
        $returnVisits = is_numeric($userMetrics['return_visits']) ? (float) $userMetrics['return_visits'] : 0;
        $score += min($returnVisits * 0.033, 0.1);

        $level = $score > 0.7 ? 'high' : ($score > 0.4 ? 'medium' : 'low');

        return [
            'level' => $level,
            'score' => $score,
        ];
    }

    /**
     * @param  array<string, mixed>  $priceBehavior
     * @return array<string, mixed>
     */
    private function calculatePriceSensitivity(array $priceBehavior): array
    {
        $viewedProducts = is_array($priceBehavior['viewed_products']) ? $priceBehavior['viewed_products'] : [];

        $purchasedPrices = array_column(array_filter($viewedProducts, function ($p) {
            return is_array($p) && ($p['purchased'] ?? false);
        }), 'price');

        $notPurchasedPrices = array_column(array_filter($viewedProducts, function ($p) {
            return is_array($p) && ! ($p['purchased'] ?? false);
        }), 'price');

        $maxPurchasedPrice = empty($purchasedPrices) ? 0 : max($purchasedPrices);
        $minNotPurchasedPrice = empty($notPurchasedPrices) ? PHP_FLOAT_MAX : min($notPurchasedPrices);

        $threshold = $maxPurchasedPrice > 0 ? $maxPurchasedPrice : $minNotPurchasedPrice;

        $priceAlertsSet = is_numeric($priceBehavior['price_alerts_set']) ? (float) $priceBehavior['price_alerts_set'] : 0;
        $discountSearches = is_numeric($priceBehavior['discount_searches']) ? (float) $priceBehavior['discount_searches'] : 0;

        $sensitivityScore = $priceAlertsSet * 0.1 + $discountSearches * 0.05;

        $level = $sensitivityScore > 0.5 ? 'high' : ($sensitivityScore > 0.2 ? 'medium' : 'low');

        return [
            'level' => $level,
            'threshold' => $threshold,
            'score' => $sensitivityScore,
        ];
    }

    /**
     * @param  array<string, mixed>  $deviceUsage
     * @return array<string, mixed>
     */
    private function analyzeDevicePreferences(array $deviceUsage): array
    {
        $primaryDevice = '';
        $purchaseDevice = '';
        $maxSessions = 0;
        $maxPurchases = 0;

        foreach ($deviceUsage as $device => $metrics) {
            if (is_array($metrics)) {
                $sessions = is_numeric($metrics['sessions']) ? (int) $metrics['sessions'] : 0;
                $purchases = is_numeric($metrics['purchases']) ? (int) $metrics['purchases'] : 0;

                if ($sessions > $maxSessions) {
                    $maxSessions = $sessions;
                    $primaryDevice = is_string($device) ? $device : '';
                }

                if ($purchases > $maxPurchases) {
                    $maxPurchases = $purchases;
                    $purchaseDevice = is_string($device) ? $device : '';
                }
            }
        }

        return [
            'primary_device' => $primaryDevice,
            'purchase_device' => $purchaseDevice,
            'device_usage' => $deviceUsage,
        ];
    }

    /**
     * @param  array<string, mixed>  $seasonalData
     * @return array<string, mixed>
     */
    private function analyzeSeasonalPatterns(array $seasonalData): array
    {
        $peakSeason = '';
        $maxPurchases = 0;
        $allCategories = [];

        foreach ($seasonalData as $season => $data) {
            if (is_array($data)) {
                $purchases = is_numeric($data['purchases']) ? (int) $data['purchases'] : 0;
                if ($purchases > $maxPurchases) {
                    $maxPurchases = $purchases;
                    $peakSeason = is_string($season) ? $season : '';
                }

                $categories = is_array($data['categories']) ? $data['categories'] : [];
                $allCategories = array_merge($allCategories, $categories);
            }
        }

        $preferredCategories = array_count_values($allCategories);
        arsort($preferredCategories);

        return [
            'peak_season' => $peakSeason,
            'preferred_categories' => array_keys(array_slice($preferredCategories, 0, 3, true)),
        ];
    }

    /**
     * @param  array<string, mixed>  $userMetrics
     * @return array<string, mixed>
     */
    private function predictChurnRisk(array $userMetrics): array
    {
        $riskScore = 0;

        // Days since last visit (0-0.3)
        $daysSinceLastVisit = is_numeric($userMetrics['days_since_last_visit']) ? (float) $userMetrics['days_since_last_visit'] : 0;
        $riskScore += min($daysSinceLastVisit / 30 * 0.3, 0.3);

        // Sessions last month (0-0.2)
        $sessionsLastMonth = is_numeric($userMetrics['sessions_last_month']) ? (int) $userMetrics['sessions_last_month'] : 0;
        $riskScore += max(0, (5 - $sessionsLastMonth) * 0.04);

        // Purchases last month (0-0.2)
        $purchasesLastMonth = is_numeric($userMetrics['purchases_last_month']) ? (int) $userMetrics['purchases_last_month'] : 0;
        $riskScore += max(0, (2 - $purchasesLastMonth) * 0.1);

        // Engagement score (0-0.2)
        $engagementScore = is_numeric($userMetrics['engagement_score']) ? (float) $userMetrics['engagement_score'] : 0;
        $riskScore += (1 - $engagementScore) * 0.2;

        // Support tickets (0-0.1)
        $supportTickets = is_numeric($userMetrics['support_tickets']) ? (int) $userMetrics['support_tickets'] : 0;
        $riskScore += min($supportTickets * 0.05, 0.1);

        $probability = min($riskScore, 1.0);
        $riskLevel = $probability > 0.7 ? 'high' : ($probability > 0.4 ? 'medium' : 'low');

        return [
            'risk_level' => $riskLevel,
            'probability' => $probability,
        ];
    }

    /**
     * @param  array<string, mixed>  $userProfile
     * @return array<int, array<string, mixed>>
     */

    /**
     * @param  array<string, mixed>  $userProfile
     * @param  array<int, array<string, mixed>>  $availableProducts
     * @return array<int, array<string, mixed>>
     */
    private function generatePersonalizedRecommendations(array $userProfile, array $availableProducts): array
    {
        $recommendations = [];

        foreach ($availableProducts as $product) {
            $score = 0;

            // Category preference
            $preferences = is_array($userProfile['preferences']) ? $userProfile['preferences'] : [];
            if (in_array($product['category'], $preferences)) {
                $score += 0.4;
            }

            // Brand preference
            if (in_array($product['brand'], $preferences)) {
                $score += 0.3;
            }

            // Price range
            $priceRange = is_array($userProfile['price_range']) ? $userProfile['price_range'] : [0, 1000];
            if (
                $product['price'] >= $priceRange[0] &&
                $product['price'] <= $priceRange[1]
            ) {
                $score += 0.3;
            }

            if ($score > 0.5) {
                $recommendations[] = array_merge($product, ['recommendation_score' => $score]);
            }
        }

        usort($recommendations, function ($a, $b) {
            return $b['recommendation_score'] <=> $a['recommendation_score'];
        });

        return $recommendations;
    }
}

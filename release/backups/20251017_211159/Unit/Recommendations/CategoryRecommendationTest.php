<?php

declare(strict_types=1);

namespace Tests\Unit\Recommendations;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CategoryRecommendationTest extends TestCase
{
    #[Test]
    public function it_recommends_categories_based_on_user_history(): void
    {
        $userHistory = [
            ['category' => 'Electronics', 'purchases' => 5],
            ['category' => 'Clothing', 'purchases' => 3],
            ['category' => 'Books', 'purchases' => 2],
        ];

        $recommendations = $this->getCategoryRecommendations($userHistory);

        $this->assertContains('Electronics', $recommendations);
        $this->assertContains('Clothing', $recommendations);
        $this->assertCount(2, $recommendations);
    }

    #[Test]
    public function it_recommends_popular_categories(): void
    {
        $categoryStats = [
            'Electronics' => 1000,
            'Clothing' => 800,
            'Books' => 600,
            'Home' => 400,
            'Sports' => 200,
        ];

        $recommendations = $this->getPopularCategoryRecommendations($categoryStats, 3);

        $this->assertContains('Electronics', $recommendations);
        $this->assertContains('Clothing', $recommendations);
        $this->assertContains('Books', $recommendations);
        $this->assertCount(3, $recommendations);
    }

    #[Test]
    public function it_recommends_related_categories(): void
    {
        $currentCategory = 'Electronics';
        $categoryRelations = [
            'Electronics' => ['Accessories', 'Gadgets', 'Computers'],
            'Clothing' => ['Shoes', 'Accessories', 'Jewelry'],
            'Books' => ['Educational', 'Fiction', 'Non-fiction'],
        ];

        $recommendations = $this->getRelatedCategoryRecommendations($currentCategory, $categoryRelations);

        $this->assertContains('Accessories', $recommendations);
        $this->assertContains('Gadgets', $recommendations);
        $this->assertContains('Computers', $recommendations);
    }

    #[Test]
    public function it_handles_seasonal_category_recommendations(): void
    {
        $currentSeason = 'Winter';
        $seasonalCategories = [
            'Winter' => ['Winter Clothing', 'Heating', 'Hot Beverages'],
            'Summer' => ['Summer Clothing', 'Cooling', 'Cold Beverages'],
            'Spring' => ['Spring Clothing', 'Gardening', 'Outdoor'],
            'Fall' => ['Fall Clothing', 'Harvest', 'Indoor'],
        ];

        $recommendations = $this->getSeasonalCategoryRecommendations($currentSeason, $seasonalCategories);

        $this->assertContains('Winter Clothing', $recommendations);
        $this->assertContains('Heating', $recommendations);
        $this->assertContains('Hot Beverages', $recommendations);
    }

    #[Test]
    public function it_recommends_categories_based_on_demographics(): void
    {
        $userProfile = [
            'age_group' => '25-35',
            'gender' => 'Female',
            'location' => 'Urban',
        ];

        $demographicCategories = [
            '25-35' => ['Electronics', 'Fashion', 'Fitness'],
            'Female' => ['Beauty', 'Fashion', 'Home Decor'],
            'Urban' => ['Tech', 'Fashion', 'Entertainment'],
        ];

        $recommendations = $this->getDemographicCategoryRecommendations($userProfile, $demographicCategories);

        $this->assertNotEmpty($recommendations);
        $this->assertContains('Fashion', $recommendations);
    }

    #[Test]
    public function it_filters_categories_by_availability(): void
    {
        $allCategories = ['Electronics', 'Clothing', 'Books', 'Sports'];
        $availableCategories = ['Electronics', 'Books', 'Sports'];

        $filteredRecommendations = $this->filterCategoriesByAvailability($allCategories, $availableCategories);

        $this->assertNotContains('Clothing', $filteredRecommendations);
        $this->assertContains('Electronics', $filteredRecommendations);
        $this->assertContains('Books', $filteredRecommendations);
    }

    #[Test]
    public function it_ranks_categories_by_relevance_score(): void
    {
        $categories = [
            ['name' => 'Electronics', 'score' => 0.9],
            ['name' => 'Clothing', 'score' => 0.7],
            ['name' => 'Books', 'score' => 0.8],
            ['name' => 'Sports', 'score' => 0.6],
        ];

        $rankedCategories = $this->rankCategoriesByScore($categories);

        $this->assertIsArray($rankedCategories[0]);
        $this->assertEquals('Electronics', $rankedCategories[0]['name']);
        $this->assertIsArray($rankedCategories[1]);
        $this->assertEquals('Books', $rankedCategories[1]['name']);
        $this->assertIsArray($rankedCategories[2]);
        $this->assertEquals('Clothing', $rankedCategories[2]['name']);
        $this->assertIsArray($rankedCategories[3]);
        $this->assertEquals('Sports', $rankedCategories[3]['name']);
    }

    #[Test]
    public function it_handles_empty_category_data(): void
    {
        $emptyHistory = [];
        $recommendations = $this->getCategoryRecommendations($emptyHistory);

        $this->assertEmpty($recommendations);
    }

    #[Test]
    public function it_calculates_category_affinity_score(): void
    {
        $userPurchases = [
            'Electronics' => 10,
            'Clothing' => 5,
            'Books' => 2,
        ];

        $category = 'Electronics';
        $affinityScore = $this->calculateCategoryAffinityScore($userPurchases, $category);

        $this->assertGreaterThan(0.5, $affinityScore);
    }

    #[Test]
    public function it_recommends_trending_categories(): void
    {
        $categoryTrends = [
            'Electronics' => ['trend' => 'up', 'growth' => 0.15],
            'Clothing' => ['trend' => 'stable', 'growth' => 0.02],
            'Books' => ['trend' => 'down', 'growth' => -0.05],
            'Fitness' => ['trend' => 'up', 'growth' => 0.25],
        ];

        $trendingCategories = $this->getTrendingCategoryRecommendations($categoryTrends);

        $this->assertContains('Electronics', $trendingCategories);
        $this->assertContains('Fitness', $trendingCategories);
        $this->assertNotContains('Books', $trendingCategories);
    }

    /**
     * @param  array<int, array<string, mixed>>  $userHistory
     * @return list<mixed>
     */
    private function getCategoryRecommendations(array $userHistory): array
    {
        if (empty($userHistory)) {
            return [];
        }

        // Sort by purchase count and return top 2 categories
        usort($userHistory, function ($a, $b) {
            return $b['purchases'] <=> $a['purchases'];
        });

        return array_slice(array_column($userHistory, 'category'), 0, 2);
    }

    /**
     * @param  array<string, mixed>  $categoryStats
     * @return array<int, array<string, mixed>>
     */

    /**
     * @param  array<string, mixed>  $categoryStats
     * @return list<string>
     */
    private function getPopularCategoryRecommendations(array $categoryStats, int $limit): array
    {
        arsort($categoryStats);

        return array_slice(array_keys($categoryStats), 0, $limit);
    }

    /**
     * @param  array<string, list<string>>  $categoryRelations
     * @return list<string>
     */
    private function getRelatedCategoryRecommendations(string $currentCategory, array $categoryRelations): array
    {
        return $categoryRelations[$currentCategory] ?? [];
    }

    /**
     * @param  array<string, list<string>>  $seasonalCategories
     * @return list<string>
     */
    private function getSeasonalCategoryRecommendations(string $season, array $seasonalCategories): array
    {
        return $seasonalCategories[$season] ?? [];
    }

    /**
     * @param  array<string, mixed>  $userProfile
     * @return array<int, array<string, mixed>>
     */

    /**
     * @param  array<string, mixed>  $userProfile
     * @param  array<string, list<string>>  $demographicCategories
     * @return array<int, string>
     */
    private function getDemographicCategoryRecommendations(array $userProfile, array $demographicCategories): array
    {
        $recommendations = [];

        foreach ($userProfile as $demographic => $value) {
            if (is_string($value) && isset($demographicCategories[$value])) {
                $categories = $demographicCategories[$value];
                if (is_array($categories)) {
                    $recommendations = array_merge($recommendations, $categories);
                }
            }
        }

        return array_unique($recommendations);
    }

    /**
     * @param  array<int, array<string, mixed>>  $allCategories
     * @return array<int, array<string, mixed>>
     */

    /**
     * @param  list<string>  $allCategories
     * @param  list<string>  $availableCategories
     * @return array<int, string>
     */
    private function filterCategoriesByAvailability(array $allCategories, array $availableCategories): array
    {
        return array_intersect($allCategories, $availableCategories);
    }

    /**
     * @param  list<array<string, mixed>>  $categories
     * @return list<array<string, mixed>>
     */
    private function rankCategoriesByScore(array $categories): array
    {
        usort($categories, function ($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        return $categories;
    }

    /**
     * @param  array<string, int>  $userPurchases
     */
    private function calculateCategoryAffinityScore(array $userPurchases, string $category): float
    {
        $totalPurchases = array_sum($userPurchases);
        $categoryPurchases = $userPurchases[$category] ?? 0;

        return $totalPurchases > 0 ? $categoryPurchases / $totalPurchases : 0;
    }

    /**
     * @param  array<string, array<string, mixed>>  $categoryTrends
     * @return list<string>
     */
    private function getTrendingCategoryRecommendations(array $categoryTrends): array
    {
        $trending = [];

        foreach ($categoryTrends as $category => $data) {
            if ($data['trend'] === 'up' && $data['growth'] > 0.1) {
                $trending[] = $category;
            }
        }

        return $trending;
    }
}

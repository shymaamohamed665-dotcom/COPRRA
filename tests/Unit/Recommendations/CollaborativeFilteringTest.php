<?php

namespace Tests\Unit\Recommendations;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CollaborativeFilteringTest extends TestCase
{
    #[Test]
    public function it_finds_similar_users(): void
    {
        $userRatings = [
            'user1' => ['item1' => 5, 'item2' => 3, 'item3' => 4],
            'user2' => ['item1' => 4, 'item2' => 2, 'item3' => 5],
            'user3' => ['item1' => 1, 'item2' => 5, 'item3' => 2],
            'user4' => ['item1' => 5, 'item2' => 3, 'item3' => 4],
        ];

        $targetUser = 'user1';
        $similarUsers = $this->findSimilarUsers($userRatings, $targetUser, 2);

        $this->assertContains('user4', $similarUsers);
        $this->assertContains('user2', $similarUsers);
        $this->assertCount(2, $similarUsers);
    }

    #[Test]
    public function it_calculates_cosine_similarity(): void
    {
        $user1 = ['item1' => 5, 'item2' => 3, 'item3' => 4];
        $user2 = ['item1' => 4, 'item2' => 2, 'item3' => 5];

        $similarity = $this->calculateCosineSimilarity($user1, $user2);

        $this->assertGreaterThan(0.8, $similarity);
    }

    #[Test]
    public function it_predicts_ratings_for_items(): void
    {
        $userRatings = [
            'user1' => ['item1' => 5, 'item2' => 3, 'item3' => 4],
            'user2' => ['item1' => 4, 'item2' => 2, 'item3' => 5],
            'user3' => ['item1' => 1, 'item2' => 5, 'item3' => 2],
        ];

        $targetUser = 'user1';
        $itemToPredict = 'item4';
        $similarUsers = ['user2', 'user3'];

        $predictedRating = $this->predictRating($userRatings, $targetUser, $itemToPredict, $similarUsers);

        $this->assertGreaterThan(0, $predictedRating);
        $this->assertLessThanOrEqual(5, $predictedRating);
    }

    #[Test]
    public function it_handles_cold_start_problem(): void
    {
        $newUser = [];
        $popularItems = [
            'item1' => 100,
            'item2' => 80,
            'item3' => 60,
            'item4' => 40,
        ];

        $recommendations = $this->handleColdStart($newUser, $popularItems, 3);

        $this->assertContains('item1', $recommendations);
        $this->assertContains('item2', $recommendations);
        $this->assertContains('item3', $recommendations);
        $this->assertCount(3, $recommendations);
    }

    #[Test]
    public function it_calculates_pearson_correlation(): void
    {
        $user1 = ['item1' => 5, 'item2' => 3, 'item3' => 4, 'item4' => 2];
        $user2 = ['item1' => 4, 'item2' => 2, 'item3' => 5, 'item4' => 1];

        $correlation = $this->calculatePearsonCorrelation($user1, $user2);

        $this->assertGreaterThan(0.7, $correlation);
    }

    #[Test]
    public function it_filters_items_by_user_preferences(): void
    {
        $allItems = ['item1', 'item2', 'item3', 'item4', 'item5'];
        $userPreferences = ['item1', 'item3'];
        $userRatings = ['item1' => 5, 'item3' => 4];

        $filteredItems = $this->filterItemsByPreferences($allItems, $userPreferences, $userRatings);

        $this->assertNotContains('item1', $filteredItems);
        $this->assertNotContains('item3', $filteredItems);
        $this->assertContains('item2', $filteredItems);
        $this->assertContains('item4', $filteredItems);
        $this->assertContains('item5', $filteredItems);
    }

    #[Test]
    public function it_calculates_item_based_similarity(): void
    {
        $itemRatings = [
            'item1' => ['user1' => 5, 'user2' => 4, 'user3' => 3],
            'item2' => ['user1' => 4, 'user2' => 3, 'user3' => 2],
            'item3' => ['user1' => 1, 'user2' => 2, 'user3' => 5],
        ];

        $similarity = $this->calculateItemBasedSimilarity($itemRatings, 'item1', 'item2');

        $this->assertGreaterThan(0.8, $similarity);
    }

    #[Test]
    public function it_handles_sparse_data(): void
    {
        $sparseRatings = [
            'user1' => ['item1' => 5],
            'user2' => ['item2' => 4],
            'user3' => ['item3' => 3],
            'user4' => ['item1' => 4, 'item2' => 3],
        ];

        $targetUser = 'user1';
        $minCommonItems = 1;

        $similarUsers = $this->findSimilarUsersWithMinCommonItems($sparseRatings, $targetUser, $minCommonItems);

        $this->assertContains('user4', $similarUsers);
    }

    #[Test]
    public function it_calculates_confidence_scores(): void
    {
        $userRatings = [
            'user1' => ['item1' => 5, 'item2' => 3],
            'user2' => ['item1' => 4, 'item2' => 2, 'item3' => 4],
            'user3' => ['item1' => 5, 'item2' => 3, 'item3' => 5],
        ];

        $targetUser = 'user1';
        $itemToPredict = 'item3';
        $similarUsers = ['user2', 'user3'];

        $confidence = $this->calculatePredictionConfidence($userRatings, $targetUser, $itemToPredict, $similarUsers);

        $this->assertGreaterThan(0, $confidence);
        $this->assertLessThanOrEqual(1, $confidence);
    }

    #[Test]
    public function it_handles_matrix_factorization(): void
    {
        $ratingsMatrix = [
            [5, 3, 0, 1],
            [4, 0, 0, 1],
            [1, 1, 0, 5],
            [1, 0, 0, 4],
            [0, 1, 5, 4],
        ];

        $factors = $this->performMatrixFactorization($ratingsMatrix, 2);

        $this->assertCount(2, $factors);
        $this->assertArrayHasKey('user_factors', $factors);
        $this->assertArrayHasKey('item_factors', $factors);
    }

    /**
     * @param  array<string, mixed>  $userRatings
     * @return array<int, array<string, mixed>>
     */

    /**
     * @param  array<string, array<string, mixed>>  $userRatings
     * @return list<string>
     */
    private function findSimilarUsers(array $userRatings, string $targetUser, int $limit): array
    {
        $targetRatings = $userRatings[$targetUser] ?? [];
        $similarities = [];

        foreach ($userRatings as $user => $ratings) {
            if ($user === $targetUser) {
                continue;
            }

            $similarity = $this->calculateCosineSimilarity($targetRatings, $ratings);
            $similarities[$user] = $similarity;
        }

        arsort($similarities);

        return array_slice(array_keys($similarities), 0, $limit);
    }

    /**
     * @param  array<string, mixed>  $user1
     * @param  array<string, mixed>  $user2
     */
    private function calculateCosineSimilarity(array $user1, array $user2): float
    {
        $commonItems = array_intersect_key($user1, $user2);

        if (empty($commonItems)) {
            return 0;
        }

        $dotProduct = 0;
        $norm1 = 0;
        $norm2 = 0;

        foreach ($commonItems as $item => $rating1) {
            $rating2 = $user2[$item];
            $rating1Float = is_numeric($rating1) ? (float) $rating1 : 0.0;
            $rating2Float = is_numeric($rating2) ? (float) $rating2 : 0.0;
            $dotProduct += $rating1Float * $rating2Float;
            $norm1 += $rating1Float * $rating1Float;
            $norm2 += $rating2Float * $rating2Float;
        }

        if ($norm1 == 0 || $norm2 == 0) {
            return 0;
        }

        return $dotProduct / (sqrt($norm1) * sqrt($norm2));
    }

    /**
     * @param  array<string, array<string, mixed>>  $userRatings
     * @param  list<string>  $similarUsers
     */
    private function predictRating(array $userRatings, string $targetUser, string $item, array $similarUsers): float
    {
        $targetRatings = $userRatings[$targetUser] ?? [];
        $targetAverage = empty($targetRatings) ? 0 : array_sum($targetRatings) / count($targetRatings);

        $weightedSum = 0;
        $totalWeight = 0;

        foreach ($similarUsers as $similarUser) {
            if (! isset($userRatings[$similarUser][$item])) {
                continue;
            }

            $similarity = $this->calculateCosineSimilarity($targetRatings, $userRatings[$similarUser]);
            $rating = $userRatings[$similarUser][$item] ?? 0;
            $ratingFloat = is_numeric($rating) ? (float) $rating : 0.0;
            $similarAverage = array_sum($userRatings[$similarUser]) / count($userRatings[$similarUser]);
            $similarAverageFloat = (float) $similarAverage;

            $weightedSum += $similarity * ($ratingFloat - $similarAverageFloat);
            $totalWeight += abs($similarity);
        }

        if ($totalWeight == 0) {
            return $targetAverage;
        }

        return $targetAverage + ($weightedSum / $totalWeight);
    }

    /**
     * @param  array<string, mixed>  $newUser
     * @return array<int, array<string, mixed>>
     */

    /**
     * @param  array<string, mixed>  $newUser
     * @param  array<string, int>  $popularItems
     * @return list<(int|string)>
     */
    private function handleColdStart(array $newUser, array $popularItems, int $limit): array
    {
        arsort($popularItems);

        return array_slice(array_keys($popularItems), 0, $limit);
    }

    /**
     * @param  array<string, mixed>  $user1
     * @param  array<string, mixed>  $user2
     */
    private function calculatePearsonCorrelation(array $user1, array $user2): float
    {
        $commonItems = array_intersect_key($user1, $user2);

        if (count($commonItems) < 2) {
            return 0;
        }

        $ratings1 = array_values($commonItems);
        $ratings2 = array_values(array_intersect_key($user2, $commonItems));

        $n = count($ratings1);
        $sum1 = array_sum($ratings1);
        $sum2 = array_sum($ratings2);
        $sum1Sq = array_sum(array_map(function ($x) {
            $xFloat = is_numeric($x) ? (float) $x : 0.0;

            return $xFloat * $xFloat;
        }, $ratings1));
        $sum2Sq = array_sum(array_map(function ($x) {
            $xFloat = is_numeric($x) ? (float) $x : 0.0;

            return $xFloat * $xFloat;
        }, $ratings2));
        $pSum = array_sum(array_map(function ($x, $y) {
            $xFloat = is_numeric($x) ? (float) $x : 0.0;
            $yFloat = is_numeric($y) ? (float) $y : 0.0;

            return $xFloat * $yFloat;
        }, $ratings1, $ratings2));

        $num = $pSum - ($sum1 * $sum2 / $n);
        $den = sqrt(($sum1Sq - $sum1 * $sum1 / $n) * ($sum2Sq - $sum2 * $sum2 / $n));

        if ($den == 0) {
            return 0;
        }

        return $num / $den;
    }

    /**
     * @param  array<int, array<string, mixed>>  $allItems
     * @return array<int, array<string, mixed>>
     */

    /**
     * @param  list<string>  $allItems
     * @param  list<string>  $userPreferences
     * @param  array<string, mixed>  $userRatings
     * @return array<int, string>
     */
    private function filterItemsByPreferences(array $allItems, array $userPreferences, array $userRatings): array
    {
        return array_diff($allItems, $userPreferences);
    }

    /**
     * @param  array<string, array<string, mixed>>  $itemRatings
     */
    private function calculateItemBasedSimilarity(array $itemRatings, string $item1, string $item2): float
    {
        if (! isset($itemRatings[$item1]) || ! isset($itemRatings[$item2])) {
            return 0;
        }

        $ratings1 = $itemRatings[$item1];
        $ratings2 = $itemRatings[$item2];

        if (is_array($ratings1) && is_array($ratings2)) {
            return $this->calculateCosineSimilarity($ratings1, $ratings2);
        }

        return 0.0;
    }

    /**
     * @param  array<string, mixed>  $userRatings
     * @return array<int, array<string, mixed>>
     */

    /**
     * @param  array<string, array<string, mixed>>  $userRatings
     * @return list<string>
     */
    private function findSimilarUsersWithMinCommonItems(array $userRatings, string $targetUser, int $minCommonItems): array
    {
        $targetRatings = $userRatings[$targetUser] ?? [];
        $similarUsers = [];

        foreach ($userRatings as $user => $ratings) {
            if ($user === $targetUser) {
                continue;
            }

            $commonItems = array_intersect_key($targetRatings, $ratings);

            if (count($commonItems) >= $minCommonItems) {
                $similarity = $this->calculateCosineSimilarity($targetRatings, $ratings);
                $similarUsers[$user] = $similarity;
            }
        }

        arsort($similarUsers);

        return array_keys($similarUsers);
    }

    /**
     * @param  array<string, array<string, mixed>>  $userRatings
     * @param  list<string>  $similarUsers
     */
    private function calculatePredictionConfidence(array $userRatings, string $targetUser, string $item, array $similarUsers): float
    {
        $targetRatings = $userRatings[$targetUser] ?? [];
        $commonItems = 0;
        $totalSimilarity = 0;

        foreach ($similarUsers as $similarUser) {
            if (isset($userRatings[$similarUser][$item])) {
                $commonItems++;
                $similarity = $this->calculateCosineSimilarity($targetRatings, $userRatings[$similarUser]);
                $totalSimilarity += abs($similarity);
            }
        }

        if ($commonItems == 0) {
            return 0;
        }

        return min(1.0, $commonItems / 5.0) * min(1.0, $totalSimilarity / $commonItems);
    }

    /**
     * @param  array<int, array<int, mixed>>  $ratingsMatrix
     * @return array<int, array<string, mixed>>
     */

    /**
     * @param  array<int, list<int>>  $ratingsMatrix
     * @return array<string, array<int, array<int, float|int>>>
     */
    private function performMatrixFactorization(array $ratingsMatrix, int $numFactors): array
    {
        $numUsers = count($ratingsMatrix);
        $firstRow = reset($ratingsMatrix);
        $numItems = is_array($firstRow) ? count($firstRow) : 0;

        // Initialize random factors
        $userFactors = [];
        $itemFactors = [];

        for ($i = 0; $i < $numUsers; $i++) {
            for ($f = 0; $f < $numFactors; $f++) {
                $userFactors[$i][$f] = rand(0, 100) / 100;
            }
        }

        for ($j = 0; $j < $numItems; $j++) {
            for ($f = 0; $f < $numFactors; $f++) {
                $itemFactors[$j][$f] = rand(0, 100) / 100;
            }
        }

        return [
            'user_factors' => $userFactors,
            'item_factors' => $itemFactors,
        ];
    }
}

<?php

namespace Tests\Unit\Recommendations;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class HybridRecommendationTest extends TestCase
{
    #[Test]
    public function it_combines_collaborative_and_content_based_filtering(): void
    {
        $collaborativeScore = 0.8;
        $contentBasedScore = 0.7;
        $weights = ['collaborative' => 0.6, 'content_based' => 0.4];

        $hybridScore = $this->calculateHybridScore($collaborativeScore, $contentBasedScore, $weights);

        $expectedScore = (0.8 * 0.6) + (0.7 * 0.4);
        $this->assertEquals($expectedScore, $hybridScore);
    }

    #[Test]
    public function it_handles_weighted_ensemble_method(): void
    {
        $recommendations = [
            ['item_id' => 'item1', 'collaborative_score' => 0.9, 'content_score' => 0.6],
            ['item_id' => 'item2', 'collaborative_score' => 0.7, 'content_score' => 0.8],
            ['item_id' => 'item3', 'collaborative_score' => 0.5, 'content_score' => 0.9],
        ];

        $weights = ['collaborative' => 0.5, 'content_based' => 0.5];
        $hybridRecommendations = $this->createWeightedEnsemble($recommendations, $weights);

        $this->assertCount(3, $hybridRecommendations);
        $this->assertArrayHasKey('hybrid_score', $hybridRecommendations[0]);
    }

    #[Test]
    public function it_handles_switching_hybrid_approach(): void
    {
        $userProfile = [
            'interaction_count' => 5,
            'preferences_available' => true,
            'cold_start' => false,
        ];

        $method = $this->selectRecommendationMethod($userProfile);

        $this->assertContains($method, ['collaborative', 'content_based', 'hybrid']);
    }

    #[Test]
    public function it_handles_mixed_hybrid_approach(): void
    {
        $collaborativeRecommendations = [
            ['item_id' => 'item1', 'score' => 0.9],
            ['item_id' => 'item2', 'score' => 0.8],
        ];

        $contentBasedRecommendations = [
            ['item_id' => 'item2', 'score' => 0.7],
            ['item_id' => 'item3', 'score' => 0.6],
        ];

        $mixedRecommendations = $this->createMixedHybrid($collaborativeRecommendations, $contentBasedRecommendations, 3);

        $this->assertCount(3, $mixedRecommendations);
        $this->assertContains('item1', array_column($mixedRecommendations, 'item_id'));
        $this->assertContains('item2', array_column($mixedRecommendations, 'item_id'));
        $this->assertContains('item3', array_column($mixedRecommendations, 'item_id'));
    }

    #[Test]
    public function it_handles_cascade_hybrid_approach(): void
    {
        $primaryRecommendations = [
            ['item_id' => 'item1', 'score' => 0.9],
            ['item_id' => 'item2', 'score' => 0.8],
            ['item_id' => 'item3', 'score' => 0.7],
        ];

        $secondaryRecommendations = [
            ['item_id' => 'item2', 'score' => 0.6],
            ['item_id' => 'item4', 'score' => 0.5],
        ];

        $cascadeRecommendations = $this->createCascadeHybrid($primaryRecommendations, $secondaryRecommendations, 3);

        $this->assertCount(3, $cascadeRecommendations);
        $this->assertEquals('item1', $cascadeRecommendations[0]['item_id']);
    }

    #[Test]
    public function it_handles_feature_combination_hybrid(): void
    {
        $collaborativeFeatures = ['user_similarity' => 0.8, 'item_popularity' => 0.6];
        $contentFeatures = ['category_match' => 0.9, 'brand_match' => 0.7];
        $demographicFeatures = ['age_group' => 0.8, 'location' => 0.5];

        $combinedScore = $this->combineFeatureVectors($collaborativeFeatures, $contentFeatures, $demographicFeatures);

        $this->assertGreaterThan(0, $combinedScore);
        $this->assertLessThanOrEqual(1, $combinedScore);
    }

    #[Test]
    public function it_handles_meta_learning_hybrid(): void
    {
        $userHistory = [
            ['method' => 'collaborative', 'accuracy' => 0.8, 'coverage' => 0.6],
            ['method' => 'content_based', 'accuracy' => 0.7, 'coverage' => 0.9],
            ['method' => 'hybrid', 'accuracy' => 0.85, 'coverage' => 0.8],
        ];

        $bestMethod = $this->selectBestMethodByMetaLearning($userHistory);

        $this->assertContains($bestMethod, ['collaborative', 'content_based', 'hybrid']);
    }

    #[Test]
    public function it_handles_dynamic_weight_adjustment(): void
    {
        $userProfile = [
            'interaction_count' => 100,
            'preference_stability' => 0.8,
            'diversity_preference' => 0.6,
        ];

        $weights = $this->adjustWeightsDynamically($userProfile);

        $this->assertArrayHasKey('collaborative', $weights);
        $this->assertArrayHasKey('content_based', $weights);
        $this->assertEquals(1.0, array_sum($weights));
    }

    #[Test]
    public function it_handles_confidence_weighted_hybrid(): void
    {
        $recommendations = [
            ['item_id' => 'item1', 'collaborative_score' => 0.9, 'collaborative_confidence' => 0.8],
            ['item_id' => 'item2', 'content_score' => 0.7, 'content_confidence' => 0.9],
        ];

        $confidenceWeightedRecommendations = $this->createConfidenceWeightedHybrid($recommendations);

        $this->assertCount(2, $confidenceWeightedRecommendations);
        $this->assertArrayHasKey('final_score', $confidenceWeightedRecommendations[0]);
    }

    #[Test]
    public function it_handles_temporal_hybrid_approach(): void
    {
        $recentRecommendations = [
            ['item_id' => 'item1', 'score' => 0.9, 'timestamp' => '2024-01-15'],
            ['item_id' => 'item2', 'score' => 0.8, 'timestamp' => '2024-01-14'],
        ];

        $historicalRecommendations = [
            ['item_id' => 'item1', 'score' => 0.7, 'timestamp' => '2024-01-10'],
            ['item_id' => 'item3', 'score' => 0.6, 'timestamp' => '2024-01-05'],
        ];

        $temporalScore = $this->createTemporalHybrid($recentRecommendations, $historicalRecommendations, 0.7);

        $this->assertGreaterThan(0, $temporalScore);
    }

    #[Test]
    public function it_evaluates_hybrid_recommendation_quality(): void
    {
        $recommendations = [
            ['item_id' => 'item1', 'score' => 0.9, 'user_rating' => 5],
            ['item_id' => 'item2', 'score' => 0.8, 'user_rating' => 4],
            ['item_id' => 'item3', 'score' => 0.7, 'user_rating' => 3],
        ];

        $qualityMetrics = $this->evaluateHybridQuality($recommendations);

        $this->assertArrayHasKey('precision', $qualityMetrics);
        $this->assertArrayHasKey('recall', $qualityMetrics);
        $this->assertArrayHasKey('f1_score', $qualityMetrics);
        $this->assertGreaterThan(0, $qualityMetrics['precision']);
    }

    /**
     * @param  array<string, float>  $weights
     */
    private function calculateHybridScore(float $collaborativeScore, float $contentScore, array $weights): float
    {
        return ($collaborativeScore * $weights['collaborative']) +
            ($contentScore * $weights['content_based']);
    }

    /**
     * @param  array<int, array<string, mixed>>  $recommendations
     * @return array<int, array<string, mixed>>
     */

    /**
     * @param  array<int, array<string, mixed>>  $recommendations
     * @param  array<string, float>  $weights
     * @return array<int, array<string, mixed>>
     */
    private function createWeightedEnsemble(array $recommendations, array $weights): array
    {
        foreach ($recommendations as &$recommendation) {
            $collaborativeScore = $recommendation['collaborative_score'] ?? 0;
            $contentScore = $recommendation['content_score'] ?? 0;

            $collaborativeFloat = is_numeric($collaborativeScore) ? (float) $collaborativeScore : 0.0;
            $contentFloat = is_numeric($contentScore) ? (float) $contentScore : 0.0;

            $recommendation['hybrid_score'] =
                ($collaborativeFloat * $weights['collaborative']) +
                ($contentFloat * $weights['content_based']);
        }

        usort($recommendations, function ($a, $b) {
            return $b['hybrid_score'] <=> $a['hybrid_score'];
        });

        return $recommendations;
    }

    /**
     * @param  array<string, mixed>  $userProfile
     */
    private function selectRecommendationMethod(array $userProfile): string
    {
        if ($userProfile['cold_start'] || $userProfile['interaction_count'] < 10) {
            return 'content_based';
        }

        if ($userProfile['interaction_count'] > 50 && $userProfile['preferences_available']) {
            return 'hybrid';
        }

        return 'collaborative';
    }

    /**
     * @param  array<int, array<string, mixed>>  $collaborativeRecs
     * @return array<int, array<string, mixed>>
     */

    /**
     * @param  array<int, array<string, mixed>>  $collaborativeRecs
     * @param  array<int, array<string, mixed>>  $contentRecs
     * @return array<int, array<string, mixed>>
     */
    private function createMixedHybrid(array $collaborativeRecs, array $contentRecs, int $limit): array
    {
        $allRecommendations = [];

        // Add collaborative recommendations
        foreach ($collaborativeRecs as $rec) {
            $allRecommendations[] = array_merge($rec, ['method' => 'collaborative']);
        }

        // Add content-based recommendations
        foreach ($contentRecs as $rec) {
            $allRecommendations[] = array_merge($rec, ['method' => 'content_based']);
        }

        // Remove duplicates and sort by score
        $uniqueRecommendations = [];
        foreach ($allRecommendations as $rec) {
            $itemId = $rec['item_id'] ?? '';
            if (is_string($itemId) && $itemId !== '') {
                if (
                    ! isset($uniqueRecommendations[$itemId]) ||
                    ($rec['score'] ?? 0) > ($uniqueRecommendations[$itemId]['score'] ?? 0)
                ) {
                    $uniqueRecommendations[$itemId] = $rec;
                }
            }
        }

        $recommendations = array_values($uniqueRecommendations);
        usort($recommendations, function ($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        return array_slice($recommendations, 0, $limit);
    }

    /**
     * @param  array<int, array<string, mixed>>  $primaryRecs
     * @return array<int, array<string, mixed>>
     */

    /**
     * @param  array<int, array<string, mixed>>  $primaryRecs
     * @param  array<int, array<string, mixed>>  $secondaryRecs
     * @return array<int, array<string, mixed>>
     */
    private function createCascadeHybrid(array $primaryRecs, array $secondaryRecs, int $limit): array
    {
        $recommendations = [];
        $usedItems = [];

        // Add primary recommendations
        foreach ($primaryRecs as $rec) {
            if (count($recommendations) >= $limit) {
                break;
            }
            $recommendations[] = $rec;
            $usedItems[] = $rec['item_id'];
        }

        // Add secondary recommendations for remaining slots
        foreach ($secondaryRecs as $rec) {
            if (count($recommendations) >= $limit) {
                break;
            }
            if (! in_array($rec['item_id'], $usedItems)) {
                $recommendations[] = $rec;
                $usedItems[] = $rec['item_id'];
            }
        }

        return $recommendations;
    }

    /**
     * @param  array<string, mixed>  $collaborativeFeatures
     * @param  array<string, mixed>  $contentFeatures
     * @param  array<string, mixed>  $demographicFeatures
     */
    private function combineFeatureVectors(array $collaborativeFeatures, array $contentFeatures, array $demographicFeatures): float
    {
        $weights = [
            'collaborative' => 0.4,
            'content' => 0.4,
            'demographic' => 0.2,
        ];

        $collaborativeScore = array_sum($collaborativeFeatures) / count($collaborativeFeatures);
        $contentScore = array_sum($contentFeatures) / count($contentFeatures);
        $demographicScore = array_sum($demographicFeatures) / count($demographicFeatures);

        return ($collaborativeScore * $weights['collaborative']) +
            ($contentScore * $weights['content']) +
            ($demographicScore * $weights['demographic']);
    }

    /**
     * @param  array<int, array<string, mixed>>  $userHistory
     */
    private function selectBestMethodByMetaLearning(array $userHistory): string
    {
        $methodScores = [];

        foreach ($userHistory as $record) {
            $method = $record['method'] ?? '';
            $accuracy = $record['accuracy'] ?? 0;
            $coverage = $record['coverage'] ?? 0;

            $accuracyFloat = is_numeric($accuracy) ? (float) $accuracy : 0.0;
            $coverageFloat = is_numeric($coverage) ? (float) $coverage : 0.0;
            $score = ($accuracyFloat * 0.7) + ($coverageFloat * 0.3);

            if (is_string($method) && $method !== '') {
                if (! isset($methodScores[$method])) {
                    $methodScores[$method] = [];
                }
                $methodScores[$method][] = $score;
            }
        }

        $averageScores = [];
        foreach ($methodScores as $method => $scores) {
            $averageScores[$method] = array_sum($scores) / count($scores);
        }

        if (empty($averageScores)) {
            return 'content_based';
        }

        $maxScore = max($averageScores);
        $bestMethods = array_keys($averageScores, $maxScore);

        return $bestMethods[0] ?? 'content_based';
    }

    /**
     * @param  array<string, mixed>  $userProfile
     * @return array<string, float>
     */
    private function adjustWeightsDynamically(array $userProfile): array
    {
        $baseWeights = ['collaborative' => 0.5, 'content_based' => 0.5];

        // Adjust based on interaction count
        if ($userProfile['interaction_count'] > 50) {
            $baseWeights['collaborative'] += 0.2;
            $baseWeights['content_based'] -= 0.2;
        }

        // Adjust based on preference stability
        if ($userProfile['preference_stability'] > 0.8) {
            $baseWeights['content_based'] += 0.1;
            $baseWeights['collaborative'] -= 0.1;
        }

        // Normalize weights
        $total = array_sum($baseWeights);
        foreach ($baseWeights as $key => $value) {
            $baseWeights[$key] = $value / $total;
        }

        return $baseWeights;
    }

    /**
     * @param  array<int, array<string, mixed>>  $recommendations
     * @return array<int, array<string, mixed>>
     */
    private function createConfidenceWeightedHybrid(array $recommendations): array
    {
        foreach ($recommendations as &$rec) {
            $collaborativeWeight = $rec['collaborative_confidence'] ?? 0;
            $contentWeight = $rec['content_confidence'] ?? 0;

            $collaborativeWeightFloat = is_numeric($collaborativeWeight) ? (float) $collaborativeWeight : 0.0;
            $contentWeightFloat = is_numeric($contentWeight) ? (float) $contentWeight : 0.0;
            $totalWeight = $collaborativeWeightFloat + $contentWeightFloat;

            if ($totalWeight > 0) {
                $collaborativeScore = $rec['collaborative_score'] ?? 0;
                $contentScore = $rec['content_score'] ?? 0;

                $collaborativeScoreFloat = is_numeric($collaborativeScore) ? (float) $collaborativeScore : 0.0;
                $contentScoreFloat = is_numeric($contentScore) ? (float) $contentScore : 0.0;

                $rec['final_score'] =
                    (($collaborativeScoreFloat * $collaborativeWeightFloat +
                        $contentScoreFloat * $contentWeightFloat) / $totalWeight);
            } else {
                $collaborativeScore = $rec['collaborative_score'] ?? 0;
                $contentScore = $rec['content_score'] ?? 0;

                $collaborativeScoreFloat = is_numeric($collaborativeScore) ? (float) $collaborativeScore : 0.0;
                $contentScoreFloat = is_numeric($contentScore) ? (float) $contentScore : 0.0;

                $rec['final_score'] = $collaborativeScoreFloat + $contentScoreFloat;
            }
        }

        usort($recommendations, function ($a, $b) {
            return $b['final_score'] <=> $a['final_score'];
        });

        return $recommendations;
    }

    /**
     * @param  array<int, array<string, mixed>>  $recentRecs
     * @param  array<int, array<string, mixed>>  $historicalRecs
     */
    private function createTemporalHybrid(array $recentRecs, array $historicalRecs, float $recentWeight): float
    {
        $recentScore = array_sum(array_column($recentRecs, 'score')) / count($recentRecs);
        $historicalScore = array_sum(array_column($historicalRecs, 'score')) / count($historicalRecs);

        return ($recentScore * $recentWeight) + ($historicalScore * (1 - $recentWeight));
    }

    /**
     * @param  array<int, array<string, mixed>>  $recommendations
     * @return array<string, float>
     */
    private function evaluateHybridQuality(array $recommendations): array
    {
        $totalRecommendations = count($recommendations);
        $relevantRecommendations = count(array_filter($recommendations, function ($rec) {
            return ($rec['user_rating'] ?? 0) >= 4;
        }));

        $precision = $totalRecommendations > 0 ? $relevantRecommendations / $totalRecommendations : 0;

        // For recall, we'd need to know total relevant items, which we don't have
        $recall = 0; // Would need ground truth data

        $f1Score = ($precision + $recall > 0) ? (2 * $precision * $recall) / ($precision + $recall) : 0;

        return [
            'precision' => $precision,
            'recall' => $recall,
            'f1_score' => $f1Score,
        ];
    }
}

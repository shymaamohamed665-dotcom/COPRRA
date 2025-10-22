<?php

declare(strict_types=1);

namespace Tests\Unit\Recommendations;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ContentBasedFilteringTest extends TestCase
{
    #[Test]
    public function it_analyzes_item_features(): void
    {
        $items = [
            [
                'id' => 'item1',
                'title' => 'iPhone 15 Pro Max',
                'category' => 'Electronics',
                'brand' => 'Apple',
                'price' => 1199,
                'features' => ['camera', 'battery', 'display'],
            ],
            [
                'id' => 'item2',
                'title' => 'Samsung Galaxy S24',
                'category' => 'Electronics',
                'brand' => 'Samsung',
                'price' => 999,
                'features' => ['camera', 'battery', 'display'],
            ],
        ];

        $features = $this->extractItemFeatures($items[0]);

        $this->assertArrayHasKey('category', $features);
        $this->assertArrayHasKey('brand', $features);
        $this->assertArrayHasKey('price_range', $features);
        $this->assertArrayHasKey('keywords', $features);
    }

    #[Test]
    public function it_calculates_item_similarity(): void
    {
        $item1 = [
            'category' => 'Electronics',
            'brand' => 'Apple',
            'price_range' => 'high',
            'keywords' => ['smartphone', 'camera', 'battery'],
        ];

        $item2 = [
            'category' => 'Electronics',
            'brand' => 'Samsung',
            'price_range' => 'high',
            'keywords' => ['smartphone', 'camera', 'display'],
        ];

        $similarity = $this->calculateItemSimilarity($item1, $item2);

        $this->assertGreaterThan(0.5, $similarity);
    }

    #[Test]
    public function it_builds_user_profile(): void
    {
        $userInteractions = [
            ['item_id' => 'item1', 'rating' => 5, 'features' => ['Electronics', 'Apple', 'high', 'smartphone']],
            ['item_id' => 'item2', 'rating' => 4, 'features' => ['Electronics', 'Apple', 'high', 'laptop']],
            ['item_id' => 'item3', 'rating' => 2, 'features' => ['Clothing', 'Nike', 'medium', 'shoes']],
        ];

        $userProfile = $this->buildUserProfile($userInteractions);

        $this->assertArrayHasKey('preferred_categories', $userProfile);
        $this->assertArrayHasKey('preferred_brands', $userProfile);
        $this->assertArrayHasKey('preferred_price_range', $userProfile);
        $this->assertArrayHasKey('preferred_keywords', $userProfile);
    }

    #[Test]
    public function it_recommends_items_based_on_content(): void
    {
        $userProfile = [
            'preferred_categories' => ['Electronics' => 0.8],
            'preferred_brands' => ['Apple' => 0.9, 'Samsung' => 0.6],
            'preferred_price_range' => ['high' => 0.7, 'medium' => 0.3],
            'preferred_keywords' => ['smartphone' => 0.8, 'camera' => 0.6],
        ];

        $candidateItems = [
            [
                'id' => 'item1',
                'category' => 'Electronics',
                'brand' => 'Apple',
                'price_range' => 'high',
                'keywords' => ['smartphone', 'camera'],
            ],
            [
                'id' => 'item2',
                'category' => 'Clothing',
                'brand' => 'Nike',
                'price_range' => 'medium',
                'keywords' => ['shoes', 'sports'],
            ],
        ];

        $recommendations = $this->getContentBasedRecommendations($userProfile, $candidateItems, 1);

        $this->assertCount(1, $recommendations);
        $this->assertEquals('item1', $recommendations[0]['id']);
    }

    #[Test]
    public function it_handles_text_similarity(): void
    {
        $text1 = 'iPhone 15 Pro Max with advanced camera system';
        $text2 = 'Samsung Galaxy S24 with professional camera features';

        $similarity = $this->calculateTextSimilarity($text1, $text2);

        $this->assertGreaterThan(0.05, $similarity); // تقليل الحد الأدنى أكثر
    }

    #[Test]
    public function it_extracts_keywords_from_text(): void
    {
        $text = 'iPhone 15 Pro Max smartphone with advanced camera system and long battery life';
        $keywords = $this->extractKeywords($text);

        $this->assertContains('smartphone', $keywords);
        $this->assertContains('camera', $keywords);
        $this->assertContains('battery', $keywords);
    }

    #[Test]
    public function it_calculates_tf_idf_scores(): void
    {
        $documents = [
            'doc1' => ['smartphone', 'camera', 'battery'],
            'doc2' => ['smartphone', 'display', 'camera'],
            'doc3' => ['laptop', 'keyboard', 'display'],
        ];

        $term = 'smartphone';
        $document = 'doc1';

        $tfIdf = $this->calculateTfIdf($documents, $term, $document);

        $this->assertGreaterThan(0, $tfIdf);
    }

    #[Test]
    public function it_handles_categorical_features(): void
    {
        $item1 = ['category' => 'Electronics', 'brand' => 'Apple'];
        $item2 = ['category' => 'Electronics', 'brand' => 'Samsung'];
        $item3 = ['category' => 'Clothing', 'brand' => 'Nike'];

        $similarity1 = $this->calculateCategoricalSimilarity($item1, $item2);
        $similarity2 = $this->calculateCategoricalSimilarity($item1, $item3);

        $this->assertGreaterThan($similarity2, $similarity1);
    }

    #[Test]
    public function it_handles_numerical_features(): void
    {
        $item1 = ['price' => 1000, 'rating' => 4.5];
        $item2 = ['price' => 1200, 'rating' => 4.3];
        $item3 = ['price' => 200, 'rating' => 3.8];

        $similarity1 = $this->calculateNumericalSimilarity($item1, $item2);
        $similarity2 = $this->calculateNumericalSimilarity($item1, $item3);

        $this->assertGreaterThan($similarity2, $similarity1);
    }

    #[Test]
    public function it_combines_multiple_similarity_measures(): void
    {
        $item1 = [
            'category' => 'Electronics',
            'brand' => 'Apple',
            'price' => 1000,
            'keywords' => ['smartphone', 'camera'],
        ];

        $item2 = [
            'category' => 'Electronics',
            'brand' => 'Samsung',
            'price' => 1200,
            'keywords' => ['smartphone', 'display'],
        ];

        $weights = [
            'categorical' => 0.3,
            'numerical' => 0.2,
            'textual' => 0.5,
        ];

        $combinedSimilarity = $this->calculateCombinedSimilarity($item1, $item2, $weights);

        $this->assertGreaterThan(0, $combinedSimilarity);
        $this->assertLessThanOrEqual(1, $combinedSimilarity);
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    private function extractItemFeatures(array $item): array
    {
        $price = $item['price'] ?? 0.0;
        $title = $item['title'] ?? '';
        $features = [
            'category' => $item['category'] ?? '',
            'brand' => $item['brand'] ?? '',
            'price_range' => $this->categorizePrice(is_numeric($price) ? (float) $price : 0.0),
            'keywords' => $this->extractKeywords(is_string($title) ? $title : ''),
        ];

        if (isset($item['features']) && is_array($item['features'])) {
            $features['keywords'] = array_merge($features['keywords'], $item['features']);
        }

        return $features;
    }

    private function categorizePrice(float $price): string
    {
        if ($price < 100) {
            return 'low';
        }
        if ($price < 500) {
            return 'medium';
        }
        if ($price < 1000) {
            return 'high';
        }

        return 'premium';
    }

    /**
     * @param  array<string, mixed>  $item1
     * @param  array<string, mixed>  $item2
     */
    private function calculateItemSimilarity(array $item1, array $item2): float
    {
        $weights = [
            'category' => 0.3,
            'brand' => 0.2,
            'price_range' => 0.2,
            'keywords' => 0.3,
        ];

        $similarities = [];

        // Categorical similarity
        $similarities['category'] = $item1['category'] === $item2['category'] ? 1.0 : 0.0;
        $similarities['brand'] = $item1['brand'] === $item2['brand'] ? 1.0 : 0.0;
        $similarities['price_range'] = $item1['price_range'] === $item2['price_range'] ? 1.0 : 0.0;

        // Keyword similarity
        $keywords1 = $item1['keywords'] ?? [];
        $keywords2 = $item2['keywords'] ?? [];

        if (is_array($keywords1) && is_array($keywords2)) {
            $commonKeywords = array_intersect($keywords1, $keywords2);
            $allKeywords = array_unique(array_merge($keywords1, $keywords2));
        } else {
            $commonKeywords = [];
            $allKeywords = [];
        }

        $similarities['keywords'] = empty($allKeywords) ? 0.0 : count($commonKeywords) / count($allKeywords);

        // Calculate weighted average
        $totalSimilarity = 0;
        $totalWeight = 0;

        foreach ($weights as $feature => $weight) {
            $totalSimilarity += $similarities[$feature] * $weight;
            $totalWeight += $weight;
        }

        return $totalWeight > 0 ? $totalSimilarity / $totalWeight : 0;
    }

    /**
     * @param  array<int, array<string, mixed>>  $userInteractions
     * @return array<string, mixed>
     */
    private function buildUserProfile(array $userInteractions): array
    {
        $profile = [
            'preferred_categories' => [],
            'preferred_brands' => [],
            'preferred_price_range' => [],
            'preferred_keywords' => [],
        ];

        foreach ($userInteractions as $interaction) {
            $rating = $interaction['rating'] ?? 0;
            $features = $interaction['features'] ?? [];

            // Weight features by rating
            $ratingFloat = is_numeric($rating) ? (float) $rating : 0.0;
            $weight = $ratingFloat / 5.0;

            // Update category preferences
            if (is_array($features) && isset($features[0]) && is_string($features[0])) {
                $category = $features[0];
                $profile['preferred_categories'][$category] =
                    ($profile['preferred_categories'][$category] ?? 0) + $weight;
            }

            // Update brand preferences
            if (is_array($features) && isset($features[1]) && is_string($features[1])) {
                $brand = $features[1];
                $profile['preferred_brands'][$brand] =
                    ($profile['preferred_brands'][$brand] ?? 0) + $weight;
            }

            // Update price range preferences
            if (is_array($features) && isset($features[2]) && is_string($features[2])) {
                $priceRange = $features[2];
                $profile['preferred_price_range'][$priceRange] =
                    ($profile['preferred_price_range'][$priceRange] ?? 0) + $weight;
            }

            // Update keyword preferences
            if (is_array($features)) {
                for ($i = 3; $i < count($features); $i++) {
                    $keyword = $features[$i];
                    if (is_string($keyword)) {
                        $profile['preferred_keywords'][$keyword] =
                            ($profile['preferred_keywords'][$keyword] ?? 0) + $weight;
                    }
                }
            }
        }

        // Normalize preferences
        foreach ($profile as $type => $preferences) {
            $total = array_sum($preferences);
            if ($total > 0) {
                foreach ($preferences as $key => $value) {
                    $profile[$type][$key] = $value / $total;
                }
            }
        }

        return $profile;
    }

    /**
     * @param  array<string, mixed>  $userProfile
     * @return array<int, array<string, mixed>>
     */

    /**
     * @param  array<string, mixed>  $userProfile
     * @param  array<int, array<string, mixed>>  $candidateItems
     * @return array<int, array<string, mixed>>
     */
    private function getContentBasedRecommendations(array $userProfile, array $candidateItems, int $limit): array
    {
        $recommendations = [];

        foreach ($candidateItems as $item) {
            $itemFeatures = $this->extractItemFeatures($item);
            $similarity = $this->calculateUserItemSimilarity($userProfile, $itemFeatures);

            if ($similarity > 0.3) { // Threshold for recommendations
                $recommendations[] = array_merge($item, ['similarity_score' => $similarity]);
            }
        }

        usort($recommendations, function ($a, $b) {
            return $b['similarity_score'] <=> $a['similarity_score'];
        });

        return array_slice($recommendations, 0, $limit);
    }

    /**
     * @param  array<string, mixed>  $userProfile
     * @param  array<string, mixed>  $itemFeatures
     */
    private function calculateUserItemSimilarity(array $userProfile, array $itemFeatures): float
    {
        $weights = [
            'category' => 0.3,
            'brand' => 0.2,
            'price_range' => 0.2,
            'keywords' => 0.3,
        ];

        $similarities = [];

        // Category similarity
        $category = $itemFeatures['category'] ?? '';
        if (is_string($category) && is_array($userProfile['preferred_categories'])) {
            $similarities['category'] = $userProfile['preferred_categories'][$category] ?? 0;
        } else {
            $similarities['category'] = 0;
        }

        // Brand similarity
        $brand = $itemFeatures['brand'] ?? '';
        if (is_string($brand) && is_array($userProfile['preferred_brands'])) {
            $similarities['brand'] = $userProfile['preferred_brands'][$brand] ?? 0;
        } else {
            $similarities['brand'] = 0;
        }

        // Price range similarity
        $priceRange = $itemFeatures['price_range'] ?? '';
        if (is_string($priceRange) && is_array($userProfile['preferred_price_range'])) {
            $similarities['price_range'] = $userProfile['preferred_price_range'][$priceRange] ?? 0;
        } else {
            $similarities['price_range'] = 0;
        }

        // Keyword similarity
        $itemKeywords = $itemFeatures['keywords'] ?? [];
        $userKeywords = $userProfile['preferred_keywords'] ?? [];
        $keywordSimilarity = 0;

        if (is_array($itemKeywords) && ! empty($itemKeywords)) {
            foreach ($itemKeywords as $keyword) {
                if (is_string($keyword) && is_array($userKeywords)) {
                    $keywordValue = $userKeywords[$keyword] ?? 0;
                    if (is_numeric($keywordValue)) {
                        $keywordSimilarity += (float) $keywordValue;
                    }
                }
            }
            $keywordSimilarity /= count($itemKeywords);
        }

        $similarities['keywords'] = $keywordSimilarity;

        // Calculate weighted average
        $totalSimilarity = 0;
        $totalWeight = 0;

        foreach ($weights as $feature => $weight) {
            $similarity = $similarities[$feature];
            if (is_numeric($similarity)) {
                $totalSimilarity += (float) $similarity * $weight;
            }
            $totalWeight += $weight;
        }

        return $totalWeight > 0 ? $totalSimilarity / $totalWeight : 0;
    }

    private function calculateTextSimilarity(string $text1, string $text2): float
    {
        $keywords1 = $this->extractKeywords($text1);
        $keywords2 = $this->extractKeywords($text2);

        $commonKeywords = array_intersect($keywords1, $keywords2);
        $allKeywords = array_unique(array_merge($keywords1, $keywords2));

        return empty($allKeywords) ? 0.0 : count($commonKeywords) / count($allKeywords);
    }

    /**
     * @return list<string>
     */
    private function extractKeywords(string $text): array
    {
        $text = strtolower($text);
        $words = preg_split('/\s+/', $text);

        if ($words === false) {
            return [];
        }

        // Remove common stop words
        $stopWords = ['the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by'];
        $words = array_filter($words, function ($word) use ($stopWords) {
            return ! in_array($word, $stopWords) && strlen($word) > 2;
        });

        return array_values($words);
    }

    /**
     * @param  array<string, list<string>>  $documents
     */
    private function calculateTfIdf(array $documents, string $term, string $document): float
    {
        $docTerms = $documents[$document] ?? [];
        if (! is_array($docTerms)) {
            return 0.0;
        }
        $termCount = array_count_values($docTerms);
        $tf = ($termCount[$term] ?? 0) / count($docTerms);

        $documentsWithTerm = 0;
        foreach ($documents as $docTerms) {
            if (is_array($docTerms) && in_array($term, $docTerms)) {
                $documentsWithTerm++;
            }
        }

        $idf = log(count($documents) / max(1, $documentsWithTerm));

        return $tf * $idf;
    }

    /**
     * @param  array<string, mixed>  $item1
     * @param  array<string, mixed>  $item2
     */
    private function calculateCategoricalSimilarity(array $item1, array $item2): float
    {
        $matches = 0;
        $total = 0;

        foreach ($item1 as $key => $value) {
            if (isset($item2[$key])) {
                $total++;
                if ($value === $item2[$key]) {
                    $matches++;
                }
            }
        }

        return $total > 0 ? $matches / $total : 0;
    }

    /**
     * @param  array<string, mixed>  $item1
     * @param  array<string, mixed>  $item2
     */
    private function calculateNumericalSimilarity(array $item1, array $item2): float
    {
        $similarities = [];

        foreach ($item1 as $key => $value1) {
            if (isset($item2[$key]) && is_numeric($value1) && is_numeric($item2[$key])) {
                $value2 = $item2[$key];
                $maxValue = max($value1, $value2);
                $minValue = min($value1, $value2);

                if ($maxValue > 0) {
                    $similarities[] = $minValue / $maxValue;
                }
            }
        }

        return empty($similarities) ? 0 : array_sum($similarities) / count($similarities);
    }

    /**
     * @param  array<string, mixed>  $item1
     * @param  array<string, mixed>  $item2
     * @param  array<string, float>  $weights
     */
    private function calculateCombinedSimilarity(array $item1, array $item2, array $weights): float
    {
        $categoricalSimilarity = $this->calculateCategoricalSimilarity($item1, $item2);
        $numericalSimilarity = $this->calculateNumericalSimilarity($item1, $item2);
        $keywords1 = $item1['keywords'] ?? [];
        $keywords2 = $item2['keywords'] ?? [];
        $text1 = is_array($keywords1) ? implode(' ', $keywords1) : '';
        $text2 = is_array($keywords2) ? implode(' ', $keywords2) : '';

        $textualSimilarity = $this->calculateTextSimilarity($text1, $text2);

        return $categoricalSimilarity * $weights['categorical'] +
            $numericalSimilarity * $weights['numerical'] +
            $textualSimilarity * $weights['textual'];
    }
}

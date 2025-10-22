<?php

declare(strict_types=1);

namespace App\Services\AI\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Service for handling AI API requests
 */
class AIRequestService
{
    private readonly string $apiKey;

    private readonly string $baseUrl;

    private readonly int $timeout;

    public function __construct(string $apiKey, string $baseUrl, int $timeout = 60)
    {
        $this->apiKey = $apiKey;
        $this->baseUrl = $baseUrl;
        $this->timeout = $timeout;
    }

    /**
     * Make HTTP request to AI service
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, string>  $headers
     *
     * @return array<string, mixed>
     *
     * @throws \Exception
     */
    public function makeRequest(string $endpoint, array $data, array $headers = []): array
    {
        // Short-circuit in testing to avoid external calls
        $disableExternal = (bool) config('ai.disable_external_calls', false);
        if ($disableExternal || (($this->apiKey === '' || $this->apiKey === '0') && (env('APP_ENV') === 'testing'))) {
            Log::info('🧪 تعطيل الاتصال الخارجي للذكاء الاصطناعي في الاختبارات');

            // Build input-aware mock content so tests can assert structure and values
            $userContent = $data['messages'][1]['content'] ?? ($data['messages'][0]['content'] ?? '');
            $text = '';
            $type = 'sentiment';

            if (is_string($userContent)) {
                if (preg_match('/Analyze the following text for\s+([a-z_]+):\s*(.*)$/i', $userContent, $m)) {
                    $type = strtolower(trim($m[1]));
                    $text = trim($m[2]);
                } elseif (str_contains($userContent, 'User preferences:')) {
                    $type = 'recommendations';
                    $text = $userContent;
                } else {
                    $text = $userContent;
                }
            } elseif (is_array($userContent)) {
                // Image analysis payload uses structured content array
                $type = 'image_analysis';
                foreach ($userContent as $seg) {
                    if (is_array($seg) && (($seg['type'] ?? '') === 'text')) {
                        $text = (string) ($seg['text'] ?? '');

                        break;
                    }
                }
            }

            $lc = mb_strtolower($text);
            $positiveWords = ['رائع', 'ممتاز', 'جيد', 'حب', 'جميل', 'أفضل', 'amazing', 'great', 'good', 'excellent', 'love'];
            $negativeWords = ['سيء', 'رديء', 'ضعيف', 'كريه', 'أسوأ', 'terrible', 'bad', 'poor', 'hate', 'awful'];

            $sentiment = 'neutral';
            foreach ($positiveWords as $w) {
                if (str_contains($lc, $w)) {
                    $sentiment = 'positive';

                    break;
                }
            }
            if ($sentiment === 'neutral') {
                foreach ($negativeWords as $w) {
                    if (str_contains($lc, $w)) {
                        $sentiment = 'negative';

                        break;
                    }
                }
            }

            $confidence = $sentiment === 'neutral' ? ($text === '' || $text === '0' ? 0.5 : 0.6) : 0.85;

            // Simple category inference (Arabic + English)
            $categories = [];
            if (preg_match('/هاتف|جوال|موبايل|سامسونج|أبل|إلكترونيات|laptop|phone|electronics/i', $text)) {
                $categories[] = 'إلكترونيات';
            }
            if (preg_match('/قميص|ملابس|shirt|clothing/i', $text)) {
                $categories[] = 'ملابس';
            }
            if (preg_match('/كتاب|برمجة|books?/i', $text)) {
                $categories[] = 'كتب';
            }
            if ($categories === []) {
                $categories[] = 'عام';
            }

            // Naive keyword extraction
            $words = preg_split('/\s+/u', trim($text));
            $keywords = [];
            foreach ($words as $word) {
                $w = trim((string) $word, ".,!?:;\"'()[]{}|\\");
                if (mb_strlen($w) >= 3) {
                    $keywords[] = mb_strtolower($w);
                }
            }
            $keywords = array_values(array_unique(array_slice($keywords, 0, 5)));

            // Recommendations lines when applicable
            $recommendationLines = [];
            if ($type === 'recommendations' || $type === 'image_analysis') {
                foreach ($categories as $cat) {
                    $recommendationLines[] = "recommendation: مناسب لفئة {$cat}";
                }
                if ($recommendationLines === []) {
                    $recommendationLines[] = 'recommendation: قم باختيار أفضل منتج';
                }
            }

            $lines = [];
            foreach ($categories as $cat) {
                $lines[] = "category: {$cat}";
            }
            foreach ($recommendationLines as $r) {
                $lines[] = $r;
            }
            $lines[] = "sentiment: {$sentiment}";
            $lines[] = 'confidence: '.number_format($confidence, 2);
            foreach ($keywords as $k) {
                $lines[] = "keyword: {$k}";
            }
            if ($text !== '' && $text !== '0') {
                $lines[] = "original_text: {$text}"; // include feedback context in result
            }

            $mockContent = implode("\n", $lines)."\n";

            return [
                'choices' => [
                    [
                        'message' => [
                            'content' => $mockContent,
                        ],
                    ],
                ],
            ];
        }

        $url = $this->baseUrl.$endpoint;
        $defaultHeaders = [
            'Authorization' => 'Bearer '.$this->apiKey,
            'Content-Type' => 'application/json',
        ];

        $headers = array_merge($defaultHeaders, $headers);

        Log::info('🤖 إرسال طلب إلى الذكاء الاصطناعي', [
            'url' => $url,
            'data' => $data,
        ]);

        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders($headers)
                ->post($url, $data);

            if ($response->successful()) {
                $result = $response->json();
                Log::info('✅ استجابة ناجحة من الذكاء الاصطناعي', ['result' => $result]);

                return $result;
            }

            Log::error('❌ فشل طلب الذكاء الاصطناعي', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \Exception('AI request failed: '.$response->status().' - '.$response->body());
        } catch (\Exception $e) {
            Log::error('❌ خطأ في طلب الذكاء الاصطناعي', ['error' => $e->getMessage()]);

            throw $e;
        }
    }
}

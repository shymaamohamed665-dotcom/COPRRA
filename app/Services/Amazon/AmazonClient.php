<?php

namespace App\Services\Amazon;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AmazonClient
{
    protected string $accessKey;

    protected string $secretKey;

    protected string $associateTag;

    public function __construct()
    {
        $this->accessKey = (string) env('AMAZON_ACCESS_KEY', '');
        $this->secretKey = (string) env('AMAZON_SECRET_KEY', '');
        $this->associateTag = (string) env('AMAZON_ASSOCIATE_TAG', '');
    }

    /**
     * Search products with throttling, retry, and caching.
     * Note: Placeholder implementation; replace with signed PA-API calls.
     */
    public function search(string $query, int $limit = 10): array
    {
        $cacheKey = 'amazon:search:'.md5($query.':'.$limit);

        return Cache::remember($cacheKey, now()->addMinutes(15), function () use ($query, $limit) {
            try {
                $response = Http::retry(3, 500)->timeout(10)->get('https://api.example.com/amazon/search', [
                    'q' => $query,
                    'limit' => $limit,
                    'tag' => $this->associateTag,
                ]);
                if ($response->ok()) {
                    return $response->json('items', []);
                }
            } catch (\Throwable $e) {
                Log::warning('Amazon search failed: '.$e->getMessage());
            }

            return [];
        });
    }
}

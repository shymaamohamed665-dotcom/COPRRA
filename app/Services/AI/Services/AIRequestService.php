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
    private string $apiKey;

    private string $baseUrl;

    private int $timeout;

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
     * @return array<string, mixed>
     *
     * @throws \Exception
     */
    public function makeRequest(string $endpoint, array $data, array $headers = []): array
    {
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

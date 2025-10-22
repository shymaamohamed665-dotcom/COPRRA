<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Store;
use PHPUnit\Framework\TestCase;

final class URLServiceTest extends TestCase
{
    public function testGenerateAffiliateUrlEncodesUrlAndInjectsAffiliateCode(): void
    {
        $store = new Store();
        $store->affiliate_base_url = 'https://aff.example.com/{AFFILIATE_CODE}?u={URL}';
        $store->affiliate_code = 'AFF123';

        $productUrl = 'https://example.com/product?id=42';
        $affiliateUrl = $store->generateAffiliateUrl($productUrl);

        $this->assertIsString($affiliateUrl);
        $this->assertStringStartsWith('https://aff.example.com/AFF123?u=', $affiliateUrl);
        // Expect slashes to remain unencoded while the rest is rawurlencoded
        $this->assertStringContainsString('https%3A//example.com/product%3Fid%3D42', $affiliateUrl);
    }

    public function testGenerateAffiliateUrlReturnsOriginalWhenMissingConfig(): void
    {
        $store = new Store();
        $store->affiliate_base_url = null; // missing base url
        $store->affiliate_code = 'AFF123';

        $productUrl = 'https://example.com/product?id=42';
        $affiliateUrl = $store->generateAffiliateUrl($productUrl);
        $this->assertSame($productUrl, $affiliateUrl);

        $store->affiliate_base_url = 'https://aff.example.com/{AFFILIATE_CODE}?u={URL}';
        $store->affiliate_code = null; // missing code
        $affiliateUrl2 = $store->generateAffiliateUrl($productUrl);
        $this->assertSame($productUrl, $affiliateUrl2);
    }

    public function testGenerateAffiliateUrlKeepsPathSlashesUnencoded(): void
    {
        $store = new Store();
        $store->affiliate_base_url = 'https://aff.example.com/{AFFILIATE_CODE}?u={URL}';
        $store->affiliate_code = 'CODE9';

        $productUrl = 'https://example.com/a/b/c?x=1&y=2';
        $affiliateUrl = $store->generateAffiliateUrl($productUrl);

        $this->assertStringContainsString('https%3A//example.com/a/b/c%3Fx%3D1%26y%3D2', $affiliateUrl);
        $this->assertStringContainsString('https://aff.example.com/CODE9?u=', $affiliateUrl);
    }
}

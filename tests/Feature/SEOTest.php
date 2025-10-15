<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use App\Services\SEOService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * @covers \App\Services\SEOService
 * @covers \App\Console\Commands\SEOAudit
 * @covers \App\Console\Commands\GenerateSitemap
 */
class SEOTest extends TestCase
{
    use RefreshDatabase;

    protected SEOService $seoService;

    protected function setUp(): void
    {
        parent::setUp();
        // استخدم الحاوية لإنشاء الخدمة مع حقن الاعتماديات تلقائيًا
        $this->seoService = $this->app->make(SEOService::class);
    }

    public function test_generates_meta_data_for_product(): void
    {
        $product = Product::factory()->create([
            'name' => 'Test Product',
            'description' => 'This is a test product description for SEO testing purposes.',
        ]);

        $metaData = $this->seoService->generateMetaData($product, 'Product');

        $this->assertIsArray($metaData);
        $this->assertArrayHasKey('title', $metaData);
        $this->assertArrayHasKey('description', $metaData);
        $this->assertArrayHasKey('keywords', $metaData);
        $this->assertArrayHasKey('og_title', $metaData);
        $this->assertArrayHasKey('og_description', $metaData);
        $this->assertArrayHasKey('og_image', $metaData);
        $this->assertArrayHasKey('canonical', $metaData);

        $this->assertStringContainsString('Test Product', $metaData['title']);
        $this->assertStringContainsString('test product description', $metaData['description']);
    }

    public function test_generates_meta_data_for_category(): void
    {
        $category = Category::factory()->create([
            'name' => 'Electronics',
            'description' => 'Browse our electronics category for the best deals.',
        ]);

        $metaData = $this->seoService->generateMetaData($category, 'Category');

        $this->assertIsArray($metaData);
        $this->assertStringContainsString('Electronics', $metaData['title']);
        $this->assertStringContainsString('electronics', strtolower($metaData['description']));
    }

    public function test_generates_meta_data_for_store(): void
    {
        $store = Store::factory()->create([
            'name' => 'Amazon',
            'description' => 'Shop at Amazon for great prices.',
        ]);

        $metaData = $this->seoService->generateMetaData($store, 'Store');

        $this->assertIsArray($metaData);
        $this->assertStringContainsString('Amazon', $metaData['title']);
        $this->assertStringContainsString('Amazon', $metaData['description']);
    }

    public function test_generates_title_with_correct_length(): void
    {
        $product = Product::factory()->create([
            'name' => 'Very Long Product Name That Should Be Truncated For SEO Purposes Because It Exceeds Maximum Length',
        ]);

        $metaData = $this->seoService->generateMetaData($product, 'Product');

        $this->assertLessThanOrEqual(60, strlen($metaData['title']));
    }

    public function test_generates_description_with_correct_length(): void
    {
        $product = Product::factory()->create([
            'description' => str_repeat('This is a very long description. ', 20),
        ]);

        $metaData = $this->seoService->generateMetaData($product, 'Product');

        $this->assertLessThanOrEqual(160, strlen($metaData['description']));
    }

    public function test_validates_meta_data_correctly(): void
    {
        $validMetaData = [
            'title' => 'This is a valid title for SEO testing',
            'description' => 'This is a valid description that is long enough for SEO purposes and contains relevant information.',
            'keywords' => 'test, seo, validation',
            'canonical' => 'https://example.com/test',
        ];

        $issues = $this->seoService->validateMetaData($validMetaData);

        $this->assertEmpty($issues);
    }

    public function test_detects_missing_title(): void
    {
        $invalidMetaData = [
            'title' => '',
            'description' => 'Valid description here',
            'keywords' => 'test',
            'canonical' => 'https://example.com',
        ];

        $issues = $this->seoService->validateMetaData($invalidMetaData);

        $this->assertNotEmpty($issues);
        $this->assertStringContainsString('Title', implode(' ', $issues));
    }

    public function test_detects_short_title(): void
    {
        $invalidMetaData = [
            'title' => 'Short',
            'description' => 'Valid description that is long enough for SEO purposes',
            'keywords' => 'test',
            'canonical' => 'https://example.com',
        ];

        $issues = $this->seoService->validateMetaData($invalidMetaData);

        $this->assertNotEmpty($issues);
        $this->assertStringContainsString('too short', implode(' ', $issues));
    }

    public function test_detects_long_title(): void
    {
        $invalidMetaData = [
            'title' => str_repeat('Very long title ', 10),
            'description' => 'Valid description that is long enough for SEO purposes',
            'keywords' => 'test',
            'canonical' => 'https://example.com',
        ];

        $issues = $this->seoService->validateMetaData($invalidMetaData);

        $this->assertNotEmpty($issues);
        $this->assertStringContainsString('too long', implode(' ', $issues));
    }

    public function test_detects_missing_description(): void
    {
        $invalidMetaData = [
            'title' => 'Valid title for testing purposes',
            'description' => '',
            'keywords' => 'test',
            'canonical' => 'https://example.com',
        ];

        $issues = $this->seoService->validateMetaData($invalidMetaData);

        $this->assertNotEmpty($issues);
        $this->assertStringContainsString('Description', implode(' ', $issues));
    }

    public function test_generates_structured_data_for_product(): void
    {
        $product = Product::factory()->create([
            'name' => 'Test Product',
            'description' => 'Test description',
            'price' => 100.00,
        ]);

        $structuredData = $this->seoService->generateStructuredData($product);

        $this->assertIsArray($structuredData);
        $this->assertEquals('https://schema.org/', $structuredData['@context']);
        $this->assertEquals('Product', $structuredData['@type']);
        $this->assertEquals('Test Product', $structuredData['name']);
        $this->assertArrayHasKey('offers', $structuredData);
    }

    public function test_generates_breadcrumb_structured_data(): void
    {
        $breadcrumbs = [
            ['name' => 'Home', 'url' => 'https://example.com'],
            ['name' => 'Electronics', 'url' => 'https://example.com/electronics'],
            ['name' => 'Laptops', 'url' => 'https://example.com/electronics/laptops'],
        ];

        $structuredData = $this->seoService->generateBreadcrumbData($breadcrumbs);

        $this->assertIsArray($structuredData);
        $this->assertEquals('https://schema.org/', $structuredData['@context']);
        $this->assertEquals('BreadcrumbList', $structuredData['@type']);
        $this->assertCount(3, $structuredData['itemListElement']);
    }

    public function test_generates_sitemap_successfully(): void
    {
        // Create test data
        Product::factory()->count(5)->create(['is_active' => true]);
        Category::factory()->count(3)->create(['is_active' => true]);
        Store::factory()->count(2)->create(['is_active' => true]);

        // Generate sitemap
        Artisan::call('sitemap:generate');

        // Check if sitemap file exists
        $this->assertTrue(File::exists(public_path('sitemap.xml')));

        // Check sitemap content
        $content = File::get(public_path('sitemap.xml'));
        $this->assertStringContainsString('<?xml version="1.0" encoding="UTF-8"?>', $content);
        $this->assertStringContainsString('<urlset', $content);
        $this->assertStringContainsString('</urlset>', $content);

        // Clean up
        File::delete(public_path('sitemap.xml'));
    }

    public function test_sitemap_includes_products(): void
    {
        $product = Product::factory()->create([
            'name' => 'Test Product',
            'slug' => 'test-product',
            'is_active' => true,
        ]);

        Artisan::call('sitemap:generate');

        $content = File::get(public_path('sitemap.xml'));
        $this->assertStringContainsString('test-product', $content);

        File::delete(public_path('sitemap.xml'));
    }

    public function test_seo_audit_command_runs_successfully(): void
    {
        Product::factory()->count(3)->create();

        $exitCode = Artisan::call('seo:audit');

        $this->assertEquals(0, $exitCode);
    }

    public function test_seo_audit_can_fix_issues(): void
    {
        $product = Product::factory()->create([
            'name' => 'Test Product',
            'meta_title' => null,
            'meta_description' => null,
        ]);

        Artisan::call('seo:audit --fix');

        $product->refresh();

        // Check if meta fields were populated
        $this->assertNotNull($product->meta_title);
        $this->assertNotNull($product->meta_description);
    }

    public function test_robots_txt_file_exists(): void
    {
        $this->assertTrue(File::exists(public_path('robots.txt')));
    }

    public function test_robots_txt_has_correct_content(): void
    {
        $content = File::get(public_path('robots.txt'));

        $this->assertStringContainsString('User-agent:', $content);
        $this->assertStringContainsString('Disallow: /admin', $content);
        $this->assertStringContainsString('Sitemap:', $content);
    }
}

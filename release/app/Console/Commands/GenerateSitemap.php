<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

/**
 * @psalm-suppress PropertyNotSetInConstructor, UnusedClass
 */
final class GenerateSitemap extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'sitemap:generate';

    /**
     * The console command description.
     */
    protected $description = 'Generate XML sitemap for SEO optimization';

    private readonly Filesystem $filesystem;

    public function __construct(Filesystem $filesystem)
    {
        parent::__construct();
        $this->filesystem = $filesystem;
    }

    public function handle(): int
    {
        $this->info('Generating sitemap...');

        $sitemap = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $sitemap .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

        // Homepage
        $sitemap .= $this->addUrl(url('/'), '1.0', 'daily');

        // Products
        Product::query()->chunk(100, function (\Illuminate\Support\Collection $products) use (&$sitemap): void {
            foreach ($products as $product) {
                $sitemap .= $this->addUrl(
                    route('products.show', $product->slug),
                    '0.8',
                    'weekly'
                );
            }
        });

        // Categories
        Category::query()->chunk(100, function (\Illuminate\Support\Collection $categories) use (&$sitemap): void {
            foreach ($categories as $category) {
                $sitemap .= $this->addUrl(
                    route('categories.show', $category->id),
                    '0.7',
                    'weekly'
                );
            }
        });

        // Brands
        Brand::query()->chunk(100, function (\Illuminate\Support\Collection $brands) use (&$sitemap): void {
            foreach ($brands as $brand) {
                $sitemap .= $this->addUrl(
                    route('brands.show', $brand->id),
                    '0.6',
                    'monthly'
                );
            }
        });

        $sitemap .= '</urlset>';

        $this->filesystem->put(public_path('sitemap.xml'), $sitemap);

        $this->info('Sitemap generated successfully at '.public_path('sitemap.xml'));

        return Command::SUCCESS;
    }

    private function addUrl(string $url, string $priority, string $changefreq): string
    {
        $lastmod = Carbon::now()->toAtomString();

        return "  <url>\n    <loc>{$url}</loc>\n    <priority>{$priority}</priority>\n    <changefreq>{$changefreq}</changefreq>\n    <lastmod>{$lastmod}</lastmod>\n  </url>\n";
    }
}

<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\Category;
use App\Models\PriceAlert;
use App\Models\PriceOffer;
use App\Models\Product;
use App\Models\Review;
use App\Models\Store;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * @property string $signature
 * @property string $description
 */
final class StatsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'coprra:stats {--detailed : Show detailed statistics}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display comprehensive statistics about the COPRRA platform';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('📊 COPRRA Platform Statistics');
        $this->line('='.str_repeat('=', 50));

        $this->displayBasicStats();
        $this->handleDetailedStatsOption();

        $this->newLine();
        $this->info('✅ Statistics generated successfully!');

        return Command::SUCCESS;
    }

    private function handleDetailedStatsOption(): void
    {
        if ($this->option('detailed')) {
            $this->newLine();
            $this->displayDetailedStats();
        }
    }

    private function displayBasicStats(): void
    {
        $stats = [
            ['Metric', 'Count'],
            ['Products', (float) Product::query()->count()],
            ['Active Products', (float) Product::query()->where('is_active', true)->count()],
            ['Stores', (float) Store::query()->count()],
            ['Active Stores', (float) Store::query()->where('is_active', true)->count()],
            ['Brands', (float) Brand::query()->count()],
            ['Categories', (float) Category::query()->count()],
            ['Price Offers', (float) PriceOffer::query()->count()],
            ['In Stock Offers', (float) PriceOffer::query()->where('in_stock', true)->count()],
            ['Reviews', (float) Review::query()->count()],
            ['Users', (float) User::query()->count()],
            ['Price Alerts', (float) PriceAlert::query()->count()],
            ['Active Alerts', (float) PriceAlert::query()->where('is_active', true)->count()],
        ];

        $this->table($stats[0], array_slice($stats, 1));
    }

    private function displayDetailedStats(): void
    {
        $this->info('📈 Detailed Statistics');

        $this->displayPriceStats();
        $this->displayTopCategories();
        $this->displayTopBrands();
        $this->displayStoreStats();
        $this->displayRecentActivity();
        $this->displayDatabaseInfo();
    }

    private function displayPriceStats(): void
    {
        $avgPriceValue = PriceOffer::query()->avg('price');
        $avgPrice = is_numeric($avgPriceValue) ? (float) $avgPriceValue : 0.0;

        $minPriceValue = PriceOffer::query()->min('price');
        $minPrice = is_numeric($minPriceValue) ? (float) $minPriceValue : 0.0;

        $maxPriceValue = PriceOffer::query()->max('price');
        $maxPrice = is_numeric($maxPriceValue) ? (float) $maxPriceValue : 0.0;

        $this->table(['Price Metric', 'Value'], [
            ['Average Price', '$'.number_format((float) $avgPrice, 2)],
            ['Minimum Price', '$'.number_format((float) $minPrice, 2)],
            ['Maximum Price', '$'.number_format((float) $maxPrice, 2)],
        ]);
    }

    private function displayTopCategories(): void
    {
        $topCategories = Category::query()->withCount('products')
            ->orderBy('products_count', 'desc')
            ->take(5)
            ->get();

        if ($topCategories->isNotEmpty()) {
            $this->info('🏆 Top 5 Categories by Product Count');
            $categoryData = $topCategories->map(static fn ($category): array => [$category->name, $category->products_count])->toArray();

            $this->table(['Category', 'Products'], $categoryData);
        }
    }

    private function displayTopBrands(): void
    {
        $topBrands = Brand::query()->withCount('products')
            ->orderBy('products_count', 'desc')
            ->take(5)
            ->get();

        if ($topBrands->isNotEmpty()) {
            $this->info('🏆 Top 5 Brands by Product Count');
            $brandData = $topBrands->map(static fn ($brand): array => [$brand->name, $brand->products_count])->toArray();

            $this->table(['Brand', 'Products'], $brandData);
        }
    }

    private function displayStoreStats(): void
    {
        $storeStats = Store::query()->withCount('priceOffers')
            ->orderBy('price_offers_count', 'desc')
            ->take(5)
            ->get();

        if ($storeStats->isNotEmpty()) {
            $this->info('🏪 Top 5 Stores by Price Offers');
            $storeData = $storeStats->map(static fn ($store): array => [$store->name, $store->price_offers_count])->toArray();

            $this->table(['Store', 'Price Offers'], $storeData);
        }
    }

    private function displayRecentActivity(): void
    {
        $recentProducts = Product::query()->where('created_at', '>=', now()->subDays(7))->count();
        $recentOffers = PriceOffer::query()->where('created_at', '>=', now()->subDays(7))->count();
        $recentUsers = User::query()->where('created_at', '>=', now()->subDays(7))->count();

        $this->info('📅 Activity in Last 7 Days');
        $this->table(['Activity', 'Count'], [
            ['New Products', $recentProducts],
            ['New Price Offers', $recentOffers],
            ['New Users', $recentUsers],
        ]);
    }

    private function displayDatabaseInfo(): void
    {
        $this->info('💾 Database Information');
        $totalRecords = (float) (Product::query()->count() + Store::query()->count() + Brand::query()->count() +
            Category::query()->count() + PriceOffer::query()->count() + Review::query()->count() +
            User::query()->count() + PriceAlert::query()->count());

        $this->table(['Database Metric', 'Value'], [
            ['Total Records', number_format($totalRecords)],
            ['Estimated Size', $this->formatBytes((int) ($totalRecords * 1024))], // Rough estimate
        ]);
    }

    private function formatBytes(int $size, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $unitsCount = count($units) - 1;
        for ($i = 0; $size > 1024 && $i < $unitsCount; $i++) {
            $size /= 1024;
        }

        return round($size, $precision).' '.$units[$i];
    }
}

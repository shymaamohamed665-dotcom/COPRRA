<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

final class SEOService
{
    public function __construct(
        private readonly UrlGenerator $urlGenerator,
        private readonly Repository $configRepository,
        private readonly Str $str
    ) {}

    /**
     * Generate SEO meta data for a model.
     *
     * @return string[]
     *
     * @psalm-return array{title: string, description: string, keywords: string, og_title: string, og_description: string, og_image: string, og_type: 'product'|'website', og_url: string, canonical: string, robots: 'index, follow'}
     */
    public function generateMetaData(Model $model, ?string $type = null): array
    {
        $type = $type !== null ? $type : class_basename($model);

        return match ($type) {
            'Product' => $model instanceof Product ? $this->generateProductMeta($model) : $this->generateDefaultMeta(),
            'Category' => $model instanceof Category
                ? $this->generateCategoryMeta($model)
                : $this->generateDefaultMeta(),
            'Store' => $model instanceof Store ? $this->generateStoreMeta($model) : $this->generateDefaultMeta(),
            default => $this->generateDefaultMeta(),
        };
    }

    /**
     * Validate SEO meta data.
     *
     * @param  array<string, string|null>  $metaData
     * @return string[]
     *
     * @psalm-return array<int, string>
     */
    public function validateMetaData(array $metaData): array
    {
        $issues = [];

        $this->validateTitle($metaData, $issues);
        $this->validateDescription($metaData, $issues);
        $this->validateKeywords($metaData, $issues);
        $this->validateCanonical($metaData, $issues);

        return $issues;
    }

    /**
     * Generate JSON-LD structured data for a product.
     *
     * @return array<string, mixed>
     */
    public function generateStructuredData(Product $product): array
    {
        $url = $this->urlGenerator->route('products.show', $product->slug);

        return [
            '@context' => 'https://schema.org/',
            '@type' => 'Product',
            'name' => $this->safeCastToString($product->name),
            'offers' => [
                '@type' => 'Offer',
                'url' => $this->safeCastToString($url),
            ],
        ];
    }

    /**
     * Generate JSON-LD breadcrumb structured data.
     *
     * @param  array<int, array{name: string, url: string}>  $breadcrumbs
     * @return array<string, mixed>
     */
    public function generateBreadcrumbData(array $breadcrumbs): array
    {
        $items = [];
        foreach ($breadcrumbs as $index => $crumb) {
            $items[] = [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $crumb['name'],
                'item' => $crumb['url'],
            ];
        }

        return [
            '@context' => 'https://schema.org/',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $items,
        ];
    }

    /**
     * Generate meta data for a product.
     *
     * @return string[]
     *
     * @psalm-return array{title: string, description: string, keywords: string, og_title: string, og_description: string, og_image: string, og_type: 'product', og_url: string, canonical: string, robots: 'index, follow'}
     */
    protected function generateProductMeta(Product $product): array
    {
        $title = $this->generateTitle($this->safeCastToString($product->name));
        $productDescription = $product->description && $product->description !== ''
            ? $product->description
            : $product->name;
        $description = $this->generateDescription($this->safeCastToString($productDescription));
        $keywords = $this->generateKeywords($product);

        $imageUrl = $product->image_url && $product->image_url !== ''
            ? $product->image_url
            : $this->urlGenerator->asset('images/default-product.png');

        return [
            'title' => $title,
            'description' => $description,
            'keywords' => $keywords,
            'og_title' => $title,
            'og_description' => $description,
            'og_image' => $this->safeCastToString($imageUrl),
            'og_type' => 'product',
            'og_url' => $this->safeCastToString(
                $this->urlGenerator->route('products.show', $product->slug)
            ),
            'canonical' => $this->safeCastToString(
                $this->urlGenerator->route('products.show', $product->slug)
            ),
            'robots' => 'index, follow',
        ];
    }

    /**
     * Generate meta data for a category.
     *
     * @return string[]
     *
     * @psalm-return array{title: string, description: string, keywords: string, og_title: string, og_description: string, og_image: string, og_type: 'website', og_url: string, canonical: string, robots: 'index, follow'}
     */
    protected function generateCategoryMeta(Category $category): array
    {
        $title = $this->generateTitle($this->safeCastToString($category->name).' - Products');
        $categoryDescription = $category->description && $category->description !== ''
            ? $category->description
            : 'Browse '.$this->safeCastToString($category->name).' products and compare prices';
        $description = $this->generateDescription($this->safeCastToString($categoryDescription));

        $imageUrl = $category->image_url && $category->image_url !== ''
            ? $category->image_url
            : $this->urlGenerator->asset('images/default-category.png');

        return [
            'title' => $title,
            'description' => $description,
            'keywords' => $this->generateKeywords($category),
            'og_title' => $title,
            'og_description' => $description,
            'og_image' => $this->safeCastToString($imageUrl),
            'og_type' => 'website',
            'og_url' => $this->safeCastToString(
                $this->urlGenerator->route('categories.show', $category->slug)
            ),
            'canonical' => $this->safeCastToString(
                $this->urlGenerator->route('categories.show', $category->slug)
            ),
            'robots' => 'index, follow',
        ];
    }

    /**
     * Generate meta data for a store.
     *
     * @return string[]
     *
     * @psalm-return array{title: string, description: string, keywords: string, og_title: string, og_description: string, og_image: string, og_type: 'website', og_url: string, canonical: string, robots: 'index, follow'}
     */
    protected function generateStoreMeta(Store $store): array
    {
        $title = $this->generateTitle($this->safeCastToString($store->name).' - Store');
        $storeDescription = $store->description && $store->description !== ''
            ? $store->description
            : 'Shop at '.$this->safeCastToString($store->name).' and compare prices';
        $description = $this->generateDescription($this->safeCastToString($storeDescription));

        $imageUrl = $store->logo_url && $store->logo_url !== ''
            ? $store->logo_url
            : $this->urlGenerator->asset('images/default-store.png');

        $storeUrl = Route::has('stores.show')
            ? $this->urlGenerator->route('stores.show', $store->slug)
            : $this->urlGenerator->to('stores/'.$this->safeCastToString($store->slug));

        return [
            'title' => $title,
            'description' => $description,
            'keywords' => $this->generateKeywords($store),
            'og_title' => $title,
            'og_description' => $description,
            'og_image' => $this->safeCastToString($imageUrl),
            'og_type' => 'website',
            'og_url' => $this->safeCastToString($storeUrl),
            'canonical' => $this->safeCastToString($storeUrl),
            'robots' => 'index, follow',
        ];
    }

    /**
     * Generate default meta data.
     *
     * @return string[]
     *
     * @psalm-return array{title: string, description: 'Compare prices across multiple stores and find the best deals', keywords: 'price comparison, shopping, deals, best prices, online shopping', og_title: string, og_description: 'Compare prices across multiple stores and find the best deals', og_image: string, og_type: 'website', og_url: string, canonical: string, robots: 'index, follow'}
     */
    protected function generateDefaultMeta(): array
    {
        $appName = $this->safeCastToString($this->configRepository->get('app.name', 'COPRRA'));
        $description = 'Compare prices across multiple stores and find the best deals';

        return [
            'title' => $appName.' - Price Comparison Platform',
            'description' => $description,
            'keywords' => 'price comparison, shopping, deals, best prices, online shopping',
            'og_title' => $appName,
            'og_description' => $description,
            'og_image' => $this->safeCastToString(
                $this->urlGenerator->asset('images/og-default.png')
            ),
            'og_type' => 'website',
            'og_url' => $this->safeCastToString($this->urlGenerator->to('/')),
            'canonical' => $this->safeCastToString($this->urlGenerator->to('/')),
            'robots' => 'index, follow',
        ];
    }

    /**
     * Generate optimized title (50-60 characters).
     */
    protected function generateTitle(string $title): string
    {
        $appName = $this->safeCastToString($this->configRepository->get('app.name', 'COPRRA'));
        $maxLength = 60;

        // If title is too long, truncate it
        if (strlen($title) > ($maxLength - strlen($appName) - 3)) {
            $title = $this->str->limit(
                $title,
                $maxLength - strlen($appName) - 6,
                ''
            );
        }

        return $title.' | '.$appName;
    }

    /**
     * Generate optimized description (150-160 characters).
     */
    protected function generateDescription(string $description): string
    {
        $maxLength = 160;

        // Strip HTML tags
        $description = strip_tags($description);

        // Truncate if too long
        if (strlen($description) > $maxLength) {
            $description = $this->str->limit($description, $maxLength - 3, '...');
        }

        return $description;
    }

    /**
     * Generate keywords from model.
     */
    protected function generateKeywords(Model $model): string
    {
        $keywords = [];

        $this->addModelNameToKeywords($model, $keywords);
        $this->addProductSpecificKeywords($model, $keywords);
        $this->addGenericKeywords($keywords);

        return $this->formatKeywords($keywords);
    }

    /**
     * Generate product structured data.
     *
     * @return array<string, array<string, string|float>|string>
     */
    protected function generateProductStructuredData(Product $product): array
    {
        $data = $this->generateBasicProductData($product);
        $data = $this->addProductOffersData($product, $data);

        return $this->addProductRatingData($product, $data);
    }

    /**
     * Validate title field.
     *
     * @param  array<string,?string>  $metaData
     * @param  array<int,string>  $issues
     */
    private function validateTitle(array $metaData, array &$issues): void
    {
        if (($metaData['title'] ?? '') === '') {
            $issues[] = 'Title is missing';

            return;
        }

        $titleLength = mb_strlen($this->safeCastToString($metaData['title']));

        if ($titleLength < 30) {
            $issues[] = 'Title is too short (minimum 30 characters)';
        } elseif ($titleLength > 60) {
            $issues[] = 'Title is too long (maximum 60 characters)';
        }
    }

    /**
     * Validate description field.
     *
     * @param  array<string,?string>  $metaData
     * @param  array<int,string>  $issues
     */
    private function validateDescription(array $metaData, array &$issues): void
    {
        if (($metaData['description'] ?? '') === '') {
            $issues[] = 'Description is missing';

            return;
        }

        $descriptionLength = mb_strlen($this->safeCastToString($metaData['description']));

        if ($descriptionLength < 70) {
            $issues[] = 'Description is too short (minimum 70 characters)';
        } elseif ($descriptionLength > 160) {
            $issues[] = 'Description is too long (maximum 160 characters)';
        }
    }

    /**
     * Validate keywords field.
     *
     * @param  array<string,?string>  $metaData
     * @param  array<int,string>  $issues
     */
    private function validateKeywords(array $metaData, array &$issues): void
    {
        if (($metaData['keywords'] ?? '') === '') {
            $issues[] = 'Keywords are missing';
        }
    }

    /**
     * Validate canonical URL field.
     *
     * @param  array<string,?string>  $metaData
     * @param  array<int,string>  $issues
     */
    private function validateCanonical(array $metaData, array &$issues): void
    {
        if (($metaData['canonical'] ?? '') === '') {
            $issues[] = 'Canonical URL is missing';
        }
    }

    /**
     * Add model name to keywords.
     *
     * @param  array<int,string>  $keywords
     */
    private function addModelNameToKeywords(Model $model, array &$keywords): void
    {
        if (isset($model->name)) {
            $keywords[] = $this->safeCastToString($model->name);
        }
    }

    /**
     * Add product-specific keywords if model is a Product.
     *
     * @param  array<int,string>  $keywords
     */
    private function addProductSpecificKeywords(Model $model, array &$keywords): void
    {
        if (! $model instanceof Product) {
            return;
        }

        $this->addCategoryKeyword($model, $keywords);
        $this->addBrandKeyword($model, $keywords);
    }

    /**
     * Add category keyword if available.
     *
     * @param  array<int,string>  $keywords
     */
    private function addCategoryKeyword(Product $product, array &$keywords): void
    {
        if ($product->category && ($product->category->name ?? '') !== '') {
            $keywords[] = $this->safeCastToString($product->category->name);
        }
    }

    /**
     * Add brand keyword if available.
     *
     * @param  array<int,string>  $keywords
     */
    private function addBrandKeyword(Product $product, array &$keywords): void
    {
        if ($product->brand && ($product->brand->name ?? '') !== '') {
            $keywords[] = $this->safeCastToString($product->brand->name);
        }
    }

    /**
     * Add generic keywords.
     *
     * @param  array<int,string>  $keywords
     */
    private function addGenericKeywords(array &$keywords): void
    {
        $keywords[] = 'price comparison';
        $keywords[] = 'best price';
        $keywords[] = 'deals';
    }

    /**
     * Format and clean keywords array.
     *
     * @param  array<int,string>  $keywords
     */
    private function formatKeywords(array $keywords): string
    {
        $keywords = array_unique($keywords);

        return implode(', ', $keywords);
    }

    /**
     * Generate basic product data.
     *
     * @return string[]
     *
     * @psalm-return array{'@context': 'https://schema.org/', '@type': 'Product', name: string, description: string, image: string, url: string}
     */
    private function generateBasicProductData(Product $product): array
    {
        $productDescription = $this->getProductDescription($product);
        $imageUrl = $this->getProductImageUrl($product);

        return [
            '@context' => 'https://schema.org/',
            '@type' => 'Product',
            'name' => $this->safeCastToString($product->name),
            'description' => $this->safeCastToString($productDescription),
            'image' => $this->safeCastToString($imageUrl),
            'url' => $this->safeCastToString(
                $this->urlGenerator->route('products.show', $product->slug)
            ),
        ];
    }

    /**
     * Get product description with fallback.
     */
    private function getProductDescription(Product $product): string
    {
        return $product->description && $product->description !== ''
            ? $product->description
            : $product->name;
    }

    /**
     * Get product image URL with fallback.
     */
    private function getProductImageUrl(Product $product): string
    {
        return $product->image_url && $product->image_url !== ''
            ? $product->image_url
            : $this->urlGenerator->asset('images/default-product.png');
    }

    /**
     * Add offers data if product has price.
     */
    private function addProductOffersData(Product $product, array $data): array
    {
        if ($product->price) {
            $data['offers'] = $this->generateProductOffers($product);
        }

        return $data;
    }

    /**
     * Add rating data if product has rating.
     */
    private function addProductRatingData(Product $product, array $data): array
    {
        if (isset($product->rating)) {
            $data['aggregateRating'] = $this->generateProductRating($product);
        }

        return $data;
    }

    /**
     * @return (float|string)[]
     *
     * @psalm-return array{'@type': 'AggregateOffer', lowPrice: float|string, priceCurrency: string, availability: 'https://schema.org/InStock'}
     */
    private function generateProductOffers(Product $product): array
    {
        $currency = $this->getProductCurrency($product);

        return [
            '@type' => 'AggregateOffer',
            'lowPrice' => $product->price ?? 0.0,
            'priceCurrency' => $currency,
            'availability' => 'https://schema.org/InStock',
        ];
    }

    /**
     * Get product currency with fallback.
     */
    private function getProductCurrency(Product $product): string
    {
        if ($product->store->currency && $product->store->currency !== '') {
            return $product->store->currency;
        }

        return 'USD';
    }

    /**
     * @return array<string, string|int|float>
     */
    private function generateProductRating(Product $product): array
    {
        $reviewCount = $this->getReviewCount($product);

        return [
            '@type' => 'AggregateRating',
            'ratingValue' => $product->rating ?? 0,
            'reviewCount' => $reviewCount,
        ];
    }

    /**
     * Get review count with fallback.
     */
    private function getReviewCount(Product $product): int
    {
        if ($product->reviews_count && $product->reviews_count !== '') {
            return (int) $product->reviews_count;
        }

        return 0;
    }

    /**
     * Safely cast a value to a string.
     */
    private function safeCastToString(string|int|float|object|null $value): string
    {
        if (is_string($value) || is_numeric($value)) {
            return (string) $value;
        }

        if (is_object($value) && method_exists($value, '__toString')) {
            return (string) $value;
        }

        return '';
    }
}

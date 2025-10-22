<?php

declare(strict_types=1);

namespace App\View\Composers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Language;

final class AppComposer
{
    /**
     * Bind data to the view.
     */
    public function compose(\Illuminate\View\View $view): void
    {
        $view->with('languages', $this->getLanguages());
        $view->with('categories', $this->getCategories());
        $view->with('brands', $this->getBrands());
        $view->with('isRTL', $this->isRTL());
    }

    /**
     * Get active languages.
     *
     * @return array<array<array<int|string>>>
     *
     * @psalm-return list<array<string, array<string, int|string>>>
     */
    private function getLanguages(): array
    {
        $mapper = /**
         * @return array<bool|string>
         *
         * @psalm-return array{code: string, name: string, native_name: string, direction: string, is_current: bool}
         */
        static fn (Language $language): array => [
            'code' => $language->code ?? '',
            'name' => $language->name ?? '',
            'native_name' => $language->native_name ?? '',
            'direction' => $language->direction ?? 'ltr',
            'is_current' => app()->getLocale() === $language->code,
        ];

        return $this->getCachedData('languages', Language::class, $mapper, 'sort_order', 3600);
    }

    /**
     * Get active categories.
     *
     * @return array<array<array<int|string>>>
     *
     * @psalm-return list<array<string, array<string, int|string>>>
     */
    private function getCategories(): array
    {
        $mapper = /**
         * @return array<int|string>
         *
         * @psalm-return array{id: int, name: string, slug: string, url: string}
         */
        static fn (Category $category): array => [
            'id' => (int) $category->id,
            'name' => $category->name ?? '',
            'slug' => $category->slug ?? '',
            'url' => $category->slug ? route('categories.show', $category->slug) : '',
        ];

        return $this->getCachedData('categories_menu', Category::class, $mapper);
    }

    /**
     * Get active brands.
     *
     * @return array<array<array<int|string>>>
     *
     * @psalm-return list<array<string, array<string, int|string>>>
     */
    private function getBrands(): array
    {
        $mapper = /**
         * @return array<int|string|null>
         *
         * @psalm-return array{id: int, name: string, slug: string, logo: string|null, url: string}
         */
        static fn (Brand $brand): array => [
            'id' => (int) $brand->id,
            'name' => $brand->name ?? '',
            'slug' => $brand->slug ?? '',
            'logo' => $brand->logo_url,
            'url' => $brand->slug ? route('brands.show', $brand->slug) : '',
        ];

        return $this->getCachedData('brands_menu', Brand::class, $mapper);
    }

    /**
     * Check if current locale is RTL.
     */
    private function isRTL(): bool
    {
        $rtlLocales = ['ar', 'ur', 'fa', 'he'];

        return in_array(app()->getLocale(), $rtlLocales, true);
    }

    /**
     * @param  class-string  $modelClass
     * @return list<array<string, array<string, int|string>>>
     */
    private function getCachedData(
        string $key,
        string $modelClass,
        callable $mapper,
        string $orderBy = 'name',
        int $ttl = 1800
    ): array {
        /** @var list<array<string, array<string, int|string>>> $result */
        $result = cache()->remember(
            $key,
            $ttl,
            /**
             * @psalm-return array<int, mixed>
             */
            static function () use ($modelClass, $orderBy, $mapper): array {
                /** @var \Illuminate\Database\Eloquent\Builder<
                 *     \Illuminate\Database\Eloquent\Model
                 * > $query */
                $query = $modelClass::where('is_active', true);

                $collection = $query->orderBy($orderBy)->get();

                /** @var \Illuminate\Support\Collection<int, array<string, array>> $mapped */
                $mapped = $collection->map($mapper);

                return $mapped->values()->toArray();
            }
        );

        return is_array($result) ? array_values($result) : [];
    }
}

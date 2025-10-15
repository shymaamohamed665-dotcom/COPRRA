<?php

declare(strict_types=1);

namespace App\Services\SEO;

use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use App\Services\SEOService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

final class SEOAuditor
{
    private SEOService $seoService;

    public function __construct(SEOService $seoService)
    {
        $this->seoService = $seoService;
    }

    /**
     * Get the model map for auditing.
     *
     * @return string[]
     *
     * @psalm-return array{'App\\Models\\Product'::class: 'Products', 'App\\Models\\Category'::class: 'Categories', 'App\\Models\\Store'::class: 'Stores'}
     */
    public function getModelMap(): array
    {
        return [
            Product::class => 'Products',
            Category::class => 'Categories',
            Store::class => 'Stores',
        ];
    }

    /**
     * Audit all instances of a given model.
     *
     * @return Collection<int, SEOAuditResult>
     */
    public function auditModels(string $modelClass): Collection
    {
        return $modelClass::all()->map(function (Model $model) {
            return $this->auditModel($model);
        });
    }

    /**
     * Audit a single model.
     */
    public function auditModel(Model $model): SEOAuditResult
    {
        $metaData = $this->getModelMetaData($model);
        $issues = $this->validateModelMetaData($metaData);

        return new SEOAuditResult(
            model: $model,
            metaData: $metaData,
            issues: $issues
        );
    }

    /**
     * Get metadata for a model.
     *
     * @return array<string, string>
     */
    private function getModelMetaData(Model $model): array
    {
        return $this->seoService->generateMetaData($model, $model::class);
    }

    /**
     * Validate metadata for SEO issues.
     *
     * @param  array<string, string>  $metaData
     * @return string[]
     *
     * @psalm-return array<int, string>
     */
    private function validateModelMetaData(array $metaData): array
    {
        return $this->seoService->validateMetaData($metaData);
    }
}

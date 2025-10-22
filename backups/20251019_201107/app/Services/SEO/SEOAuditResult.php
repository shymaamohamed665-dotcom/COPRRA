<?php

declare(strict_types=1);

namespace App\Services\SEO;

use Illuminate\Database\Eloquent\Model;

final readonly class SEOAuditResult
{
    /**
     * @param  array<string, mixed>  $metaData
     * @param  array<int, array<string, mixed>>  $issues
     */
    public function __construct(
        private Model $model,
        private array $metaData,
        private array $issues
    ) {}

    public function getModel(): Model
    {
        return $this->model;
    }

    /**
     * @return array<string, mixed>
     */
    public function getMetaData(): array
    {
        return $this->metaData;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getIssues(): array
    {
        return $this->issues;
    }

    public function hasIssues(): bool
    {
        return $this->issues !== [];
    }

    /**
     * @psalm-return int<0, max>
     */
    public function getIssueCount(): int
    {
        return count($this->issues);
    }

    public function getModelType(): string
    {
        $className = $this->model::class;

        return match ($className) {
            'App\Models\Product' => 'Products',
            'App\Models\Category' => 'Categories',
            'App\Models\Store' => 'Stores',
            default => class_basename($className),
        };
    }

    public function getModelId(): string
    {
        return (string) $this->model->getKey();
    }

    public function getModelName(): string
    {
        return $this->model->name ?? 'unknown';
    }
}

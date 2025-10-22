<?php

declare(strict_types=1);

namespace App\Services\SEO;

use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Database\Eloquent\Model;

final class SEOIssueFixer
{
    /**
     * Get the mapping of model fields to metadata keys.
     *
     * @return array<string>
     *
     * @psalm-return array{meta_title: 'title', meta_description: 'description', meta_keywords: 'keywords'}
     */
    public function getMetaFieldsMap(): array
    {
        return [
            'meta_title' => 'title',
            'meta_description' => 'description',
            'meta_keywords' => 'keywords',
        ];
    }

    /**
     * Fix model issues.
     *
     * @param  array<string, string|null>  $metaData
     */
    public function fixModelIssues(Model $model, array $metaData): bool
    {
        $metaFields = $this->getMetaFieldsMap();
        $fixed = $this->fixMetaFields($model, $metaFields, $metaData);

        if ($fixed) {
            $model->save();
        }

        return $fixed;
    }

    /**
     * Get model class from type string.
     *
     * @psalm-return Category::class|Product::class|Store::class|null
     */
    public function getModelClassFromType(string $type): ?string
    {
        $modelMap = [
            'product' => Product::class,
            'category' => Category::class,
            'store' => Store::class,
        ];

        return $modelMap[strtolower($type)] ?? null;
    }

    /**
     * Fix multiple meta fields on a model.
     *
     * @param  array<string, string>  $metaFields  Map of model field names to metaData keys
     * @param  array<string, string|null>  $metaData
     *
     * @return bool Whether any fields were fixed
     */
    private function fixMetaFields(Model $model, array $metaFields, array $metaData): bool
    {
        $fixed = false;

        foreach ($metaFields as $field => $metaType) {
            if ($this->shouldFixField($model, $field, $metaData, $metaType)) {
                $model->{$field} = $metaData[$metaType];
                $fixed = true;
            }
        }

        return $fixed;
    }

    /**
     * Determine if a field should be fixed.
     *
     * @param  array<string, string|null>  $metaData
     */
    private function shouldFixField(Model $model, string $field, array $metaData, string $metaType): bool
    {
        // Check if model has the column in its fillable or table
        if (! $this->modelHasField($model, $field)) {
            return false;
        }

        return ($model->{$field} === null || $model->{$field} === '')
            && isset($metaData[$metaType])
            && $metaData[$metaType] !== '';
    }

    /**
     * Check if model has a specific field.
     */
    private function modelHasField(Model $model, string $field): bool
    {
        // Check if field is in fillable array or if it exists in the table
        try {
            $columns = \Schema::getColumnListing($model->getTable());

            return in_array($field, $columns, true);
        } catch (\Throwable $e) {
            // If we can't check the schema, try to access the attribute
            try {
                $model->getAttribute($field);

                return true;
            } catch (\Throwable $attributeException) {
                return false;
            }
        }
    }
}

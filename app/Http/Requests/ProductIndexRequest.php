<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductIndexRequest extends FormRequest
{
    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'per_page.integer' => 'Items per page must be a number.',
            'per_page.min' => 'Items per page must be at least 1.',
            'per_page.max' => 'Items per page cannot exceed 100.',
            'search.max' => 'Search query cannot exceed 255 characters.',
            'category_id.exists' => 'Selected category does not exist.',
            'brand_id.exists' => 'Selected brand does not exist.',
            'min_price.min' => 'Minimum price must be 0 or greater.',
            'max_price.min' => 'Maximum price must be 0 or greater.',
            'max_price.gte' => 'Maximum price must be greater than or equal to minimum price.',
            'sort.in' => 'Invalid sort option.',
        ];
    }
}

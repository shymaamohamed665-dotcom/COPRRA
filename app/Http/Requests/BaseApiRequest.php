<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class BaseApiRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Override in child classes
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<string|int>|string>
     */
    public function rules(): array
    {
        return [];
    }

    /**
     * Handle a failed validation attempt.
     */
    #[\Override]
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
                'error_code' => 'VALIDATION_ERROR',
            ], 422)
        );
    }

    /**
     * Get common validation rules for pagination.
     *
     * @return array<string, string>
     */
    protected function paginationRules(): array
    {
        return [
            'page' => 'sometimes|integer|min:1',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ];
    }

    /**
     * Get common validation rules for search.
     *
     * @return array<string, string>
     */
    protected function searchRules(): array
    {
        return [
            'search' => 'sometimes|string|max:255',
            'q' => 'sometimes|string|max:255',
            'query' => 'sometimes|string|max:255',
        ];
    }

    /**
     * Get common validation rules for sorting.
     *
     * @return array<string, string>
     */
    protected function sortingRules(): array
    {
        return [
            'sort' => 'sometimes|string|in:name,price,created_at,updated_at',
            'order' => 'sometimes|string|in:asc,desc',
        ];
    }

    /**
     * Get common validation rules for filtering.
     *
     * @return array<string, string>
     */
    protected function filteringRules(): array
    {
        return [
            'category_id' => 'sometimes|integer|exists:categories,id',
            'brand_id' => 'sometimes|integer|exists:brands,id',
            'min_price' => 'sometimes|numeric|min:0',
            'max_price' => 'sometimes|numeric|min:0|gte:min_price',
            'is_active' => 'sometimes|boolean',
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCartRequest extends FormRequest
{
    /**
     * Authorize the request.
     *
     * @return true
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return string[][]
     *
     * @psalm-return array{id: list{'required'}, quantity: list{'required', 'integer', 'min:1', 'max:999'}}
     */
    public function rules(): array
    {
        return [
            // Cart item ID may be numeric or string depending on package usage
            'id' => ['required'],
            'quantity' => ['required', 'integer', 'min:1', 'max:999'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return string[]
     *
     * @psalm-return array{'id.required': 'Cart item ID is required.', 'quantity.required': 'Quantity is required.', 'quantity.integer': 'Quantity must be a number.', 'quantity.min': 'Quantity must be at least 1.', 'quantity.max': 'Quantity cannot exceed 999.'}
     */
    #[\Override]
    public function messages(): array
    {
        return [
            'id.required' => 'Cart item ID is required.',
            'quantity.required' => 'Quantity is required.',
            'quantity.integer' => 'Quantity must be a number.',
            'quantity.min' => 'Quantity must be at least 1.',
            'quantity.max' => 'Quantity cannot exceed 999.',
        ];
    }
}

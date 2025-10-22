<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<array<string>>
     *
     * @psalm-return array{name: list{'required', 'string', 'max:255'}, slug: list{'required', 'string', 'max:255', string}, description: list{'required', 'string'}, price: list{'required', 'numeric', 'min:0'}, image: list{'nullable', 'image', 'max:2048'}, category_id: list{'required', 'exists:categories,id'}, brand_id: list{'required', 'exists:brands,id'}, store_id: list{'required', 'exists:stores,id'}, is_active: list{'boolean'}}
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:products,slug,'.$this->route('product')],
            'description' => ['required', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'image' => ['nullable', 'image', 'max:2048'], // 2MB max
            'category_id' => ['required', 'exists:categories,id'],
            'brand_id' => ['required', 'exists:brands,id'],
            'store_id' => ['required', 'exists:stores,id'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string>
     *
     * @psalm-return array{'name.required': 'Product name is required', 'name.max': 'Product name cannot exceed 255 characters', 'slug.unique': 'This URL slug is already in use', 'price.min': 'Price must be greater than or equal to 0', 'image.image': 'The file must be an image', 'image.max': 'Image size cannot exceed 2MB', 'category_id.exists': 'Selected category does not exist', 'brand_id.exists': 'Selected brand does not exist', 'store_id.exists': 'Selected store does not exist'}
     */
    #[\Override]
    public function messages(): array
    {
        return [
            'name.required' => 'Product name is required',
            'name.max' => 'Product name cannot exceed 255 characters',
            'slug.unique' => 'This URL slug is already in use',
            'price.min' => 'Price must be greater than or equal to 0',
            'image.image' => 'The file must be an image',
            'image.max' => 'Image size cannot exceed 2MB',
            'category_id.exists' => 'Selected category does not exist',
            'brand_id.exists' => 'Selected brand does not exist',
            'store_id.exists' => 'Selected store does not exist',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    #[\Override]
    protected function prepareForValidation(): void
    {
        if ($this->has('name') && ! $this->has('slug')) {
            $this->merge([
                'slug' => str(is_string($this->name) ? $this->name : '')->slug()->toString(),
            ]);
        }
    }
}

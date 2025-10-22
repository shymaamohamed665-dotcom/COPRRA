<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Rules\DimensionSum;
use Illuminate\Foundation\Http\FormRequest;

class ProductCreateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\Product::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<array<array|string>>
     *
     * @psalm-return array<string, array<int, array|string>>
     */
    public function rules(): array
    {
        return array_merge(
            $this->getNameRules(),
            $this->getDescriptionRules(),
            $this->getPriceRules(),
            $this->getCategoryIdRules(),
            $this->getBrandIdRules(),
            $this->getSkuRules(),
            $this->getStockQuantityRules(),
            $this->getWeightRules(),
            $this->getDimensionsRules(),
            $this->getImagesRules(),
            $this->getTagsRules(),
            $this->getStatusRules()
        );
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string>
     *
     * @psalm-return array{'name.required': 'اسم المنتج مطلوب', 'name.min': 'اسم المنتج يجب أن يكون على الأقل 3 أحرف', 'name.max': 'اسم المنتج لا يمكن أن يتجاوز 255 حرف', 'description.required': 'وصف المنتج مطلوب', 'description.min': 'وصف المنتج يجب أن يكون على الأقل 10 أحرف', 'description.max': 'وصف المنتج لا يمكن أن يتجاوز 5000 حرف', 'price.required': 'سعر المنتج مطلوب', 'price.numeric': 'سعر المنتج يجب أن يكون رقماً', 'price.min': 'سعر المنتج يجب أن يكون أكبر من 0', 'price.max': 'سعر المنتج لا يمكن أن يتجاوز 999999.99', 'category_id.required': 'فئة المنتج مطلوبة', 'category_id.exists': 'فئة المنتج المحددة غير موجودة', 'brand_id.required': 'علامة المنتج التجارية مطلوبة', 'brand_id.exists': 'علامة المنتج التجارية المحددة غير موجودة', 'sku.required': 'رمز المنتج (SKU) مطلوب', 'sku.unique': 'رمز المنتج (SKU) مستخدم بالفعل', 'stock_quantity.required': 'كمية المخزون مطلوبة', 'stock_quantity.integer': 'كمية المخزون يجب أن تكون رقماً صحيحاً', 'stock_quantity.min': 'كمية المخزون لا يمكن أن تكون سالبة', 'images.max': 'يمكن رفع 10 صور كحد أقصى', 'images.*.image': 'الملف يجب أن يكون صورة', 'images.*.mimes': 'نوع الصورة يجب أن يكون: jpeg, png, jpg, gif, webp', 'images.*.max': 'حجم الصورة لا يمكن أن يتجاوز 5 ميجابايت', 'tags.max': 'يمكن إضافة 20 علامة كحد أقصى', 'tags.*.max': 'العلامة لا يمكن أن تتجاوز 50 حرف'}
     */
    #[\Override]
    public function messages(): array
    {
        return [
            'name.required' => 'اسم المنتج مطلوب',
            'name.min' => 'اسم المنتج يجب أن يكون على الأقل 3 أحرف',
            'name.max' => 'اسم المنتج لا يمكن أن يتجاوز 255 حرف',
            'description.required' => 'وصف المنتج مطلوب',
            'description.min' => 'وصف المنتج يجب أن يكون على الأقل 10 أحرف',
            'description.max' => 'وصف المنتج لا يمكن أن يتجاوز 5000 حرف',
            'price.required' => 'سعر المنتج مطلوب',
            'price.numeric' => 'سعر المنتج يجب أن يكون رقماً',
            'price.min' => 'سعر المنتج يجب أن يكون أكبر من 0',
            'price.max' => 'سعر المنتج لا يمكن أن يتجاوز 999999.99',
            'category_id.required' => 'فئة المنتج مطلوبة',
            'category_id.exists' => 'فئة المنتج المحددة غير موجودة',
            'brand_id.required' => 'علامة المنتج التجارية مطلوبة',
            'brand_id.exists' => 'علامة المنتج التجارية المحددة غير موجودة',
            'sku.required' => 'رمز المنتج (SKU) مطلوب',
            'sku.unique' => 'رمز المنتج (SKU) مستخدم بالفعل',
            'stock_quantity.required' => 'كمية المخزون مطلوبة',
            'stock_quantity.integer' => 'كمية المخزون يجب أن تكون رقماً صحيحاً',
            'stock_quantity.min' => 'كمية المخزون لا يمكن أن تكون سالبة',
            'images.max' => 'يمكن رفع 10 صور كحد أقصى',
            'images.*.image' => 'الملف يجب أن يكون صورة',
            'images.*.mimes' => 'نوع الصورة يجب أن يكون: jpeg, png, jpg, gif, webp',
            'images.*.max' => 'حجم الصورة لا يمكن أن يتجاوز 5 ميجابايت',
            'tags.max' => 'يمكن إضافة 20 علامة كحد أقصى',
            'tags.*.max' => 'العلامة لا يمكن أن تتجاوز 50 حرف',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string>
     *
     * @psalm-return array{name: 'اسم المنتج', description: 'وصف المنتج', price: 'سعر المنتج', category_id: 'فئة المنتج', brand_id: 'علامة المنتج التجارية', sku: 'رمز المنتج', stock_quantity: 'كمية المخزون', weight: 'الوزن', dimensions: 'الأبعاد', images: 'الصور', tags: 'العلامات', is_active: 'حالة النشاط', is_featured: 'المنتج المميز'}
     */
    #[\Override]
    public function attributes(): array
    {
        return [
            'name' => 'اسم المنتج',
            'description' => 'وصف المنتج',
            'price' => 'سعر المنتج',
            'category_id' => 'فئة المنتج',
            'brand_id' => 'علامة المنتج التجارية',
            'sku' => 'رمز المنتج',
            'stock_quantity' => 'كمية المخزون',
            'weight' => 'الوزن',
            'dimensions' => 'الأبعاد',
            'images' => 'الصور',
            'tags' => 'العلامات',
            'is_active' => 'حالة النشاط',
            'is_featured' => 'المنتج المميز',
        ];
    }

    /**
     * Get the validated data from the request.
     *
     * @return array<string, mixed>
     */
    #[\Override]
    public function validated(mixed $key = null, mixed $default = null): array
    {
        $validated = parent::validated($key, $default);

        // Add computed fields
        if (is_array($validated)) {
            $validated['slug'] = str(is_string($validated['name'] ?? '') ? ($validated['name'] ?? '') : '')
                ->slug()
                ->toString();
            $validated['created_by'] = $this->user()?->id;
        }

        return $validated;
    }

    /**
     * Prepare the data for validation.
     */
    #[\Override]
    protected function prepareForValidation(): void
    {
        // Clean and format data before validation
        $this->merge([
            'name' => is_string($this->name) ? trim($this->name) : '',
            'description' => is_string($this->description) ? trim($this->description) : '',
            'sku' => is_string($this->sku) ? strtoupper(trim($this->sku)) : '',
            'tags' => is_array($this->tags) ? array_map(static fn ($tag): string => is_string($tag) ? trim($tag) : '', $this->tags) : null,
        ]);
    }

    /**
     * @return array<array<string>>
     *
     * @psalm-return array{name: list{'required', 'string', 'max:255', 'min:3'}}
     */
    private function getNameRules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                'min:3',
            ],
        ];
    }

    /**
     * Get validation rules for status fields.
     *
     * @return array<array<string>>
     *
     * @psalm-return array{is_active: list{'boolean'}, is_featured: list{'boolean'}}
     */
    private function getStatusRules(): array
    {
        return [
            'is_active' => [
                'boolean',
            ],
            'is_featured' => [
                'boolean',
            ],
        ];
    }

    /**
     * Get validation rules for tags.
     *
     * @return array<array<string>>
     *
     * @psalm-return array{tags: list{'nullable', 'array', 'max:20'}, 'tags.*': list{'string', 'max:50'}}
     */
    private function getTagsRules(): array
    {
        return [
            'tags' => [
                'nullable',
                'array',
                'max:20',
            ],
            'tags.*' => [
                'string',
                'max:50',
            ],
        ];
    }

    /**
     * Get validation rules for images.
     *
     * @return array<array<string>>
     *
     * @psalm-return array{images: list{'nullable', 'array', 'max:10'}, 'images.*': list{'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:5120'}}
     */
    private function getImagesRules(): array
    {
        return [
            'images' => [
                'nullable',
                'array',
                'max:10',
            ],
            'images.*' => [
                'image',
                'mimes:jpeg,png,jpg,gif,webp',
                'max:5120', // 5MB
            ],
        ];
    }

    /**
     * Get validation rules for dimensions.
     *
     * @return array<array<DimensionSum|string>>
     *
     * @psalm-return array{dimensions: list{'nullable', 'array', 'size:3', DimensionSum}, 'dimensions.length': list{'nullable', 'numeric', 'min:0', 'max:999.99'}, 'dimensions.width': list{'nullable', 'numeric', 'min:0', 'max:999.99'}, 'dimensions.height': list{'nullable', 'numeric', 'min:0', 'max:999.99'}}
     */
    private function getDimensionsRules(): array
    {
        return [
            'dimensions' => [
                'nullable',
                'array',
                'size:3',
                new DimensionSum(2000),
            ],
            'dimensions.length' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999.99',
            ],
            'dimensions.width' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999.99',
            ],
            'dimensions.height' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999.99',
            ],
        ];
    }

    /**
     * Get validation rules for weight.
     *
     * @return array<array<string>>
     *
     * @psalm-return array{weight: list{'nullable', 'numeric', 'min:0', 'max:999.99'}}
     */
    private function getWeightRules(): array
    {
        return [
            'weight' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999.99',
            ],
        ];
    }

    /**
     * Get validation rules for stock quantity.
     *
     * @return array<array<string>>
     *
     * @psalm-return array{stock_quantity: list{'required', 'integer', 'min:0', 'max:999999'}}
     */
    private function getStockQuantityRules(): array
    {
        return [
            'stock_quantity' => [
                'required',
                'integer',
                'min:0',
                'max:999999',
            ],
        ];
    }

    /**
     * Get validation rules for SKU.
     *
     * @return array<array<string>>
     *
     * @psalm-return array{sku: list{'required', 'string', 'max:100', 'unique:products,sku'}}
     */
    private function getSkuRules(): array
    {
        return [
            'sku' => [
                'required',
                'string',
                'max:100',
                'unique:products,sku',
            ],
        ];
    }

    /**
     * Get validation rules for brand ID.
     *
     * @return array<array<string>>
     *
     * @psalm-return array{brand_id: list{'required', 'integer', 'exists:brands,id'}}
     */
    private function getBrandIdRules(): array
    {
        return [
            'brand_id' => [
                'required',
                'integer',
                'exists:brands,id',
            ],
        ];
    }

    /**
     * Get validation rules for category ID.
     *
     * @return array<array<string>>
     *
     * @psalm-return array{category_id: list{'required', 'integer', 'exists:categories,id'}}
     */
    private function getCategoryIdRules(): array
    {
        return [
            'category_id' => [
                'required',
                'integer',
                'exists:categories,id',
            ],
        ];
    }

    /**
     * Get validation rules for price.
     *
     * @return array<array<string>>
     *
     * @psalm-return array{price: list{'required', 'numeric', 'min:0.01', 'max:999999.99'}}
     */
    private function getPriceRules(): array
    {
        return [
            'price' => [
                'required',
                'numeric',
                'min:0.01',
                'max:999999.99',
            ],
        ];
    }

    /**
     * Get validation rules for description.
     *
     * @return array<array<string>>
     *
     * @psalm-return array{description: list{'required', 'string', 'max:5000', 'min:10'}}
     */
    private function getDescriptionRules(): array
    {
        return [
            'description' => [
                'required',
                'string',
                'max:5000',
                'min:10',
            ],
        ];
    }
}

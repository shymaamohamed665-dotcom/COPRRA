<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Rules\DimensionSum;
use App\Services\Validators\PriceChangeValidator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductUpdateRequest extends FormRequest
{
    private \App\Services\Validators\PriceChangeValidator $priceChangeValidator;

    public function __construct(PriceChangeValidator $priceChangeValidator)
    {
        parent::__construct();
        $this->priceChangeValidator = $priceChangeValidator;
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $product = $this->route('product');

        return $this->user()?->can('update', $product) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return (Rule|string)[][]
     *
     * @psalm-return array<string, array<Rule|string>>
     */
    public function rules(): array
    {
        $productId = $this->getProductIdForRules();

        return array_merge(
            $this->getBaseRules(),
            $this->getSkuRules($productId),
            $this->getDimensionRules(),
            $this->getImageRules(),
            $this->getTagRules()
        );
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return string[]
     *
     * @psalm-return array{'name.min': 'اسم المنتج يجب أن يكون على الأقل 3 أحرف', 'name.max': 'اسم المنتج لا يمكن أن يتجاوز 255 حرف', 'description.min': 'وصف المنتج يجب أن يكون على الأقل 10 أحرف', 'description.max': 'وصف المنتج لا يمكن أن يتجاوز 5000 حرف', 'price.numeric': 'سعر المنتج يجب أن يكون رقماً', 'price.min': 'سعر المنتج يجب أن يكون أكبر من 0', 'price.max': 'سعر المنتج لا يمكن أن يتجاوز 999999.99', 'category_id.exists': 'فئة المنتج المحددة غير موجودة', 'brand_id.exists': 'علامة المنتج التجارية المحددة غير موجودة', 'sku.unique': 'رمز المنتج (SKU) مستخدم بالفعل', 'stock_quantity.integer': 'كمية المخزون يجب أن تكون رقماً صحيحاً', 'stock_quantity.min': 'كمية المخزون لا يمكن أن تكون سالبة', 'images.max': 'يمكن رفع 10 صور كحد أقصى', 'images.*.image': 'الملف يجب أن يكون صورة', 'images.*.mimes': 'نوع الصورة يجب أن يكون: jpeg, png, jpg, gif, webp', 'images.*.max': 'حجم الصورة لا يمكن أن يتجاوز 5 ميجابايت', 'tags.max': 'يمكن إضافة 20 علامة كحد أقصى', 'tags.*.max': 'العلامة لا يمكن أن تتجاوز 50 حرف'}
     */
    #[\Override]
    public function messages(): array
    {
        return [
            'name.min' => 'اسم المنتج يجب أن يكون على الأقل 3 أحرف',
            'name.max' => 'اسم المنتج لا يمكن أن يتجاوز 255 حرف',
            'description.min' => 'وصف المنتج يجب أن يكون على الأقل 10 أحرف',
            'description.max' => 'وصف المنتج لا يمكن أن يتجاوز 5000 حرف',
            'price.numeric' => 'سعر المنتج يجب أن يكون رقماً',
            'price.min' => 'سعر المنتج يجب أن يكون أكبر من 0',
            'price.max' => 'سعر المنتج لا يمكن أن يتجاوز 999999.99',
            'category_id.exists' => 'فئة المنتج المحددة غير موجودة',
            'brand_id.exists' => 'علامة المنتج التجارية المحددة غير موجودة',
            'sku.unique' => 'رمز المنتج (SKU) مستخدم بالفعل',
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
     * @return string[]
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
     * Configure the validator instance.
     */
    public function withValidator(\Illuminate\Contracts\Validation\Validator $validator): void
    {
        $validator->after(function (): void {
            if ($this->has('price')) {
                $this->priceChangeValidator->validate($this->route('product'), $this->input('price'));
            }
        });
    }

    /**
     * Get the validated data from the request.
     */
    #[\Override]
    public function validated(mixed $key = null, mixed $default = null): mixed
    {
        $validated = parent::validated($key, $default);

        // Add computed fields
        if (is_array($validated) && isset($validated['name'])) {
            $name = $validated['name'];
            $validated['slug'] = Str::slug(is_string($name) ? $name : '');
        }

        if (is_array($validated)) {
            $validated['updated_by'] = $this->user()?->id;
        }

        return $validated;
    }

    /**
     * Prepare the data for validation.
     */
    #[\Override]
    protected function prepareForValidation(): void
    {
        $this->merge(array_filter([
            'name' => is_string($this->name) ? trim($this->name) : null,
            'description' => is_string($this->description) ? trim($this->description) : null,
            'sku' => is_string($this->sku) ? strtoupper(trim($this->sku)) : null,
            'tags' => is_array($this->tags) ? array_map(static fn ($tag) => is_string($tag) ? trim($tag) : '', $this->tags) : null,
        ]));
    }

    private function getProductIdForRules(): ?int
    {
        $product = $this->route('product');

        return $product instanceof \App\Models\Product ? $product->id : (is_numeric($product) ? (int) $product : null);
    }

    /**
     * Get base validation rules.
     *
     * @return string[][]
     *
     * @psalm-return array{name: list{'sometimes', 'string', 'max:255', 'min:3'}, description: list{'sometimes', 'string', 'max:5000', 'min:10'}, price: list{'sometimes', 'numeric', 'min:0.01', 'max:999999.99'}, category_id: list{'sometimes', 'integer', 'exists:categories,id'}, brand_id: list{'sometimes', 'integer', 'exists:brands,id'}, stock_quantity: list{'sometimes', 'integer', 'min:0', 'max:999999'}, weight: list{'nullable', 'numeric', 'min:0', 'max:999.99'}, is_active: list{'boolean'}, is_featured: list{'boolean'}}
     */
    private function getBaseRules(): array
    {
        /** @return array */
        return [
            'name' => [
                'sometimes',
                'string',
                'max:255',
                'min:3',
            ],
            'description' => [
                'sometimes',
                'string',
                'max:5000',
                'min:10',
            ],
            'price' => [
                'sometimes',
                'numeric',
                'min:0.01',
                'max:999999.99',
            ],
            'category_id' => [
                'sometimes',
                'integer',
                'exists:categories,id',
            ],
            'brand_id' => [
                'sometimes',
                'integer',
                'exists:brands,id',
            ],
            'stock_quantity' => [
                'sometimes',
                'integer',
                'min:0',
                'max:999999',
            ],
            'weight' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999.99',
            ],
            'is_active' => [
                'boolean',
            ],
            'is_featured' => [
                'boolean',
            ],
        ];
    }

    /**
     * Get SKU validation rules.
     *
     * @return (\Illuminate\Validation\Rules\Unique|string)[][]
     *
     * @psalm-return array{sku: list{'sometimes', 'string', 'max:100', \Illuminate\Validation\Rules\Unique}}
     */
    private function getSkuRules(?int $productId): array
    {
        /** @return array */
        return [
            'sku' => [
                'sometimes',
                'string',
                'max:100',
                Rule::unique('products', 'sku')->ignore($productId),
            ],
        ];
    }

    /**
     * Get dimension validation rules.
     *
     * @return (DimensionSum|string)[][]
     *
     * @psalm-return array{dimensions: list{'nullable', 'array', 'size:3', DimensionSum}, 'dimensions.length': list{'nullable', 'numeric', 'min:0', 'max:999.99'}, 'dimensions.width': list{'nullable', 'numeric', 'min:0', 'max:999.99'}, 'dimensions.height': list{'nullable', 'numeric', 'min:0', 'max:999.99'}}
     */
    private function getDimensionRules(): array
    {
        /** @return array */
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
     * Get image validation rules.
     *
     * @return string[][]
     *
     * @psalm-return array{images: list{'nullable', 'array', 'max:10'}, 'images.*': list{'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:5120'}}
     */
    private function getImageRules(): array
    {
        /** @return array */
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
     * Get tag validation rules.
     *
     * @return string[][]
     *
     * @psalm-return array{tags: list{'nullable', 'array', 'max:20'}, 'tags.*': list{'string', 'max:50'}}
     */
    private function getTagRules(): array
    {
        /** @return array */
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
}

<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductSearchRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Search is public
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<array<string>>
     *
     * @psalm-return array{q: list{'required', 'string', 'min:2', 'max:255'}, category_id: list{'nullable', 'integer', 'exists:categories,id'}, brand_id: list{'nullable', 'integer', 'exists:brands,id'}, min_price: list{'nullable', 'numeric', 'min:0', 'max:999999.99'}, max_price: list{'nullable', 'numeric', 'min:0', 'max:999999.99', 'gte:min_price'}, sort: list{'nullable', 'string', 'in:name,price,created_at,updated_at,popularity'}, order: list{'nullable', 'string', 'in:asc,desc'}, page: list{'nullable', 'integer', 'min:1', 'max:1000'}, per_page: list{'nullable', 'integer', 'min:1', 'max:100'}, tags: list{'nullable', 'array', 'max:10'}, 'tags.*': list{'string', 'max:50'}, in_stock: list{'nullable', 'boolean'}, featured: list{'nullable', 'boolean'}}
     */
    public function rules(): array
    {
        return array_merge(
            $this->getQueryRules(),
            $this->getCategoryIdRules(),
            $this->getBrandIdRules(),
            $this->getPriceRules(),
            $this->getSortingRules(),
            $this->getPaginationRules(),
            $this->getTagsRules(),
            $this->getStatusRules()
        );
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string>
     *
     * @psalm-return array{'q.required': 'كلمة البحث مطلوبة', 'q.min': 'كلمة البحث يجب أن تكون على الأقل حرفين', 'q.max': 'كلمة البحث لا يمكن أن تتجاوز 255 حرف', 'category_id.exists': 'فئة المنتج المحددة غير موجودة', 'brand_id.exists': 'علامة المنتج التجارية المحددة غير موجودة', 'min_price.numeric': 'الحد الأدنى للسعر يجب أن يكون رقماً', 'min_price.min': 'الحد الأدنى للسعر لا يمكن أن يكون سالباً', 'max_price.numeric': 'الحد الأقصى للسعر يجب أن يكون رقماً', 'max_price.min': 'الحد الأقصى للسعر لا يمكن أن يكون سالباً', 'max_price.gte': 'الحد الأقصى للسعر يجب أن يكون أكبر من أو يساوي الحد الأدنى', 'sort.in': 'نوع الترتيب غير صحيح', 'order.in': 'اتجاه الترتيب غير صحيح', 'page.min': 'رقم الصفحة يجب أن يكون أكبر من 0', 'page.max': 'رقم الصفحة لا يمكن أن يتجاوز 1000', 'per_page.min': 'عدد العناصر في الصفحة يجب أن يكون أكبر من 0', 'per_page.max': 'عدد العناصر في الصفحة لا يمكن أن يتجاوز 100', 'tags.max': 'يمكن البحث بـ 10 علامات كحد أقصى', 'tags.*.max': 'العلامة لا يمكن أن تتجاوز 50 حرف'}
     */
    #[\Override]
    public function messages(): array
    {
        return [
            'q.required' => 'كلمة البحث مطلوبة',
            'q.min' => 'كلمة البحث يجب أن تكون على الأقل حرفين',
            'q.max' => 'كلمة البحث لا يمكن أن تتجاوز 255 حرف',
            'category_id.exists' => 'فئة المنتج المحددة غير موجودة',
            'brand_id.exists' => 'علامة المنتج التجارية المحددة غير موجودة',
            'min_price.numeric' => 'الحد الأدنى للسعر يجب أن يكون رقماً',
            'min_price.min' => 'الحد الأدنى للسعر لا يمكن أن يكون سالباً',
            'max_price.numeric' => 'الحد الأقصى للسعر يجب أن يكون رقماً',
            'max_price.min' => 'الحد الأقصى للسعر لا يمكن أن يكون سالباً',
            'max_price.gte' => 'الحد الأقصى للسعر يجب أن يكون أكبر من أو يساوي الحد الأدنى',
            'sort.in' => 'نوع الترتيب غير صحيح',
            'order.in' => 'اتجاه الترتيب غير صحيح',
            'page.min' => 'رقم الصفحة يجب أن يكون أكبر من 0',
            'page.max' => 'رقم الصفحة لا يمكن أن يتجاوز 1000',
            'per_page.min' => 'عدد العناصر في الصفحة يجب أن يكون أكبر من 0',
            'per_page.max' => 'عدد العناصر في الصفحة لا يمكن أن يتجاوز 100',
            'tags.max' => 'يمكن البحث بـ 10 علامات كحد أقصى',
            'tags.*.max' => 'العلامة لا يمكن أن تتجاوز 50 حرف',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string>
     *
     * @psalm-return array{q: 'كلمة البحث', category_id: 'فئة المنتج', brand_id: 'علامة المنتج التجارية', min_price: 'الحد الأدنى للسعر', max_price: 'الحد الأقصى للسعر', sort: 'نوع الترتيب', order: 'اتجاه الترتيب', page: 'رقم الصفحة', per_page: 'عدد العناصر في الصفحة', tags: 'العلامات', in_stock: 'متوفر في المخزون', featured: 'منتج مميز'}
     */
    #[\Override]
    public function attributes(): array
    {
        return [
            'q' => 'كلمة البحث',
            'category_id' => 'فئة المنتج',
            'brand_id' => 'علامة المنتج التجارية',
            'min_price' => 'الحد الأدنى للسعر',
            'max_price' => 'الحد الأقصى للسعر',
            'sort' => 'نوع الترتيب',
            'order' => 'اتجاه الترتيب',
            'page' => 'رقم الصفحة',
            'per_page' => 'عدد العناصر في الصفحة',
            'tags' => 'العلامات',
            'in_stock' => 'متوفر في المخزون',
            'featured' => 'منتج مميز',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(): void
    {
        // The 'gte:min_price' rule already handles the price validation.
        // Custom warnings are experimental and can be handled on the frontend if needed.
    }

    /**
     * Get search filters.
     *
     * @return array<string, string|int|bool|array<string>|null>
     */
    public function getFilters(): array
    {
        return $this->safe()->except(['q', 'sort', 'order', 'page', 'per_page']);
    }

    /**
     * Get search query.
     */
    public function getQuery(): string
    {
        $query = $this->validated('q', '');

        return is_string($query) ? $query : '';
    }

    /**
     * Get pagination parameters.
     *
     * @return array<int>
     *
     * @psalm-return array{page: int, per_page: int}
     */
    public function getPagination(): array
    {
        $page = $this->input('page', 1);
        $perPage = $this->input('per_page', 15);

        return [
            'page' => is_numeric($page) ? (int) $page : 1,
            'per_page' => is_numeric($perPage) ? (int) $perPage : 15,
        ];
    }

    /**
     * Get sorting parameters.
     *
     * @return array<string, string>
     */
    public function getSorting(): array
    {
        $validated = $this->validated();

        return [
            'sort' => $validated['sort'] ?? 'popularity',
            'order' => $validated['order'] ?? 'desc',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    #[\Override]
    protected function prepareForValidation(): void
    {
        $this->merge($this->mergeQueryForValidation());
        $this->merge($this->mergeTagsForValidation());
        $this->merge($this->mergeSortingAndPagination());
    }

    /**
     * @return array<string>
     *
     * @psalm-return array{q: string}
     */
    private function mergeQueryForValidation(): array
    {
        return [
            'q' => $this->prepareQueryForValidation(),
        ];
    }

    /**
     * @return array<array<string>>
     *
     * @psalm-return array{tags: array<int, string>}
     */
    private function mergeTagsForValidation(): array
    {
        return [
            'tags' => $this->prepareTagsForValidation(),
        ];
    }

    /**
     * @return array<string, string|int>
     */
    private function mergeSortingAndPagination(): array
    {
        return [
            'sort' => $this->input('sort', 'popularity'),
            'order' => $this->input('order', 'desc'),
            'page' => (int) $this->input('page', 1),
            'per_page' => (int) $this->input('per_page', 15),
        ];
    }

    private function prepareQueryForValidation(): string
    {
        return is_string($this->q) ? trim($this->q) : '';
    }

    /**
     * @return array<int, string>
     */
    private function prepareTagsForValidation(): array
    {
        return is_array($this->tags) ? array_map(static fn ($tag): string => is_string($tag) ? trim($tag) : '', $this->tags) : [];
    }

    /**
     * @return array<array<string>>
     *
     * @psalm-return array{category_id: list{'nullable', 'integer', 'exists:categories,id'}}
     */
    private function getCategoryIdRules(): array
    {
        return [
            'category_id' => [
                'nullable',
                'integer',
                'exists:categories,id',
            ],
        ];
    }

    /**
     * @return array<array<string>>
     *
     * @psalm-return array{q: list{'required', 'string', 'min:2', 'max:255'}}
     */
    private function getQueryRules(): array
    {
        return [
            'q' => [
                'required',
                'string',
                'min:2',
                'max:255',
            ],
        ];
    }

    /**
     * @return array<array<string>>
     *
     * @psalm-return array{brand_id: list{'nullable', 'integer', 'exists:brands,id'}}
     */
    private function getBrandIdRules(): array
    {
        return [
            'brand_id' => [
                'nullable',
                'integer',
                'exists:brands,id',
            ],
        ];
    }

    /**
     * @return array<array<string>>
     *
     * @psalm-return array{min_price: list{'nullable', 'numeric', 'min:0', 'max:999999.99'}, max_price: list{'nullable', 'numeric', 'min:0', 'max:999999.99', 'gte:min_price'}}
     */
    private function getPriceRules(): array
    {
        return [
            'min_price' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999999.99',
            ],
            'max_price' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999999.99',
                'gte:min_price',
            ],
        ];
    }

    /**
     * @return array<array<string>>
     *
     * @psalm-return array{sort: list{'nullable', 'string', 'in:name,price,created_at,updated_at,popularity'}, order: list{'nullable', 'string', 'in:asc,desc'}}
     */
    private function getSortingRules(): array
    {
        return [
            'sort' => [
                'nullable',
                'string',
                'in:name,price,created_at,updated_at,popularity',
            ],
            'order' => [
                'nullable',
                'string',
                'in:asc,desc',
            ],
        ];
    }

    /**
     * @return array<array<string>>
     *
     * @psalm-return array{page: list{'nullable', 'integer', 'min:1', 'max:1000'}, per_page: list{'nullable', 'integer', 'min:1', 'max:100'}}
     */
    private function getPaginationRules(): array
    {
        return [
            'page' => [
                'nullable',
                'integer',
                'min:1',
                'max:1000',
            ],
            'per_page' => [
                'nullable',
                'integer',
                'min:1',
                'max:100',
            ],
        ];
    }

    /**
     * @return array<array<string>>
     *
     * @psalm-return array{tags: list{'nullable', 'array', 'max:10'}, 'tags.*': list{'string', 'max:50'}}
     */
    private function getTagsRules(): array
    {
        return [
            'tags' => [
                'nullable',
                'array',
                'max:10',
            ],
            'tags.*' => [
                'string',
                'max:50',
            ],
        ];
    }

    /**
     * @return array<array<string>>
     *
     * @psalm-return array{in_stock: list{'nullable', 'boolean'}, featured: list{'nullable', 'boolean'}}
     */
    private function getStatusRules(): array
    {
        return [
            'in_stock' => [
                'nullable',
                'boolean',
            ],
            'featured' => [
                'nullable',
                'boolean',
            ],
        ];
    }
}

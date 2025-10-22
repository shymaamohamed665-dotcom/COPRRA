<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 */
final class ProductController extends Controller
{
    public function __construct()
    {
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        try {
            return $this->createProductAndRespond($request);
        } catch (\Exception $e) {
            return $this->handleApiException($e, 'An error occurred while creating the product');
        }
    }

    /**
     * @OA\Get(
     *     path="/products",
     *     summary="List products",
     *     description="Get a list of products with optional search",
     *     operationId="listProducts",
     *     tags={"Products"},
     *
     *     @OA\Parameter(
     *         name="name",
     *         in="query",
     *         description="Search by product name",
     *
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="List of products",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Product")),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    /**
     * Display a listing of products.
     *
     * @param  Request  $request  The request containing query parameters.
     *
     * @return JsonResponse The JSON response with the list of products.
     */
    public function index(Request $request): JsonResponse
    {
        // Validate query parameters and return 422 on invalid inputs
        $validator = \Validator::make($request->query(), [
            // لا نرفض القيم الأكبر من 100، سنقوم بتقنينها لاحقًا
            'per_page' => 'sometimes|integer|min:1',
            'sort' => 'sometimes|in:price_asc,price_desc',
            'category_id' => 'sometimes|integer',
            'brand_id' => 'sometimes|integer',
            'min_price' => 'sometimes|numeric|min:0',
            'max_price' => 'sometimes|numeric|min:0',
            'search' => 'sometimes|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Invalid parameters',
                'errors' => $validator->errors(),
            ], 422);
        }

        $query = Product::query()
            ->with(['category:id,name', 'brand:id,name', 'stores:id,name'])
            ->where('is_active', true);
        $query = $this->applySearchFilter($query, $request, 'search', 'name');

        // Apply filters
        $categoryId = $request->input('category_id');
        if ($categoryId !== null && is_numeric($categoryId)) {
            $query->where('category_id', (int) $categoryId);
        }

        $brandId = $request->input('brand_id');
        if ($brandId !== null && is_numeric($brandId)) {
            $query->where('brand_id', (int) $brandId);
        }

        $isFeatured = $request->input('is_featured');
        if ($isFeatured !== null) {
            $flag = in_array(strtolower((string) $isFeatured), ['1', 'true', 'on', 'yes'], true);
            $query->where('is_featured', $flag);
        }

        $minPrice = $request->input('min_price');
        if ($minPrice !== null && is_numeric($minPrice)) {
            $query->where('price', '>=', (float) $minPrice);
        }
        $maxPrice = $request->input('max_price');
        if ($maxPrice !== null && is_numeric($maxPrice)) {
            $query->where('price', '<=', (float) $maxPrice);
        }

        $sort = $request->input('sort');
        if ($sort === 'price_asc') {
            $query->orderBy('price', 'asc');
        } elseif ($sort === 'price_desc') {
            $query->orderBy('price', 'desc');
        }

        // Pagination (limit per_page to 100)
        $perPageRaw = $request->input('per_page', 15);
        $perPage = is_numeric($perPageRaw) ? (int) $perPageRaw : 15;
        $perPage = max(1, min(100, $perPage));

        $paginator = $query->paginate($perPage);

        $data = collect($paginator->items())->map(function (Product $product): array {
            return $this->formatProductResponse($product);
        });

        return response()->json([
            'data' => $data,
            'links' => [
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
            ],
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
            'message' => 'Products retrieved successfully',
        ]);
    }

    /**
     * @OA\Get(
     *     path="/products/{id}",
     *     summary="Get product",
     *     description="Get a single product by ID",
     *     operationId="getProduct",
     *     tags={"Products"},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Product ID",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Product details",
     *
     *         @OA\JsonContent(ref="#/components/schemas/Product")
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Product not found"
     *     )
     * )
     */
    public function show(int $id): JsonResponse
    {
        try {
            return $this->getProductAndRespond($id);
        } catch (\Exception $e) {
            return $this->handleApiException($e, 'An error occurred while retrieving the product');
        }
    }

    /**
     * @OA\Put(
     *     path="/products/{id}",
     *     summary="Update product",
     *     description="Update an existing product (Admin only)",
     *     operationId="updateProduct",
     *     tags={"Products"},
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Product ID",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(ref="#/components/schemas/ProductUpdateRequest")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Product updated successfully",
     *
     *         @OA\JsonContent(ref="#/components/schemas/Product")
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Product not found"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - Admin access required"
     *     )
     * )
     */
    public function update(UpdateProductRequest $request, int $id): JsonResponse
    {
        try {
            $product = Product::findOrFail($id);

            $validated = $request->validated();

            $validated['slug'] = $this->updateProductSlug($validated, $id);

            $product->update($validated);

            return response()->json([
                'data' => $this->formatProductResponse($product),
                'message' => 'Product updated successfully',
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Product not found'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while updating the product',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/products",
     *     summary="Create product",
     *     description="Create a new product (Admin only)",
     *     operationId="createProduct",
     *     tags={"Products"},
     *     security={{"sanctum": {}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(ref="#/components/schemas/ProductCreateRequest")
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Product created successfully",
     *
     *         @OA\JsonContent(ref="#/components/schemas/Product")
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation failed"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - Admin access required"
     *     )
     * )
     */

    /**
     * Handle common API exceptions and return appropriate responses.
     *
     * @param  \Exception  $e  The exception to handle
     * @param  string  $defaultMessage  Default error message
     */
    private function handleApiException(\Exception $e, string $defaultMessage): JsonResponse
    {
        if ($e instanceof ValidationException) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }

        if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json(['message' => 'Resource not found'], 404);
        }

        return response()->json([
            'message' => $defaultMessage,
            'error' => $e->getMessage(),
        ], 500);
    }

    private function createProductAndRespond(StoreProductRequest $request): JsonResponse
    {
        $validated = $request->validated();

        /** @var string $name */
        $name = $validated['name'];
        $validated['slug'] = $this->generateUniqueSlug($name);

        $product = Product::create($validated);

        return response()->json([
            'data' => $this->formatProductResponse($product),
            'message' => 'Product created successfully',
        ], 201);
    }

    /**
     * Apply filter to query based on request parameter
     */
    private function applySearchFilter(\Illuminate\Database\Eloquent\Builder $query, Request $request, string $paramName, string $fieldName): \Illuminate\Database\Eloquent\Builder
    {
        if ($request->has($paramName)) {
            $value = $request->input($paramName);
            if ($value !== null && $value !== '') {
                $query->where($fieldName, 'like', '%'.$value.'%');
            }
        }

        return $query;
    }

    /**
     * @psalm-return \Illuminate\Database\Eloquent\Collection<int, \Illuminate\Database\Eloquent\Model>
     */
    private function getFilteredProducts(Request $request): \Illuminate\Database\Eloquent\Collection
    {
        $query = Product::query()->where('is_active', true);

        // Search by name using `search` query param
        $query = $this->applySearchFilter($query, $request, 'search', 'name');

        // Filter by category_id
        $categoryId = $request->input('category_id');
        if ($categoryId !== null && is_numeric($categoryId)) {
            $query->where('category_id', (int) $categoryId);
        }

        // Filter by brand_id
        $brandId = $request->input('brand_id');
        if ($brandId !== null && is_numeric($brandId)) {
            $query->where('brand_id', (int) $brandId);
        }

        // Filter by featured flag
        $isFeatured = $request->input('is_featured');
        if ($isFeatured !== null) {
            $flag = in_array(strtolower((string) $isFeatured), ['1', 'true', 'on', 'yes'], true);
            $query->where('is_featured', $flag);
        }

        // Filter by price range
        $minPrice = $request->input('min_price');
        if ($minPrice !== null && is_numeric($minPrice)) {
            $query->where('price', '>=', (float) $minPrice);
        }
        $maxPrice = $request->input('max_price');
        if ($maxPrice !== null && is_numeric($maxPrice)) {
            $query->where('price', '<=', (float) $maxPrice);
        }

        // Sorting
        $sort = $request->input('sort');
        if ($sort === 'price_asc') {
            $query->orderBy('price', 'asc');
        } elseif ($sort === 'price_desc') {
            $query->orderBy('price', 'desc');
        }

        return $query->get();
    }

    private function getProductAndRespond(int $id): JsonResponse
    {
        $product = Product::with(['category:id,name', 'brand:id,name', 'stores:id,name'])->findOrFail($id);

        return response()->json([
            'data' => $this->formatProductResponse($product),
            'message' => 'Product retrieved successfully',
        ]);
    }

    private function updateProductSlug(array $validated, int $id): string
    {
        if (! isset($validated['name'])) {
            return $validated['slug'] ?? '';
        }

        $nameValue = $validated['name'];
        $nameString = is_string($nameValue) ? $nameValue : '';
        $baseSlug = \Illuminate\Support\Str::slug($nameString);
        $slug = $baseSlug;
        $counter = 1;

        while (Product::where('slug', $slug)->where('id', '!=', $id)->exists()) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Generate a unique slug for the product.
     */
    private function generateUniqueSlug(string $name): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 1;

        while (Product::where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Format product data for response.
     *
     * @return array<array<int|string>|bool|int|mixed|string|null>
     *
     * @psalm-return array{id: int, name: string, slug: string, description: string, price: string, created_at: mixed|null, updated_at: mixed|null, image_url: string|null, is_active: bool, category_id: int, brand_id: int, category: array{id: int, name: string}|null, brand: array{id: int, name: string}|null, stores: array<never, never>|mixed}
     */
    private function formatProductResponse(Product $product): array
    {
        $response = [
            'id' => $product->id,
            'name' => htmlspecialchars($product->name, ENT_QUOTES, 'UTF-8'),
            'slug' => $product->slug,
            'description' => htmlspecialchars($product->description, ENT_QUOTES, 'UTF-8'),
            'price' => $product->price,
            'created_at' => $product->created_at ? $product->created_at->toIso8601String() : null,
            'updated_at' => $product->updated_at ? $product->updated_at->toIso8601String() : null,
            'image_url' => $product->image ? asset('storage/'.$product->image) : null,
            'is_active' => $product->is_active,
            'category_id' => $product->category_id,
            'brand_id' => $product->brand_id,
            'category' => null,
            'brand' => null,
            'stores' => [],
        ];

        if ($product->relationLoaded('category') && $product->category) {
            $response['category'] = [
                'id' => $product->category->id,
                'name' => $product->category->name,
            ];
        }

        if ($product->relationLoaded('brand') && $product->brand) {
            $response['brand'] = [
                'id' => $product->brand->id,
                'name' => $product->brand->name,
            ];
        }

        if ($product->relationLoaded('stores') && $product->stores) {
            $response['stores'] = $product->stores->map(function ($store): array {
                return [
                    'id' => $store->id,
                    'name' => $store->name,
                ];
            })->all();
        }

        return $response;
    }
}

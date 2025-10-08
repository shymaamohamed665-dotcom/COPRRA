<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

final class ProductController extends Controller
{
    public function __construct(private readonly ProductService $productService)
    {
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
     * @param \Exception $e The exception to handle
     * @param string $defaultMessage Default error message
     * @return JsonResponse
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
    
    public function store(StoreProductRequest $request): JsonResponse
    {
        try {
            return $this->createProductAndRespond($request);
        } catch (\Exception $e) {
            return $this->handleApiException($e, 'An error occurred while creating the product');
        }
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
        $products = $this->getFilteredProducts($request);

        return response()->json([
            'data' => $products->map(function ($product) {
                return $this->formatProductResponse($product);
            }),
            'message' => 'Products retrieved successfully',
        ]);
    }

    /**
     * Apply filter to query based on request parameter
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param Request $request
     * @param string $paramName
     * @param string $fieldName
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function applySearchFilter($query, Request $request, string $paramName, string $fieldName)
    {
        if ($request->has($paramName) && $request->$paramName !== null && $request->$paramName !== '') {
            $value = $request->$paramName;
            $query->where($fieldName, 'like', '%' . $value . '%');
        }
        
        return $query;
    }
    
    private function getFilteredProducts(Request $request): \Illuminate\Database\Eloquent\Collection
    {
        $query = Product::query();
        
        $query = $this->applySearchFilter($query, $request, 'name', 'name');
        
        return $query->get();
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

    private function getProductAndRespond(int $id): JsonResponse
    {
        $product = Product::findOrFail($id);

        return response()->json([
            'data' => $this->formatProductResponse($product),
            'message' => 'Product retrieved successfully',
        ]);
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
            $slug = $baseSlug . '-' . $counter;
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
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Format product data for response.
     *
     * @return array<string, int|string|float|bool|null>
     */
    private function formatProductResponse(Product $product): array
    {
        return [
            'id' => $product->id,
            'name' => htmlspecialchars($product->name, ENT_QUOTES, 'UTF-8'),
            'description' => htmlspecialchars($product->description, ENT_QUOTES, 'UTF-8'),
            'price' => $product->price,
            'image_url' => $product->image ? asset('storage/' . $product->image) : null,
            'is_active' => $product->is_active,
            'category_id' => $product->category_id,
            'brand_id' => $product->brand_id,
        ];
    }
}

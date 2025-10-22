<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Product\SearchFilterBuilder;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductService $productService,
        private readonly SearchFilterBuilder $searchFilterBuilder
    ) {
    }

    public function index(Request $request): View
    {
        // Check if search parameters are present
        if ($request->has('search') || $request->has('category') || $request->has('sort') || $request->has('order')) {
            return $this->search($request);
        }

        $products = $this->productService->getPaginatedProducts();

        return view('products.index', [
            'products' => $products,
        ]);
    }

    public function search(Request $request): View
    {
        $query = $request->get('search', '');
        $queryString = is_string($query) ? $query : '';

        $filters = $this->searchFilterBuilder->buildFromRequest($request);

        $products = $this->productService->searchProducts($queryString, $filters);

        return view('products.index', [
            'products' => $products,
        ]);
    }

    public function show(string $slug): View
    {
        $product = $this->productService->getBySlug($slug);

        if (! $product instanceof \App\Models\Product) {
            abort(404);
        }

        $relatedProducts = $this->productService->getRelatedProducts($product);

        return view('products.show', [
            'product' => $product,
            'relatedProducts' => $relatedProducts,
        ]);
    }
}

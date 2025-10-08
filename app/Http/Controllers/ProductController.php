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
    ) {}

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
}

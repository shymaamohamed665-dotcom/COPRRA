<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Contracts\View\View;

/**
 */
class HomeController extends Controller
{
    /**
     * Display the application homepage.
     */
    public function index(): View
    {
        $featuredProducts = Product::where('is_featured', true)
            ->with(['category', 'brand'])
            ->latest()
            ->limit(8)
            ->get();

        return view('welcome', [
            'featuredProducts' => $featuredProducts,
        ]);
    }
}

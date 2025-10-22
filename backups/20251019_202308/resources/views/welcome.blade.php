<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'COPRRA') }} - Price Comparison Platform</title>
    <meta name="description" content="Find the best prices for your favorite products across multiple stores">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .hero-section { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .product-card { transition: transform 0.3s ease; }
        .product-card:hover { transform: translateY(-5px); }
        .category-card { transition: all 0.3s ease; }
        .category-card:hover { transform: scale(1.05); }
    </style>
</head>
<body>
    @include('layouts.navigation')
    
    <!-- Hero Section -->
    <section class="hero-section text-white py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="display-4 fw-bold mb-4">Find the Best Prices</h1>
                    <p class="lead mb-4">Compare prices across multiple stores and save money on your purchases. Our AI-powered platform helps you find the best deals.</p>
                    <a href="{{ route('products.index') }}" class="btn btn-light btn-lg">Start Shopping</a>
                </div>
                <div class="col-lg-6">
                    <i class="fas fa-search-dollar fa-10x opacity-75"></i>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Products -->
    @if(isset($featuredProducts) && $featuredProducts->count() > 0)
    <section class="py-5">
        <div class="container">
            <h2 class="text-center mb-5">Featured Products</h2>
            <div class="row">
                @foreach($featuredProducts as $product)
                <div class="col-md-3 mb-4">
                    <div class="card product-card h-100">
                        @if($product->image)
                        <img src="{{ $product->image }}" class="card-img-top" alt="{{ $product->name }}" style="height: 200px; object-fit: cover;">
                        @else
                        <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                            <i class="fas fa-image fa-3x text-muted"></i>
                        </div>
                        @endif
                        <div class="card-body">
                            <h5 class="card-title">{{ $product->name }}</h5>
                            <!-- Null-safe access to category name to avoid 500 errors -->
                            <p class="card-text text-muted">{{ $product->category?->name ?? 'Uncategorized' }}</p>
                            <p class="card-text"><strong>${{ number_format($product->price, 2) }}</strong></p>
                            <a href="{{ route('products.show', $product) }}" class="btn btn-primary">View Details</a>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    <!-- Categories -->
    @if(isset($categories) && $categories->count() > 0)
    <section class="py-5 bg-light">
        <div class="container">
            <h2 class="text-center mb-5">Shop by Category</h2>
            <div class="row">
                @foreach($categories as $category)
                <div class="col-md-2 mb-4">
                    <div class="card category-card text-center h-100">
                        <div class="card-body">
                            @if($category->image_url)
                            <img src="{{ $category->image_url }}" class="img-fluid mb-3" alt="{{ $category->name }}" style="height: 80px; object-fit: cover;">
                            @else
                            <i class="fas fa-tag fa-3x text-muted mb-3"></i>
                            @endif
                            <h6 class="card-title">{{ $category->name }}</h6>
                            <p class="card-text small text-muted">{{ $category->products_count }} products</p>
                            <a href="{{ route('categories.show', $category) }}" class="btn btn-outline-primary btn-sm">Browse</a>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    <!-- Brands -->
    @if(isset($brands) && $brands->count() > 0)
    <section class="py-5">
        <div class="container">
            <h2 class="text-center mb-5">Popular Brands</h2>
            <div class="row">
                @foreach($brands as $brand)
                <div class="col-md-2 mb-4">
                    <div class="card text-center h-100">
                        <div class="card-body">
                            @if($brand->logo_url)
                            <img src="{{ $brand->logo_url }}" class="img-fluid mb-3" alt="{{ $brand->name }}" style="height: 60px; object-fit: contain;">
                            @else
                            <i class="fas fa-building fa-3x text-muted mb-3"></i>
                            @endif
                            <h6 class="card-title">{{ $brand->name }}</h6>
                            <p class="card-text small text-muted">{{ $brand->products_count }} products</p>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    <!-- Footer -->
    <footer class="bg-dark text-white py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>{{ config('app.name', 'COPRRA') }}</h5>
                    <p>Your trusted price comparison platform</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p>&copy; {{ date('Y') }} {{ config('app.name', 'COPRRA') }}. All rights reserved.</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
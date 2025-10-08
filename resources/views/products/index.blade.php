@extends('layouts.app')

@section('title', 'Products - ' . config('app.name'))

@section('content')
<div class="container">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Products</h1>
                <div class="d-flex gap-2">
                    <div class="d-flex flex-column">
                        <label for="categoryFilter" class="form-label small mb-1">Filter by Category</label>
                        <select class="form-select" id="categoryFilter" aria-label="Filter products by category">
                            <option value="">All Categories</option>
                            @foreach($categories ?? [] as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="d-flex flex-column">
                        <label for="brandFilter" class="form-label small mb-1">Filter by Brand</label>
                        <select class="form-select" id="brandFilter" aria-label="Filter products by brand">
                            <option value="">All Brands</option>
                            @foreach($brands ?? [] as $brand)
                            <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="d-flex flex-column">
                        <label for="sortBy" class="form-label small mb-1">Sort by</label>
                        <select class="form-select" id="sortBy" aria-label="Sort products">
                            <option value="name">Sort by Name</option>
                            <option value="price_low">Price: Low to High</option>
                            <option value="price_high">Price: High to Low</option>
                            <option value="newest">Newest First</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row" id="productsContainer">
        @forelse($products ?? [] as $product)
        <div class="col-lg-3 col-md-4 col-sm-6 mb-4 product-item"
             data-category="{{ $product->category_id }}"
             data-brand="{{ $product->brand_id }}"
             data-price="{{ $product->price }}">
            <div class="card h-100 product-card">
                @if($product->image)
                <img src="{{ $product->image }}"
                     class="card-img-top"
                     alt="{{ $product->name }} - {{ $product->category->name ?? 'Product' }}"
                     style="height: 200px; object-fit: cover;"
                     loading="lazy"
                     width="300"
                     height="200">
                @else
                <div class="card-img-top bg-light d-flex align-items-center justify-content-center"
                     style="height: 200px;"
                     role="img"
                     aria-label="No image available for {{ $product->name }}">
                    <i class="fas fa-image fa-3x text-muted" aria-hidden="true"></i>
                </div>
                @endif
                <div class="card-body d-flex flex-column">
                    <h5 class="card-title">{{ $product->name }}</h5>
                    <p class="card-text text-muted small">
                        <span class="badge bg-secondary">{{ $product->category->name ?? 'Uncategorized' }}</span>
                        @if($product->brand)
                        <span class="badge bg-info">{{ $product->brand->name }}</span>
                        @endif
                    </p>
                    <p class="card-text">
                        <strong class="text-primary">${{ number_format($product->price, 2) }}</strong>
                    </p>
                    <div class="mt-auto">
                        <a href="{{ route('products.show', $product->slug) }}"
                           class="btn btn-primary btn-sm w-100"
                           aria-label="View details for {{ $product->name }}">
                            View Details
                        </a>
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="col-12">
            <div class="text-center py-5">
                <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                <h3>No products found</h3>
                <p class="text-muted">Try adjusting your filters or check back later for new products.</p>
            </div>
        </div>
        @endforelse
    </div>

    @if(isset($products) && $products->hasPages())
    <div class="row">
        <div class="col-12">
            <nav aria-label="Products pagination">
                {{ $products->links() }}
            </nav>
        </div>
    </div>
    @endif
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const categoryFilter = document.getElementById('categoryFilter');
    const brandFilter = document.getElementById('brandFilter');
    const sortBy = document.getElementById('sortBy');
    const productsContainer = document.getElementById('productsContainer');
    const productItems = document.querySelectorAll('.product-item');

    function filterAndSort() {
        const selectedCategory = categoryFilter.value;
        const selectedBrand = brandFilter.value;
        const sortValue = sortBy.value;

        let visibleProducts = Array.from(productItems).filter(item => {
            const categoryMatch = !selectedCategory || item.dataset.category === selectedCategory;
            const brandMatch = !selectedBrand || item.dataset.brand === selectedBrand;
            return categoryMatch && brandMatch;
        });

        // Sort products
        visibleProducts.sort((a, b) => {
            switch(sortValue) {
                case 'name':
                    return a.querySelector('.card-title').textContent.localeCompare(b.querySelector('.card-title').textContent);
                case 'price_low':
                    return parseFloat(a.dataset.price) - parseFloat(b.dataset.price);
                case 'price_high':
                    return parseFloat(b.dataset.price) - parseFloat(a.dataset.price);
                case 'newest':
                    return 0; // Assuming newest first by default order
                default:
                    return 0;
            }
        });

        // Hide all products first
        productItems.forEach(item => item.style.display = 'none');

        // Show filtered and sorted products
        visibleProducts.forEach(item => item.style.display = 'block');

        // Show no results message if no products visible
        const visibleCount = visibleProducts.length;
        if (visibleCount === 0) {
            productsContainer.innerHTML = `
                <div class="col-12">
                    <div class="text-center py-5">
                        <i class="fas fa-search fa-3x text-muted mb-3"></i>
                        <h3>No products match your filters</h3>
                        <p class="text-muted">Try adjusting your search criteria.</p>
                    </div>
                </div>
            `;
        }
    }

    categoryFilter.addEventListener('change', filterAndSort);
    brandFilter.addEventListener('change', filterAndSort);
    sortBy.addEventListener('change', filterAndSort);
});
</script>
@endsection

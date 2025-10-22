@extends('layouts.app')

@section('title', 'Categories - ' . config('app.name'))

@section('content')
<div class="container">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Product Categories</h1>
                <div class="d-flex gap-2">
                    <div class="d-flex flex-column">
                        <label for="searchInput" class="form-label small mb-1">Search Categories</label>
                        <input type="text" class="form-control" id="searchInput" placeholder="Search categories..." style="max-width: 300px;" aria-label="Search categories">
                    </div>
                    <div class="d-flex flex-column">
                        <label for="sortBy" class="form-label small mb-1">Sort by</label>
                        <select class="form-select" id="sortBy" style="max-width: 200px;" aria-label="Sort categories">
                            <option value="name">Sort by Name</option>
                            <option value="products_count">Most Products</option>
                            <option value="newest">Newest First</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row" id="categoriesContainer">
        @forelse($categories ?? [] as $category)
        <div class="col-lg-3 col-md-4 col-sm-6 mb-4 category-item"
             data-name="{{ strtolower($category->name) }}"
             data-products-count="{{ $category->products_count ?? 0 }}">
            <div class="card h-100 category-card">
                <div class="card-body text-center">
                    @if($category->image_url)
                    <img src="{{ $category->image_url }}" class="img-fluid mb-3" alt="{{ $category->name }}" style="height: 80px; object-fit: cover;">
                    @else
                    <div class="bg-light rounded d-flex align-items-center justify-content-center mb-3" style="height: 80px;">
                        <i class="fas fa-tag fa-2x text-muted"></i>
                    </div>
                    @endif
                    <h5 class="card-title">{{ $category->name }}</h5>
                    <p class="card-text text-muted">
                        <span class="badge bg-primary">{{ $category->products_count ?? 0 }} products</span>
                    </p>
                    @if($category->description)
                    <p class="card-text small text-muted">{{ Str::limit($category->description, 100) }}</p>
                    @endif
                    <a href="{{ route('categories.show', $category) }}" class="btn btn-outline-primary btn-sm">Browse Products</a>
                </div>
            </div>
        </div>
        @empty
        <div class="col-12">
            <div class="text-center py-5">
                <i class="fas fa-tags fa-3x text-muted mb-3"></i>
                <h3>No categories found</h3>
                <p class="text-muted">Check back later for new categories.</p>
            </div>
        </div>
        @endforelse
    </div>

    @if(isset($categories) && $categories->hasPages())
    <div class="row">
        <div class="col-12">
            <nav aria-label="Categories pagination">
                {{ $categories->links() }}
            </nav>
        </div>
    </div>
    @endif
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const sortBy = document.getElementById('sortBy');
    const categoriesContainer = document.getElementById('categoriesContainer');
    const categoryItems = document.querySelectorAll('.category-item');

    function filterAndSort() {
        const searchTerm = searchInput.value.toLowerCase();
        const sortValue = sortBy.value;

        let visibleCategories = Array.from(categoryItems).filter(item => {
            const name = item.dataset.name;
            return name.includes(searchTerm);
        });

        // Sort categories
        visibleCategories.sort((a, b) => {
            switch(sortValue) {
                case 'name':
                    return a.querySelector('.card-title').textContent.localeCompare(b.querySelector('.card-title').textContent);
                case 'products_count':
                    return parseInt(b.dataset.productsCount) - parseInt(a.dataset.productsCount);
                case 'newest':
                    return 0; // Assuming newest first by default order
                default:
                    return 0;
            }
        });

        // Hide all categories first
        categoryItems.forEach(item => item.style.display = 'none');

        // Show filtered and sorted categories
        visibleCategories.forEach(item => item.style.display = 'block');

        // Show no results message if no categories visible
        const visibleCount = visibleCategories.length;
        if (visibleCount === 0) {
            categoriesContainer.innerHTML = `
                <div class="col-12">
                    <div class="text-center py-5">
                        <i class="fas fa-search fa-3x text-muted mb-3"></i>
                        <h3>No categories match your search</h3>
                        <p class="text-muted">Try adjusting your search criteria.</p>
                    </div>
                </div>
            `;
        }
    }

    searchInput.addEventListener('input', filterAndSort);
    sortBy.addEventListener('change', filterAndSort);
});
</script>
@endsection

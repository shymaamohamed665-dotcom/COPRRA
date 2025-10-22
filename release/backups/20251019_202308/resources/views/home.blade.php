@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">Home</div>

                <div class="card-body">
                    <h5>Featured Products</h5>
                    @if(isset($featuredProducts) && $featuredProducts->count() > 0)
                    <div class="row">
                        @foreach($featuredProducts as $product)
                        <div class="col-md-3">
                            <div class="card mb-3">
                                <div class="card-body">
                                    <h6 class="card-title">{{ $product->name }}</h6>
                                    <!-- Null-safe access to brand name to avoid 500 errors -->
                                    <p class="card-text text-muted">{{ $product->brand?->name ?? '' }}</p>
                                    <p><strong>${{ number_format($product->price, 2) }}</strong></p>
                                    <a href="{{ route('products.show', $product) }}" class="btn btn-sm btn-primary">View</a>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <p class="text-muted">No featured products available.</p>
                    @endif

                    @if(isset($categories) && $categories->count() > 0)
                    <h5 class="mt-4">Categories</h5>
                    <div class="row">
                        @foreach($categories as $category)
                        <div class="col-md-2">
                            <div class="card mb-3">
                                <div class="card-body text-center">
                                    <h6 class="card-title">{{ $category->name }}</h6>
                                    <p class="small text-muted">{{ $category->products_count }} products</p>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @endif

                    @if(isset($brands) && $brands->count() > 0)
                    <h5 class="mt-4">Brands</h5>
                    <div class="row">
                        @foreach($brands as $brand)
                        <div class="col-md-2">
                            <div class="card mb-3">
                                <div class="card-body text-center">
                                    <h6 class="card-title">{{ $brand->name }}</h6>
                                    <p class="small text-muted">{{ $brand->products_count }} products</p>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@extends('layouts.app')

@section('title', $brand->name . ' - ' . config('app.name'))

@section('content')
<div class="container">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>{{ $brand->name }}</h1>
                <div class="d-flex gap-2">
                    <a href="{{ route('brands.edit', $brand) }}" class="btn btn-outline-secondary">Edit</a>
                    <form action="{{ route('brands.destroy', $brand) }}" method="POST" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger" onclick="return confirm('Are you sure?')">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body text-center">
                    @if($brand->logo_url)
                    <img src="{{ $brand->logo_url }}" class="img-fluid mb-3" alt="{{ $brand->name }}" style="height: 150px; object-fit: contain;">
                    @else
                    <div class="bg-light rounded d-flex align-items-center justify-content-center mb-3" style="height: 150px;">
                        <i class="fas fa-building fa-4x text-muted"></i>
                    </div>
                    @endif
                    <h5 class="card-title">{{ $brand->name }}</h5>
                    @if($brand->description)
                    <p class="card-text">{{ $brand->description }}</p>
                    @endif
                    <p class="card-text">
                        <small class="text-muted">
                            Status: 
                            <span class="badge {{ $brand->is_active ? 'bg-success' : 'bg-secondary' }}">
                                {{ $brand->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </small>
                    </p>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Products by {{ $brand->name }}</h5>
                </div>
                <div class="card-body">
                    @if($brand->products->count() > 0)
                    <div class="row">
                        @foreach($brand->products as $product)
                        <div class="col-md-6 mb-3">
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="card-title">{{ $product->name }}</h6>
                                    <p class="card-text text-muted">{{ $product->category->name ?? 'Uncategorized' }}</p>
                                    <p class="card-text">
                                        <strong class="text-primary">${{ number_format($product->price, 2) }}</strong>
                                    </p>
                                    <a href="{{ route('products.show', $product) }}" class="btn btn-outline-primary btn-sm">View Product</a>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <div class="text-center py-4">
                        <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                        <h5>No products found</h5>
                        <p class="text-muted">This brand doesn't have any products yet.</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

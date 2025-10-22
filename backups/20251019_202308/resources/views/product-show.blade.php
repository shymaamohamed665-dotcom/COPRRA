@extends('layouts.app')

@section('title', $product->name)

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-6">
            @if($product->image)
                <img src="{{ $product->image }}" class="img-fluid" alt="{{ $product->name }}">
            @else
                <div class="bg-light p-5 text-center">
                    <p>No image available</p>
                </div>
            @endif
        </div>
        <div class="col-md-6">
            <h1>{{ $product->name }}</h1>

            @if($product->brand)
                <p><strong>Brand:</strong> {{ $product->brand->name }}</p>
            @endif

            @if($product->category)
                <p><strong>Category:</strong> {{ $product->category->name }}</p>
            @endif

            <p class="h3 text-success">${{ $product->price }}</p>

            @if($product->description)
                <div class="mt-4">
                    <h5>Description</h5>
                    <p>{{ $product->description }}</p>
                </div>
            @endif

            <div class="mt-4">
                <button class="btn btn-primary btn-lg" aria-label="Add product to cart">Add to Cart</button>
                <button class="btn btn-outline-secondary" aria-label="Add product to wishlist">Add to Wishlist</button>
            </div>

            @if($product->store)
                <div class="mt-3">
                    <small class="text-muted">Sold by: {{ $product->store->name }}</small>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@extends('layouts.app')

@section('title', $category->name)

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <h1>{{ $category->name }}</h1>

            @if($category->description)
                <p>{{ $category->description }}</p>
            @endif

            <div class="row">
                @forelse($products as $product)
                    <div class="col-md-3 mb-4">
                        <div class="card">
                            @if($product->image)
                                <img src="{{ $product->image }}" class="card-img-top" alt="{{ $product->name }}">
                            @endif
                            <div class="card-body">
                                <h5 class="card-title">{{ $product->name }}</h5>
                                <p class="card-text">{{ Str::limit($product->description, 100) }}</p>
                                <p class="card-text"><strong>${{ $product->price }}</strong></p>
                                <a href="{{ route('products.show', $product->slug) }}" class="btn btn-primary">View Product</a>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12">
                        <p>No products found in this category.</p>
                    </div>
                @endforelse
            </div>

            {{ $products->links() }}
        </div>
    </div>
</div>
@endsection

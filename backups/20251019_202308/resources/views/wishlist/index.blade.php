@extends('layouts.app')

@section('title', 'My Wishlist')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <h1>My Wishlist</h1>

            @if($wishlistItems->count() > 0)
                <div class="row">
                    @foreach($wishlistItems as $item)
                        <div class="col-md-3 mb-4">
                            <div class="card">
                                @if($item->product->image)
                                    <img src="{{ $item->product->image }}" class="card-img-top" alt="{{ $item->product->name }}">
                                @endif
                                <div class="card-body">
                                    <h5 class="card-title">{{ $item->product->name }}</h5>
                                    <p class="card-text">{{ Str::limit($item->product->description, 100) }}</p>
                                    <p class="card-text"><strong>${{ $item->product->price }}</strong></p>
                                    <div class="d-flex justify-content-between">
                                        <a href="#" class="btn btn-primary btn-sm" aria-label="View details for {{ $item->product->name }}">View Product</a>
                                        <form method="POST" action="{{ route('wishlist.destroy', $item->id) }}" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="product_id" value="{{ $item->product->id }}">
                                            <button type="submit" class="btn btn-outline-danger btn-sm" aria-label="Remove {{ $item->product->name }} from wishlist">Remove</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-5">
                    <h3>Your wishlist is empty</h3>
                    <p>Start adding products to your wishlist!</p>
                    <a href="#" class="btn btn-primary">Browse Products</a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

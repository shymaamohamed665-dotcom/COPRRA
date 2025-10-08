@extends('layouts.app')

@section('title', __('messages.home') . ' - ' . config('app.name'))

@section('content')
<div class="container py-4">
    <h1 class="mb-4">{{ __('messages.home') }}</h1>

    <h2 class="h4 mt-4 mb-3">{{ __('messages.featured_products') }}</h2>
    <div class="row">
        @forelse($featuredProducts as $product)
            <div class="col-md-3 mb-3">
                <div class="card h-100">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title">{{ $product->name }}</h5>
                        <p class="card-text">{{ $product->brand->name ?? '' }}</p>
                        <p class="mt-auto fw-bold">{{ number_format($product->price, 2) }}</p>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12 text-muted">{{ __('messages.no_results') }}</div>
        @endforelse
    </div>

    <h2 class="h4 mt-5 mb-3">{{ __('messages.categories') }}</h2>
    <div class="row">
        @forelse($categories as $category)
            <div class="col-md-2 mb-3">
                <div class="border rounded p-3 h-100">{{ $category->name }}</div>
            </div>
        @empty
            <div class="col-12 text-muted">{{ __('messages.no_results') }}</div>
        @endforelse
    </div>

    <h2 class="h4 mt-5 mb-3">{{ __('messages.brands') }}</h2>
    <div class="row">
        @forelse($brands as $brand)
            <div class="col-md-3 mb-3">
                <div class="border rounded p-3 h-100">{{ $brand->name }}</div>
            </div>
        @empty
            <div class="col-12 text-muted">{{ __('messages.no_results') }}</div>
        @endforelse
    </div>
</div>
@endsection



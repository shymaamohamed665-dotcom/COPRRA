@extends('layouts.app')

@section('title', 'Brands - ' . config('app.name'))

@section('content')
<div class="container">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Brands</h1>
                <a href="{{ route('brands.create') }}" class="btn btn-primary">Add New Brand</a>
            </div>
        </div>
    </div>

    <div class="row">
        @forelse($brands as $brand)
        <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    @if($brand->logo_url)
                    <img src="{{ $brand->logo_url }}" class="img-fluid mb-3" alt="{{ $brand->name }}" style="height: 80px; object-fit: contain;">
                    @else
                    <div class="bg-light rounded d-flex align-items-center justify-content-center mb-3" style="height: 80px;">
                        <i class="fas fa-building fa-2x text-muted"></i>
                    </div>
                    @endif
                    <h5 class="card-title">{{ $brand->name }}</h5>
                    @if($brand->description)
                    <p class="card-text small text-muted">{{ Str::limit($brand->description, 100) }}</p>
                    @endif
                    <div class="d-flex gap-2 justify-content-center">
                        <a href="{{ route('brands.show', $brand) }}" class="btn btn-outline-primary btn-sm">View</a>
                        <a href="{{ route('brands.edit', $brand) }}" class="btn btn-outline-secondary btn-sm">Edit</a>
                        <form action="{{ route('brands.destroy', $brand) }}" method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('Are you sure?')">Delete</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="col-12">
            <div class="text-center py-5">
                <i class="fas fa-building fa-3x text-muted mb-3"></i>
                <h3>No brands found</h3>
                <p class="text-muted">Start by adding your first brand.</p>
                <a href="{{ route('brands.create') }}" class="btn btn-primary">Add New Brand</a>
            </div>
        </div>
        @endforelse
    </div>

    @if($brands->hasPages())
    <div class="row">
        <div class="col-12">
            <nav aria-label="Brands pagination">
                {{ $brands->links() }}
            </nav>
        </div>
    </div>
    @endif
</div>
@endsection

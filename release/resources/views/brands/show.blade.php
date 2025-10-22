{{-- Minimal stub view for brands.show --}}
@php /** @var \App\Models\Brand $brand */ @endphp
<div>
    <h1>Brand Details</h1>
    <p>{{ $brand->name }}</p>
    <p>{{ $brand->slug }}</p>
    <p>{{ $brand->website_url }}</p>
    <p>{{ $brand->logo_url }}</p>
    <p>{{ $brand->description }}</p>
</div>

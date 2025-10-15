{{-- Minimal stub view for brands.edit --}}
@php /** @var \App\Models\Brand $brand */ @endphp
<div>
    <h1>Edit Brand</h1>
    <form method="POST" action="/brands/{{ $brand->id }}">
        @csrf
        @method('PUT')
        <input type="text" name="name" value="{{ $brand->name }}" />
        <input type="text" name="slug" value="{{ $brand->slug }}" />
        <input type="text" name="website_url" value="{{ $brand->website_url }}" />
        <input type="text" name="logo_url" value="{{ $brand->logo_url }}" />
        <textarea name="description">{{ $brand->description }}</textarea>
        <button type="submit">Update</button>
    </form>
</div>

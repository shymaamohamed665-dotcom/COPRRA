{{-- Minimal stub view for brands.index --}}
@php /** @var \Illuminate\Pagination\LengthAwarePaginator $brands */ @endphp
<div>
    <h1>Brands Index</h1>
    @if(isset($brands))
        <ul>
            @foreach($brands as $brand)
                <li>{{ $brand->name }}</li>
            @endforeach
        </ul>
        {{ $brands->links() }}
    @endif
</div>

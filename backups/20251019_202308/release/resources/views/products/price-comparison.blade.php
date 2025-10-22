@extends('layouts.app')

@section('title', __('Price Comparison') . ' - ' . $product->name)

@section('meta')
    <meta name="description" content="{{ __('Compare prices for') }} {{ $product->name }} {{ __('from multiple stores') }}">
    <meta property="og:title" content="{{ __('Price Comparison') }} - {{ $product->name }}">
    <meta property="og:description" content="{{ __('Find the best deal for') }} {{ $product->name }}">
    <meta property="og:image" content="{{ $product->image_url }}">
@endsection

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Breadcrumb -->
    <nav class="mb-6 text-sm">
        <ol class="flex items-center space-x-2 text-gray-600 dark:text-gray-400">
            <li><a href="{{ route('home') }}" class="hover:text-blue-600">{{ __('Home') }}</a></li>
            <li><span class="mx-2">/</span></li>
            <li><a href="{{ route('products.index') }}" class="hover:text-blue-600">{{ __('Products') }}</a></li>
            <li><span class="mx-2">/</span></li>
            <li><a href="{{ route('products.show', $product) }}" class="hover:text-blue-600">{{ $product->name }}</a></li>
            <li><span class="mx-2">/</span></li>
            <li class="text-gray-900 dark:text-white font-medium">{{ __('Price Comparison') }}</li>
        </ol>
    </nav>

    <!-- Product Header -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 mb-6">
        <div class="flex flex-col md:flex-row gap-6">
            <!-- Product Image -->
            <div class="md:w-1/4">
                <img src="{{ $product->image_url }}" alt="{{ $product->name }}" 
                     class="w-full rounded-lg shadow-sm">
            </div>

            <!-- Product Info -->
            <div class="md:w-3/4">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                    {{ $product->name }}
                </h1>

                @if($product->brand)
                <p class="text-lg text-gray-600 dark:text-gray-400 mb-4">
                    {{ __('by') }} <span class="font-semibold">{{ $product->brand }}</span>
                </p>
                @endif

                @if($product->description)
                <p class="text-gray-700 dark:text-gray-300 mb-4">
                    {{ Str::limit($product->description, 200) }}
                </p>
                @endif

                <!-- Quick Stats -->
                <div class="flex flex-wrap gap-4 mb-4">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-store text-blue-600"></i>
                        <span class="text-sm text-gray-700 dark:text-gray-300">
                            {{ count($prices) }} {{ __('stores') }}
                        </span>
                    </div>

                    @if($product->rating)
                    <div class="flex items-center gap-2">
                        <i class="fas fa-star text-yellow-400"></i>
                        <span class="text-sm text-gray-700 dark:text-gray-300">
                            {{ $product->rating }} ({{ $product->reviews_count }} {{ __('reviews') }})
                        </span>
                    </div>
                    @endif

                    @if($product->category)
                    <div class="flex items-center gap-2">
                        <i class="fas fa-tag text-green-600"></i>
                        <span class="text-sm text-gray-700 dark:text-gray-300">
                            {{ $product->category->name }}
                        </span>
                    </div>
                    @endif
                </div>

                <!-- Action Buttons -->
                <div class="flex flex-wrap gap-3">
                    <button onclick="refreshPrices()" 
                            class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-md transition">
                        <i class="fas fa-sync-alt mr-2"></i>
                        {{ __('Refresh Prices') }}
                    </button>

                    <a href="{{ route('products.show', $product) }}" 
                       class="inline-flex items-center px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium rounded-md transition">
                        <i class="fas fa-arrow-left mr-2"></i>
                        {{ __('Back to Product') }}
                    </a>

                    @auth
                    <button onclick="addToWishlist({{ $product->id }})" 
                            class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded-md transition">
                        <i class="fas fa-heart mr-2"></i>
                        {{ __('Add to Wishlist') }}
                    </button>
                    @endauth
                </div>
            </div>
        </div>
    </div>

    <!-- Price Comparison Table -->
    @if(count($prices) > 0)
        <x-price-comparison-table 
            :product="$product" 
            :prices="$prices" 
            :showHistory="$showHistory" 
        />
    @else
        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-6 text-center">
            <i class="fas fa-exclamation-triangle text-yellow-600 text-4xl mb-4"></i>
            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
                {{ __('No Prices Available') }}
            </h3>
            <p class="text-gray-600 dark:text-gray-400 mb-4">
                {{ __('We couldn\'t find any prices for this product at the moment.') }}
            </p>
            <button onclick="refreshPrices()" 
                    class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-md transition">
                <i class="fas fa-sync-alt mr-2"></i>
                {{ __('Try Again') }}
            </button>
        </div>
    @endif

    <!-- Price Alert Section -->
    @auth
    <div class="mt-8 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-lg p-6">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">
                    <i class="fas fa-bell text-blue-600 mr-2"></i>
                    {{ __('Price Alert') }}
                </h3>
                <p class="text-gray-600 dark:text-gray-400">
                    {{ __('Get notified when the price drops below your target') }}
                </p>
            </div>
            <button onclick="setupPriceAlert()" 
                    class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-md transition">
                {{ __('Set Alert') }}
            </button>
        </div>
    </div>
    @endauth

    <!-- Additional Information -->
    <div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Why Compare -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
            <div class="text-blue-600 text-3xl mb-4">
                <i class="fas fa-chart-line"></i>
            </div>
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">
                {{ __('Save Money') }}
            </h3>
            <p class="text-gray-600 dark:text-gray-400 text-sm">
                {{ __('Compare prices from multiple stores to find the best deal and save money on your purchase.') }}
            </p>
        </div>

        <!-- Real-time Updates -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
            <div class="text-green-600 text-3xl mb-4">
                <i class="fas fa-sync"></i>
            </div>
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">
                {{ __('Real-time Updates') }}
            </h3>
            <p class="text-gray-600 dark:text-gray-400 text-sm">
                {{ __('Our prices are updated regularly to ensure you always get the most accurate information.') }}
            </p>
        </div>

        <!-- Trusted Stores -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
            <div class="text-purple-600 text-3xl mb-4">
                <i class="fas fa-shield-alt"></i>
            </div>
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">
                {{ __('Trusted Stores') }}
            </h3>
            <p class="text-gray-600 dark:text-gray-400 text-sm">
                {{ __('We only compare prices from verified and trusted online stores for your safety.') }}
            </p>
        </div>
    </div>
</div>

@push('scripts')
<script>
function refreshPrices() {
    // Show loading state
    const btn = event.target;
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>{{ __("Refreshing...") }}';
    btn.disabled = true;

    // Fetch new prices
    fetch('{{ route("products.price-comparison.refresh", $product) }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('{{ __("Failed to refresh prices. Please try again.") }}');
    })
    .finally(() => {
        btn.innerHTML = originalText;
        btn.disabled = false;
    });
}

function setupPriceAlert() {
    // TODO: Implement price alert modal
    alert('{{ __("Price alert feature coming soon!") }}');
}

function addToWishlist(productId) {
    // TODO: Implement wishlist functionality
    alert('{{ __("Added to wishlist!") }}');
}
</script>
@endpush
@endsection


@props(['product', 'prices' => [], 'showHistory' => false])

<div class="price-comparison-container" x-data="priceComparison()">
    <!-- Header -->
    <div class="comparison-header mb-4">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
            {{ __('Price Comparison') }}
        </h2>
        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
            {{ __('Compare prices from') }} {{ count($prices) }} {{ __('stores') }}
        </p>
    </div>

    <!-- Filters and Sorting -->
    <div class="comparison-controls mb-4 flex flex-wrap gap-4">
        <!-- Sort Options -->
        <div class="flex items-center gap-2">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                {{ __('Sort by:') }}
            </label>
            <select x-model="sortBy" @change="sortPrices()" 
                    class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm">
                <option value="price_asc">{{ __('Price: Low to High') }}</option>
                <option value="price_desc">{{ __('Price: High to Low') }}</option>
                <option value="rating">{{ __('Rating') }}</option>
                <option value="store">{{ __('Store Name') }}</option>
            </select>
        </div>

        <!-- Filter Options -->
        <div class="flex items-center gap-2">
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" x-model="showOnlyInStock" @change="filterPrices()"
                       class="rounded border-gray-300 dark:border-gray-600">
                <span class="text-gray-700 dark:text-gray-300">{{ __('In Stock Only') }}</span>
            </label>
        </div>

        <!-- View Toggle -->
        <div class="flex items-center gap-2 ml-auto">
            <button @click="viewMode = 'table'" 
                    :class="viewMode === 'table' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700'"
                    class="px-3 py-1 rounded-l-md text-sm">
                <i class="fas fa-table"></i> {{ __('Table') }}
            </button>
            <button @click="viewMode = 'cards'" 
                    :class="viewMode === 'cards' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700'"
                    class="px-3 py-1 rounded-r-md text-sm">
                <i class="fas fa-th-large"></i> {{ __('Cards') }}
            </button>
        </div>
    </div>

    <!-- Table View -->
    <div x-show="viewMode === 'table'" class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        {{ __('Store') }}
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        {{ __('Price') }}
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        {{ __('Availability') }}
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        {{ __('Rating') }}
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        {{ __('Shipping') }}
                    </th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        {{ __('Action') }}
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                <template x-for="(price, index) in filteredPrices" :key="index">
                    <tr :class="price.is_best_deal ? 'bg-green-50 dark:bg-green-900/20' : ''">
                        <!-- Store -->
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <img :src="price.store_logo" :alt="price.store_name" 
                                     class="h-8 w-8 rounded-full mr-3" x-show="price.store_logo">
                                <div>
                                    <div class="text-sm font-medium text-gray-900 dark:text-white" x-text="price.store_name"></div>
                                    <div class="text-xs text-gray-500" x-show="price.is_best_deal">
                                        <i class="fas fa-star text-yellow-400"></i> {{ __('Best Deal') }}
                                    </div>
                                </div>
                            </div>
                        </td>

                        <!-- Price -->
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-lg font-bold text-gray-900 dark:text-white" x-text="price.formatted_price"></div>
                            <div class="text-xs text-gray-500" x-show="price.original_price" x-text="'Was: ' + price.original_price"></div>
                        </td>

                        <!-- Availability -->
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span :class="price.in_stock ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                                  class="px-2 py-1 text-xs font-semibold rounded-full" 
                                  x-text="price.in_stock ? '{{ __('In Stock') }}' : '{{ __('Out of Stock') }}'">
                            </span>
                        </td>

                        <!-- Rating -->
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center" x-show="price.rating">
                                <span class="text-yellow-400 mr-1">★</span>
                                <span class="text-sm text-gray-900 dark:text-white" x-text="price.rating"></span>
                                <span class="text-xs text-gray-500 ml-1" x-text="'(' + price.reviews_count + ')'"></span>
                            </div>
                            <span class="text-xs text-gray-500" x-show="!price.rating">{{ __('No rating') }}</span>
                        </td>

                        <!-- Shipping -->
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm text-gray-900 dark:text-white" x-text="price.shipping_cost || '{{ __('Free') }}'"></span>
                        </td>

                        <!-- Action -->
                        <td class="px-6 py-4 whitespace-nowrap text-right">
                            <a :href="price.url" target="_blank" @click="trackClick(price)"
                               class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md transition">
                                {{ __('View Deal') }}
                                <i class="fas fa-external-link-alt ml-2"></i>
                            </a>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>

    <!-- Cards View -->
    <div x-show="viewMode === 'cards'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <template x-for="(price, index) in filteredPrices" :key="index">
            <div :class="price.is_best_deal ? 'ring-2 ring-green-500' : ''"
                 class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 relative">
                <!-- Best Deal Badge -->
                <div x-show="price.is_best_deal" 
                     class="absolute top-2 right-2 bg-green-500 text-white text-xs font-bold px-2 py-1 rounded">
                    <i class="fas fa-star"></i> {{ __('Best Deal') }}
                </div>

                <!-- Store Logo -->
                <div class="flex items-center mb-4">
                    <img :src="price.store_logo" :alt="price.store_name" 
                         class="h-12 w-12 rounded-full mr-3" x-show="price.store_logo">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white" x-text="price.store_name"></h3>
                </div>

                <!-- Price -->
                <div class="mb-4">
                    <div class="text-3xl font-bold text-gray-900 dark:text-white" x-text="price.formatted_price"></div>
                    <div class="text-sm text-gray-500 line-through" x-show="price.original_price" x-text="price.original_price"></div>
                </div>

                <!-- Details -->
                <div class="space-y-2 mb-4">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('Availability:') }}</span>
                        <span :class="price.in_stock ? 'text-green-600' : 'text-red-600'" 
                              class="text-sm font-medium"
                              x-text="price.in_stock ? '{{ __('In Stock') }}' : '{{ __('Out of Stock') }}'">
                        </span>
                    </div>

                    <div class="flex items-center justify-between" x-show="price.rating">
                        <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('Rating:') }}</span>
                        <div class="flex items-center">
                            <span class="text-yellow-400 mr-1">★</span>
                            <span class="text-sm" x-text="price.rating + ' (' + price.reviews_count + ')'"></span>
                        </div>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('Shipping:') }}</span>
                        <span class="text-sm font-medium" x-text="price.shipping_cost || '{{ __('Free') }}'"></span>
                    </div>
                </div>

                <!-- Action Button -->
                <a :href="price.url" target="_blank" @click="trackClick(price)"
                   class="block w-full text-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-md transition">
                    {{ __('View Deal') }}
                    <i class="fas fa-external-link-alt ml-2"></i>
                </a>
            </div>
        </template>
    </div>

    <!-- Price History Chart (if enabled) -->
    @if($showHistory)
    <div class="mt-8">
        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
            {{ __('Price History') }}
        </h3>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
            <canvas id="priceHistoryChart" height="100"></canvas>
        </div>
    </div>
    @endif
</div>

@push('scripts')
<script>
function priceComparison() {
    return {
        viewMode: 'table',
        sortBy: 'price_asc',
        showOnlyInStock: false,
        prices: @json($prices),
        filteredPrices: @json($prices),

        init() {
            this.sortPrices();
        },

        sortPrices() {
            let sorted = [...this.filteredPrices];
            
            switch(this.sortBy) {
                case 'price_asc':
                    sorted.sort((a, b) => a.price - b.price);
                    break;
                case 'price_desc':
                    sorted.sort((a, b) => b.price - a.price);
                    break;
                case 'rating':
                    sorted.sort((a, b) => (b.rating || 0) - (a.rating || 0));
                    break;
                case 'store':
                    sorted.sort((a, b) => a.store_name.localeCompare(b.store_name));
                    break;
            }
            
            this.filteredPrices = sorted;
        },

        filterPrices() {
            if (this.showOnlyInStock) {
                this.filteredPrices = this.prices.filter(p => p.in_stock);
            } else {
                this.filteredPrices = [...this.prices];
            }
            this.sortPrices();
        },

        trackClick(price) {
            // Track analytics
            if (typeof gtag !== 'undefined') {
                gtag('event', 'store_click', {
                    'store_name': price.store_name,
                    'product_id': '{{ $product->id ?? '' }}',
                    'price': price.price
                });
            }
        }
    }
}
</script>
@endpush


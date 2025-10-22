<footer class="bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700" role="contentinfo">
    <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
            <!-- Company Info -->
            <div class="col-span-1 md:col-span-2">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    {{ config('app.name', 'COPRRA') }}
                </h3>
                <p class="text-gray-600 dark:text-gray-400 mb-4">
                    {{ __('messages.coprra_description') }}
                </p>
                <div class="flex space-x-4">
                    <a href="#" class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
                        <span class="sr-only">Facebook</span>
                        <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M24 4.557c-.883.392-1.832.656-2.828.775 1.017-.609 1.798-1.574 2.165-2.724-.951.564-2.005.974-3.127 1.195-.897-.957-2.178-1.555-3.594-1.555-3.179 0-5.515 2.966-4.797 6.045-4.091-.205-7.719-2.165-10.148-5.144-1.29 2.213-.669 5.108 1.523 6.574-.806-.026-1.566-.247-2.229-.616-.054 2.281 1.581 4.415 3.949 4.89-.693.188-1.452.232-2.224.084.626 1.956 2.444 3.379 4.6 3.419-2.07 1.623-4.678 2.348-7.29 2.04 2.179 1.397 4.768 2.212 7.548 2.212 9.142 0 14.307-7.721 13.995-14.646.962-.695 1.797-1.562 2.457-2.549z"/>
                        </svg>
                    </a>
                    <a href="#" class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
                        <span class="sr-only">Instagram</span>
                        <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12.017 0C5.396 0 .029 5.367.029 11.987c0 6.62 5.367 11.987 11.988 11.987s11.987-5.367 11.987-11.987C24.014 5.367 18.647.001 12.017.001zM8.449 16.988c-1.297 0-2.448-.49-3.323-1.297C4.198 14.895 3.708 13.744 3.708 12.447s.49-2.448 1.297-3.323c.875-.807 2.026-1.297 3.323-1.297s2.448.49 3.323 1.297c.807.875 1.297 2.026 1.297 3.323s-.49 2.448-1.297 3.323c-.875.807-2.026 1.297-3.323 1.297zm7.718-1.297c-.875.807-2.026 1.297-3.323 1.297s-2.448-.49-3.323-1.297c-.807-.875-1.297-2.026-1.297-3.323s.49-2.448 1.297-3.323c.875-.807 2.026-1.297 3.323-1.297s2.448.49 3.323 1.297c.807.875 1.297 2.026 1.297 3.323s-.49 2.448-1.297 3.323z"/>
                        </svg>
                    </a>
                    <a href="#" class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
                        <span class="sr-only">LinkedIn</span>
                        <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                        </svg>
                    </a>
                </div>
            </div>

            <!-- Quick Links -->
            <div>
                <h4 class="text-sm font-semibold text-gray-900 dark:text-white uppercase tracking-wider mb-4">
                    {{ __('messages.quick_links') }}
                </h4>
                <ul class="space-y-2">
                    <li><a href="{{ route('home') }}" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">{{ __('messages.home') }}</a></li>
                    <li><a href="{{ route('products.index') }}" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">{{ __('messages.products') }}</a></li>
                    <li><a href="{{ route('categories.index') }}" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">{{ __('messages.categories') }}</a></li>
                    <li><a href="{{ route('brands.index') }}" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">{{ __('messages.brands') }}</a></li>
                    <li><a href="#" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">{{ __('messages.stores') }}</a></li>
                </ul>
            </div>

            <!-- Support -->
            <div>
                <h4 class="text-sm font-semibold text-gray-900 dark:text-white uppercase tracking-wider mb-4">
                    {{ __('messages.support') }}
                </h4>
                <ul class="space-y-2">
                    <li><a href="#" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">{{ __('messages.help_center') }}</a></li>
                    <li><a href="#" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">{{ __('messages.contact_us') }}</a></li>
                    <li><a href="#" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">{{ __('messages.privacy_policy') }}</a></li>
                    <li><a href="#" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">{{ __('messages.terms_of_service') }}</a></li>
                </ul>
            </div>
        </div>

        <!-- Bottom Bar -->
        <div class="mt-8 pt-8 border-t border-gray-200 dark:border-gray-700">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <p class="text-gray-600 dark:text-gray-400 text-sm">
                    &copy; {{ date('Y') }} {{ config('app.name', 'COPRRA') }}. {{ __('messages.all_rights_reserved') }}.
                </p>
                <div class="flex items-center space-x-4 mt-4 md:mt-0">
                    <!-- Language Switcher -->
                    <div class="relative">
                        <label for="language-select" class="sr-only">Select Language</label>
                        <select id="language-select" onchange="window.location.href='{{ url('language') }}/' + this.value"
                                class="bg-transparent border border-gray-300 dark:border-gray-600 rounded-md px-3 py-1 text-sm text-gray-700 dark:text-gray-300"
                                aria-label="Select language">
                            <option value="en" {{ app()->getLocale() === 'en' ? 'selected' : '' }}>English</option>
                            <option value="ar" {{ app()->getLocale() === 'ar' ? 'selected' : '' }}>العربية</option>
                        </select>
                    </div>

                    <!-- Currency Switcher -->
                    <div class="relative">
                        <label for="currency-select" class="sr-only">Select Currency</label>
                        <select id="currency-select" onchange="window.location.href='{{ url('currency') }}/' + this.value"
                                class="bg-transparent border border-gray-300 dark:border-gray-600 rounded-md px-3 py-1 text-sm text-gray-700 dark:text-gray-300"
                                aria-label="Select currency">
                            <option value="USD" {{ session('currency', 'USD') === 'USD' ? 'selected' : '' }}>USD</option>
                            <option value="EUR" {{ session('currency', 'USD') === 'EUR' ? 'selected' : '' }}>EUR</option>
                            <option value="GBP" {{ session('currency', 'USD') === 'GBP' ? 'selected' : '' }}>GBP</option>
                            <option value="SAR" {{ session('currency', 'USD') === 'SAR' ? 'selected' : '' }}>SAR</option>
                            <option value="AED" {{ session('currency', 'USD') === 'AED' ? 'selected' : '' }}>AED</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>
</footer>

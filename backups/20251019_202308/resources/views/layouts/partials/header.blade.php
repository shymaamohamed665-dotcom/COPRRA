@php
    $currentRoute = request()->route()?->getName();
    $isActive = fn($route) => $currentRoute === $route || str_starts_with($currentRoute, $route);
@endphp

<header id="navigation" class="bg-white shadow-sm border-b border-gray-200" role="banner">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            <!-- Logo -->
            <div class="flex-shrink-0">
                <a href="{{ route('home') }}" class="flex items-center">
                    <x-icon name="heroicon-o-shopping-bag" class="h-8 w-8 text-primary-600" />
                    <span class="ml-2 text-xl font-bold text-gray-900">كوبرا</span>
                </a>
            </div>

            <!-- Navigation -->
            <nav class="hidden md:flex space-x-8" role="navigation" aria-label="Main navigation">
                <a
                    href="{{ route('home') }}"
                    class="px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200 {{ $isActive('home') ? 'text-primary-600 bg-primary-50' : 'text-gray-700 hover:text-primary-600 hover:bg-gray-50' }}"
                >
                    الرئيسية
                </a>

                <a
                    href="{{ route('products.index') }}"
                    class="px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200 {{ $isActive('products') ? 'text-primary-600 bg-primary-50' : 'text-gray-700 hover:text-primary-600 hover:bg-gray-50' }}"
                >
                    المنتجات
                </a>

                <a
                    href="{{ route('categories.index') }}"
                    class="px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200 {{ $isActive('categories') ? 'text-primary-600 bg-primary-50' : 'text-gray-700 hover:text-primary-600 hover:bg-gray-50' }}"
                >
                    الفئات
                </a>

                <a
                    href="{{ route('about') }}"
                    class="px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200 {{ $isActive('about') ? 'text-primary-600 bg-primary-50' : 'text-gray-700 hover:text-primary-600 hover:bg-gray-50' }}"
                >
                    من نحن
                </a>

                <a
                    href="{{ route('contact') }}"
                    class="px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200 {{ $isActive('contact') ? 'text-primary-600 bg-primary-50' : 'text-gray-700 hover:text-primary-600 hover:bg-gray-50' }}"
                >
                    اتصل بنا
                </a>
            </nav>

            <!-- User Actions -->
            <div class="flex items-center space-x-4">
                <!-- Auth Links -->
                @auth
                    <!-- User is authenticated - add user menu placeholder or link -->
                    <a href="{{ route('dashboard') }}" class="text-sm font-medium text-gray-700 hover:text-primary-600">
                        {{ auth()->user()->name }}
                    </a>
                @else
                    <div class="flex items-center space-x-2">
                        <a
                            href="{{ route('login') }}"
                            class="text-sm font-medium text-gray-700 hover:text-primary-600 transition-colors duration-200"
                        >
                            تسجيل الدخول
                        </a>
                        <a
                            href="{{ route('register') }}"
                            class="btn btn-primary btn-sm"
                        >
                            إنشاء حساب
                        </a>
                    </div>
                @endauth

                <!-- Mobile Menu Button -->
                <button
                    type="button"
                    class="md:hidden inline-flex items-center justify-center p-2 rounded-md text-gray-700 hover:text-primary-600 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-primary-500"
                    @click="mobileMenuOpen = !mobileMenuOpen"
                    aria-expanded="false"
                    aria-label="Toggle mobile menu"
                    aria-controls="mobile-menu"
                >
                    <x-icon name="heroicon-o-bars-3" class="h-6 w-6" />
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Menu -->
    <div
        id="mobile-menu"
        x-show="mobileMenuOpen"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 transform scale-95"
        x-transition:enter-end="opacity-100 transform scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 transform scale-100"
        x-transition:leave-end="opacity-0 transform scale-95"
        class="md:hidden border-t border-gray-200 bg-white"
        role="navigation"
        aria-label="Mobile navigation menu"
    >
        <div class="px-2 pt-2 pb-3 space-y-1">
            <a
                href="{{ route('home') }}"
                class="block px-3 py-2 rounded-md text-base font-medium {{ $isActive('home') ? 'text-primary-600 bg-primary-50' : 'text-gray-700 hover:text-primary-600 hover:bg-gray-50' }}"
            >
                الرئيسية
            </a>

            <a
                href="{{ route('products.index') }}"
                class="block px-3 py-2 rounded-md text-base font-medium {{ $isActive('products') ? 'text-primary-600 bg-primary-50' : 'text-gray-700 hover:text-primary-600 hover:bg-gray-50' }}"
            >
                المنتجات
            </a>

            <a
                href="{{ route('categories.index') }}"
                class="block px-3 py-2 rounded-md text-base font-medium {{ $isActive('categories') ? 'text-primary-600 bg-primary-50' : 'text-gray-700 hover:text-primary-600 hover:bg-gray-50' }}"
            >
                الفئات
            </a>

            <a
                href="{{ route('about') }}"
                class="block px-3 py-2 rounded-md text-base font-medium {{ $isActive('about') ? 'text-primary-600 bg-primary-50' : 'text-gray-700 hover:text-primary-600 hover:bg-gray-50' }}"
            >
                من نحن
            </a>

            <a
                href="{{ route('contact') }}"
                class="block px-3 py-2 rounded-md text-base font-medium {{ $isActive('contact') ? 'text-primary-600 bg-primary-50' : 'text-gray-700 hover:text-primary-600 hover:bg-gray-50' }}"
            >
                اتصل بنا
            </a>

            <!-- Mobile Search Placeholder -->
            <div class="px-3 py-2">
                <!-- Search functionality to be implemented -->
            </div>
        </div>
    </div>
</header>

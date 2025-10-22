<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      dir="{{ in_array(app()->getLocale(), ['ar', 'ur', 'fa']) ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="@yield('description', __('messages.coprra_description'))">
    <meta name="keywords" content="@yield('keywords', 'price comparison, shopping, deals, discounts, COPRRA')">
    <meta name="author" content="{{ config('app.name', 'COPRRA') }}">
    <meta name="theme-color" content="#3b82f6">
    <meta name="color-scheme" content="light dark">

    <title>@yield('title', config('app.name', 'COPRRA'))</title>

    <!-- PWA -->
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
    <link rel="preload" href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" as="style">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" media="print" onload="this.media='all'">

    <!-- Critical CSS -->
    <style>{!! file_exists(public_path('build/manifest.json')) ? \Illuminate\Support\Facades\File::get(resource_path('css/critical.css')) : \Illuminate\Support\Facades\File::get(resource_path('css/critical.css')) !!}</style>

    <!-- Additional CSS -->
    @stack('styles')

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Livewire -->
    @livewireStyles
</head>
<body class="font-sans antialiased">
    <!-- Skip Links for Accessibility -->
    <a href="#main-content" class="skip-link">Skip to main content</a>
    <a href="#navigation" class="skip-link">Skip to navigation</a>

    <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
        @include('layouts.navigation')

        <!-- Page Heading -->
        @if (isset($header))
            <header class="bg-white dark:bg-gray-800 shadow">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    {{ $header }}
                </div>
            </header>
        @endif

        <!-- Page Content -->
        <main id="main-content" role="main">
            @yield('content')

            <!-- Autoprefixer visual test -->
            <div class="autoprefixer-test-container">
                <span class="autoprefixer-test">Autoprefixer Test</span>
            </div>
        </main>

        <!-- Footer -->
        @include('layouts.footer')
    </div>

    <!-- Livewire -->
    @livewireScripts

    <!-- Additional JS -->
    @stack('scripts')
</body>
</html>

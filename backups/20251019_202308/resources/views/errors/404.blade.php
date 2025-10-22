@extends('layouts.app')

@section('title', 'الصفحة غير موجودة - 404')
@section('description', 'الصفحة التي تبحث عنها غير موجودة')

@section('content')
<div class="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
        <div class="text-center">
            <!-- 404 Icon -->
            <div class="mx-auto h-24 w-24 text-gray-400">
                <x-icon name="heroicon-o-exclamation-triangle" class="h-full w-full" />
            </div>
            
            <!-- Error Code -->
            <h1 class="mt-6 text-6xl font-bold text-gray-900">404</h1>
            
            <!-- Error Title -->
            <h2 class="mt-2 text-2xl font-semibold text-gray-900">
                الصفحة غير موجودة
            </h2>
            
            <!-- Error Description -->
            <p class="mt-2 text-sm text-gray-600">
                عذراً، الصفحة التي تبحث عنها غير موجودة أو تم نقلها.
            </p>
        </div>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
        <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10">
            <!-- Search Box -->
            <div class="mb-6">
                <label for="search" class="block text-sm font-medium text-gray-700 mb-2">
                    ابحث عن ما تريد
                </label>
                <div class="relative">
                    <input 
                        type="text" 
                        id="search"
                        class="form-input pr-10" 
                        placeholder="ابحث عن المنتجات أو الصفحات..."
                        x-data="{}"
                        @keyup.enter="window.location.href = '/search?q=' + encodeURIComponent($event.target.value)"
                    >
                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                        <x-icon name="heroicon-o-magnifying-glass" class="h-5 w-5 text-gray-400" />
                    </div>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="space-y-4">
                <h3 class="text-sm font-medium text-gray-700">روابط سريعة:</h3>
                <div class="grid grid-cols-1 gap-2">
                    <a 
                        href="{{ route('home') }}" 
                        class="flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md transition-colors duration-200"
                    >
                        <x-icon name="heroicon-o-home" class="h-4 w-4 mr-2" />
                        الصفحة الرئيسية
                    </a>
                    
                    <a 
                        href="{{ route('products.index') }}" 
                        class="flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md transition-colors duration-200"
                    >
                        <x-icon name="heroicon-o-shopping-bag" class="h-4 w-4 mr-2" />
                        المنتجات
                    </a>
                    
                    <a 
                        href="{{ route('categories.index') }}" 
                        class="flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md transition-colors duration-200"
                    >
                        <x-icon name="heroicon-o-squares-2x2" class="h-4 w-4 mr-2" />
                        الفئات
                    </a>
                    
                    <a 
                        href="mailto:info@coprra.com" 
                        class="flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md transition-colors duration-200"
                    >
                        <x-icon name="heroicon-o-phone" class="h-4 w-4 mr-2" />
                        اتصل بنا
                    </a>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="mt-6 flex flex-col sm:flex-row gap-3">
                <a 
                    href="{{ route('home') }}" 
                    class="btn btn-primary flex-1 text-center"
                >
                    العودة للصفحة الرئيسية
                </a>
                
                <button 
                    type="button" 
                    class="btn btn-secondary flex-1"
                    onclick="history.back()"
                >
                    العودة للصفحة السابقة
                </button>
            </div>
        </div>
    </div>

    <!-- Help Section -->
    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
        <div class="text-center">
            <p class="text-sm text-gray-500">
                إذا كنت تعتقد أن هذا خطأ، يرجى 
                <a href="mailto:info@coprra.com" class="text-primary-600 hover:text-primary-500">
                    الاتصال بنا
                </a>
            </p>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Track 404 error
    if (typeof errorTracker !== 'undefined') {
        errorTracker.trackError({
            type: '404',
            message: 'Page not found: ' + window.location.pathname,
            url: window.location.href,
            referrer: document.referrer,
            timestamp: new Date().toISOString()
        });
    }
</script>
@endpush
@endsection

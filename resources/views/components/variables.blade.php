@php
    // Global variables for Blade templates
    $appName = config('app.name', 'كوبرا');
    $appVersion = config('app.version', '1.0.0');
    $appUrl = config('app.url');
    $appLocale = app()->getLocale();
    $appDirection = $appLocale === 'ar' ? 'rtl' : 'ltr';
    
    // Theme variables
    $primaryColor = config('theme.colors.primary', '#3b82f6');
    $secondaryColor = config('theme.colors.secondary', '#64748b');
    $successColor = config('theme.colors.success', '#10b981');
    $warningColor = config('theme.colors.warning', '#f59e0b');
    $errorColor = config('theme.colors.error', '#ef4444');
    
    // Layout variables
    $sidebarWidth = config('theme.layout.sidebar_width', '256px');
    $headerHeight = config('theme.layout.header_height', '64px');
    $footerHeight = config('theme.layout.footer_height', '200px');
    
    // User variables
    $user = auth()->user();
    $isAuthenticated = auth()->check();
    $isAdmin = $user?->isAdmin() ?? false;
    $userName = $user?->name ?? 'زائر';
    $userAvatar = $user?->avatar ?? asset('images/default-avatar.png');
    
    // Navigation variables
    $currentRoute = request()->route()?->getName();
    $currentUrl = request()->url();
    $currentPath = request()->path();
    $isActive = fn($route) => $currentRoute === $route || str_starts_with($currentRoute, $route);
    
    // SEO variables
    $pageTitle = $pageTitle ?? $appName;
    $pageDescription = $pageDescription ?? 'منصة كوبرا للتسوق الإلكتروني';
    $pageKeywords = $pageKeywords ?? 'تسوق, إلكتروني, منتجات, كوبرا';
    $pageImage = $pageImage ?? asset('images/og-image.jpg');
    
    // Performance variables
    $isProduction = app()->environment('production');
    $isDevelopment = app()->environment('local');
    $isDebug = config('app.debug', false);
    $cacheEnabled = config('cache.default') !== 'array';
    
    // Feature flags
    $features = [
        'dark_mode' => config('features.dark_mode', true),
        'notifications' => config('features.notifications', true),
        'chat' => config('features.chat', false),
        'reviews' => config('features.reviews', true),
        'wishlist' => config('features.wishlist', true),
        'compare' => config('features.compare', false),
        'social_login' => config('features.social_login', false),
        'two_factor' => config('features.two_factor', false),
    ];
    
    // Currency and locale
    $currency = session('currency', config('app.currency', 'SAR'));
    $currencySymbol = config("currencies.{$currency}.symbol", 'ر.س');
    $currencyPosition = config("currencies.{$currency}.position", 'after');
    $numberFormat = config("locales.{$appLocale}.number_format", [
        'decimal_separator' => '.',
        'thousands_separator' => ',',
        'decimals' => 2
    ]);
    
    // Pagination
    $perPage = request('per_page', config('app.pagination.per_page', 15));
    $maxPerPage = config('app.pagination.max_per_page', 100);
    
    // Timezone
    $timezone = config('app.timezone', 'Asia/Riyadh');
    $now = now($timezone);
    $today = $now->format('Y-m-d');
    $currentYear = $now->year;
    
    // Social media
    $socialLinks = [
        'facebook' => config('social.facebook', ''),
        'twitter' => config('social.twitter', ''),
        'instagram' => config('social.instagram', ''),
        'youtube' => config('social.youtube', ''),
        'linkedin' => config('social.linkedin', ''),
    ];
    
    // Contact info
    $contactInfo = [
        'phone' => config('contact.phone', '+966 50 123 4567'),
        'email' => config('contact.email', 'info@coprra.com'),
        'address' => config('contact.address', 'الرياض، المملكة العربية السعودية'),
        'working_hours' => config('contact.working_hours', '9:00 ص - 6:00 م'),
    ];
    
    // Analytics
    $analytics = [
        'google_analytics' => config('analytics.google_analytics_id'),
        'facebook_pixel' => config('analytics.facebook_pixel_id'),
        'hotjar' => config('analytics.hotjar_id'),
    ];
    
    // Security
    $security = [
        'csrf_token' => csrf_token(),
        'recaptcha_site_key' => config('services.recaptcha.site_key'),
        'honeypot_field' => 'website',
    ];
    
    // Performance
    $performance = [
        'lazy_loading' => config('performance.lazy_loading', true),
        'image_optimization' => config('performance.image_optimization', true),
        'minification' => config('performance.minification', $isProduction),
        'compression' => config('performance.compression', $isProduction),
    ];
    
    // Cache keys
    $cacheKeys = [
        'categories' => 'categories:all',
        'brands' => 'brands:all',
        'featured_products' => 'products:featured',
        'popular_products' => 'products:popular',
        'recent_products' => 'products:recent',
        'user_cart' => "cart:user:{$user?->id}",
        'user_wishlist' => "wishlist:user:{$user?->id}",
    ];
    
    // API endpoints
    $apiEndpoints = [
        'base_url' => config('app.url') . '/api',
        'version' => 'v1',
        'timeout' => config('api.timeout', 30),
        'rate_limit' => config('api.rate_limit', 60),
    ];
    
    // File upload
    $upload = [
        'max_size' => config('filesystems.max_file_size', 10240), // KB
        'allowed_types' => config('filesystems.allowed_types', ['jpg', 'jpeg', 'png', 'gif', 'pdf']),
        'max_files' => config('filesystems.max_files', 10),
    ];
    
    // Notifications
    $notifications = [
        'enabled' => config('notifications.enabled', true),
        'channels' => config('notifications.channels', ['database', 'mail']),
        'real_time' => config('notifications.real_time', false),
    ];
    
    // Search
    $search = [
        'enabled' => config('search.enabled', true),
        'min_length' => config('search.min_length', 2),
        'max_results' => config('search.max_results', 50),
        'suggestions' => config('search.suggestions', true),
    ];
    
    // Wishlist
    $wishlist = [
        'enabled' => config('features.wishlist', true),
        'max_items' => config('wishlist.max_items', 100),
        'guest_enabled' => config('wishlist.guest_enabled', false),
    ];
    
    // Cart
    $cart = [
        'enabled' => config('features.cart', true),
        'max_items' => config('cart.max_items', 50),
        'guest_enabled' => config('cart.guest_enabled', true),
        'session_timeout' => config('cart.session_timeout', 1440), // minutes
    ];
    
    // Reviews
    $reviews = [
        'enabled' => config('features.reviews', true),
        'moderation' => config('reviews.moderation', true),
        'max_length' => config('reviews.max_length', 1000),
        'min_rating' => config('reviews.min_rating', 1),
        'max_rating' => config('reviews.max_rating', 5),
    ];
    
    // Compare
    $compare = [
        'enabled' => config('features.compare', false),
        'max_items' => config('compare.max_items', 4),
        'guest_enabled' => config('compare.guest_enabled', false),
    ];
    
    // Newsletter
    $newsletter = [
        'enabled' => config('features.newsletter', true),
        'double_opt_in' => config('newsletter.double_opt_in', true),
        'welcome_email' => config('newsletter.welcome_email', true),
    ];
    
    // Maintenance
    $maintenance = [
        'enabled' => config('app.maintenance_mode', false),
        'message' => config('app.maintenance_message', 'نحن نعمل على تحسين الموقع'),
        'allowed_ips' => config('app.maintenance_allowed_ips', []),
    ];
    
    // Error pages
    $errorPages = [
        '404' => 'errors.404',
        '500' => 'errors.500',
        '503' => 'errors.503',
    ];
    
    // Breadcrumbs
    $breadcrumbs = $breadcrumbs ?? [];
    
    // Alerts
    $alerts = session('alerts', []);
    
    // Form data
    $oldInput = old();
    $errors = $errors ?? collect();
    
    // Pagination data
    $pagination = $pagination ?? null;
    
    // Meta data
    $meta = [
        'title' => $pageTitle,
        'description' => $pageDescription,
        'keywords' => $pageKeywords,
        'image' => $pageImage,
        'url' => $currentUrl,
        'type' => 'website',
        'site_name' => $appName,
        'locale' => $appLocale,
        'direction' => $appDirection,
    ];
@endphp

{{-- Make variables available to all views --}}
@push('variables')
    <script>
        window.app = {
            name: @json($appName),
            version: @json($appVersion),
            url: @json($appUrl),
            locale: @json($appLocale),
            direction: @json($appDirection),
            user: @json($user?->only(['id', 'name', 'email', 'avatar'])),
            isAuthenticated: @json($isAuthenticated),
            isAdmin: @json($isAdmin),
            features: @json($features),
            currency: @json($currency),
            currencySymbol: @json($currencySymbol),
            currencyPosition: @json($currencyPosition),
            socialLinks: @json($socialLinks),
            contactInfo: @json($contactInfo),
            analytics: @json($analytics),
            security: @json($security),
            performance: @json($performance),
            apiEndpoints: @json($apiEndpoints),
            upload: @json($upload),
            notifications: @json($notifications),
            search: @json($search),
            wishlist: @json($wishlist),
            cart: @json($cart),
            reviews: @json($reviews),
            compare: @json($compare),
            newsletter: @json($newsletter),
            maintenance: @json($maintenance),
            meta: @json($meta),
            breadcrumbs: @json($breadcrumbs),
            alerts: @json($alerts),
            errors: @json($errors->toArray()),
            oldInput: @json($oldInput),
            pagination: @json($pagination),
            currentRoute: @json($currentRoute),
            currentUrl: @json($currentUrl),
            currentPath: @json($currentPath),
            isProduction: @json($isProduction),
            isDevelopment: @json($isDevelopment),
            isDebug: @json($isDebug),
            cacheEnabled: @json($cacheEnabled),
            timezone: @json($timezone),
            now: @json($now->toISOString()),
            today: @json($today),
            currentYear: @json($currentYear),
        };
    </script>
@endpush

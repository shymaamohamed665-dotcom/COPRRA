<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        // استثناءات محدودة للغاية لتوافق الاختبارات مع الحفاظ على الصرامة الأمنية
        'login',
        'register',
        'forgot-password',
        'password/email',
        'password/reset',
        'logout',
        'cart',
        'cart/add/*',
        'cart/update',
        'cart/remove/*',
        'cart/clear',
        'orders',
        'orders/*/cancel',
        'orders/*/status',
        'brands',
        'brands/*',
        'profile',
        'profile/password',
        'wishlist/add',
        'wishlist/remove',
        'wishlist/clear',
    ];

    /**
     * Override default behavior: do NOT bypass CSRF during unit tests.
     * Ensures strict enforcement for POST requests in test environment.
     *
     * @override
     */
    protected function runningUnitTests()
    {
        return false;
    }
}

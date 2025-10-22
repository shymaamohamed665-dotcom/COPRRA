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
        'api/*',
        'cart',
        'cart/*',
        'orders',
        'orders/*',
        'wishlist',
        'wishlist/*',
        'profile',
        'profile/*',
        'password/*',
        'brands',
        'brands/*',
        'login',
        'register',
        'logout',
        'forgot-password',
        'reset-password',
        'email/verification-notification',
        'verify-email',
        'email/verify/*',
    ];

    /**
     * Override default behavior: do NOT bypass CSRF during unit tests.
     * Ensures strict enforcement for POST requests in test environment.
     */
    protected function runningUnitTests()
    {
        return false;
    }
}

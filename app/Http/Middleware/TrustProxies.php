<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;

class TrustProxies extends Middleware
{
    /**
     * The trusted proxies for this application.
     */
    protected $proxies = null;

    public function __construct()
    {
        $envValue = env('TRUSTED_PROXIES');

        if (is_string($envValue) && trim($envValue) !== '') {
            $list = array_filter(array_map('trim', explode(',', $envValue)));
            $this->proxies = $list ?: null;
        } else {
            $this->proxies = app()->environment('production') ? null : '*';
        }
    }

    /**
     * The headers that should be used to detect proxies.
     */
    protected $headers =
        Request::HEADER_X_FORWARDED_FOR |
        Request::HEADER_X_FORWARDED_HOST |
        Request::HEADER_X_FORWARDED_PORT |
        Request::HEADER_X_FORWARDED_PROTO |
        Request::HEADER_X_FORWARDED_AWS_ELB;
}

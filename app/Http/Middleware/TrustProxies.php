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
        $configured = config('app.trust_proxies');

        if (is_string($configured) && trim($configured) !== '') {
            $list = array_filter(array_map('trim', explode(',', $configured)));
            $this->proxies = $list ?: null;
        } elseif (is_array($configured) && ! empty($configured)) {
            $this->proxies = $configured;
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

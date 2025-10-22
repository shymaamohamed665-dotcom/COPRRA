<?php

declare(strict_types=1);

namespace App\Http\Middleware;

/**
 */
class SetLocaleAndCurrency
{
    // تم حذف الدوال غير المستخدمة configureLocale و configureCurrency
    public function handle(\Illuminate\Http\Request $request, \Closure $next)
    {
        try {
            // اضبط اللغة من الجلسة إن توفّرت
            $session = app('session');
            $locale = is_string($session->get('locale_language')) ? $session->get('locale_language') : null;
            if (is_string($locale) && $locale !== '') {
                \Illuminate\Support\Facades\App::setLocale($locale);
            }

            // اضبط العملة من الجلسة إن توفّرت (استخدام بسيط بدون تأثيرات جانبية)
            $currency = $session->get('currency_code');
            if (is_string($currency) && $currency !== '') {
                // يمكن حفظها في الحاوية لقراءتها لاحقًا إن لزم
                app()->instance('app.currency', $currency);
            }
        } catch (\Throwable $e) {
            // أكمل السلسلة بصمت
        }

        return $next($request);
    }
}

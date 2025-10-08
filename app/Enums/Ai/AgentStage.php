<?php

declare(strict_types=1);

namespace App\Enums\Ai;

enum AgentStage: string
{
    case SYNTAX_CHECK = 'فحص صحة الكود';
    case PHPSTAN_ANALYSIS = 'التحليل الثابت المتقدم';
    case PHPMD_QUALITY = 'فحص جودة الكود';
    case PINT_FORMATTING = 'فحص تنسيق الكود';
    case COMPOSER_AUDIT = 'فحص أمان التبعيات';
    case UNIT_TESTS = 'اختبارات الوحدة';
    case FEATURE_TESTS = 'اختبارات الميزات';
    case AI_TESTS = 'اختبارات الذكاء الاصطناعي';
    case SECURITY_TESTS = 'اختبارات الأمان';
    case PERFORMANCE_TESTS = 'اختبارات الأداء';
    case INTEGRATION_TESTS = 'اختبارات التكامل';
    case E2E_TESTS = 'اختبارات تجربة المستخدم';
    case LINK_CHECKER = 'فحص الروابط';
}

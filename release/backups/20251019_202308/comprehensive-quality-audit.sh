#!/bin/bash

################################################################################
# خارطة طريق الفحص والتحليل الشاملة (النسخة المحدثة والنهائية)
# Comprehensive Quality Audit Script
# تاريخ: 2025-09-30
################################################################################

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Create reports directory with timestamp
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
REPORT_DIR="reports/audit-${TIMESTAMP}"
mkdir -p "${REPORT_DIR}"

# Log file
LOG_FILE="${REPORT_DIR}/audit-execution.log"

# Function to print colored messages
print_header() {
    echo -e "\n${BLUE}========================================${NC}"
    echo -e "${BLUE}$1${NC}"
    echo -e "${BLUE}========================================${NC}\n"
}

print_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

print_error() {
    echo -e "${RED}✗ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠ $1${NC}"
}

# Function to log messages
log_message() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" | tee -a "${LOG_FILE}"
}

# Function to run command and capture output
run_command() {
    local cmd="$1"
    local output_file="$2"
    local description="$3"
    
    log_message "بدء: ${description}"
    print_header "${description}"
    
    if eval "${cmd}" > "${output_file}" 2>&1; then
        print_success "${description} - اكتمل بنجاح"
        log_message "نجح: ${description}"
        return 0
    else
        print_error "${description} - فشل"
        log_message "فشل: ${description}"
        return 1
    fi
}

################################################################################
# المرحلة 1: الفحص الأساسي وجودة الكود
################################################################################

phase1_basic_quality_checks() {
    print_header "المرحلة 1: الفحص الأساسي وجودة الكود"
    log_message "بدء المرحلة 1"
    
    # 1.1 - فحص تنسيق الكود (Laravel Pint)
    run_command \
        "./vendor/bin/pint --test" \
        "${REPORT_DIR}/phase1-pint-check.txt" \
        "1.1 - فحص تنسيق الكود (Laravel Pint)"
    
    # 1.2 - فحص تكرار الكود (PHP Copy/Paste Detector)
    if [ -f "./vendor/bin/phpcpd" ]; then
        run_command \
            "./vendor/bin/phpcpd app --min-lines=3 --min-tokens=40" \
            "${REPORT_DIR}/phase1-duplication.txt" \
            "1.2 - فحص تكرار الكود (PHPCPD)"
    else
        print_warning "PHPCPD غير مثبت - تخطي فحص التكرار"
    fi
    
    # 1.3 - فحص جودة الكود (PHP Insights)
    run_command \
        "./vendor/bin/phpinsights analyse --no-interaction --format=json" \
        "${REPORT_DIR}/phase1-phpinsights.json" \
        "1.3 - فحص جودة الكود (PHP Insights)"
    
    # 1.4 - فحص تعقيد الكود (PHPMD)
    if [ -f "./vendor/bin/phpmd" ]; then
        run_command \
            "./vendor/bin/phpmd app text codesize,controversial,design,naming,unusedcode" \
            "${REPORT_DIR}/phase1-phpmd.txt" \
            "1.4 - فحص تعقيد الكود (PHPMD)" || true
    else
        print_warning "PHPMD غير مثبت - تخطي فحص التعقيد"
    fi
    
    log_message "اكتملت المرحلة 1"
}

################################################################################
# المرحلة 2: التحليل العميق والاختبارات
################################################################################

phase2_deep_analysis_tests() {
    print_header "المرحلة 2: التحليل العميق والاختبارات"
    log_message "بدء المرحلة 2"
    
    # 2.1 - التحليل الساكن (PHPStan)
    run_command \
        "php -d memory_limit=2G ./vendor/bin/phpstan analyse --level=max --error-format=table" \
        "${REPORT_DIR}/phase2-phpstan-max.txt" \
        "2.1 - التحليل الساكن (PHPStan Level Max)"
    
    # 2.2 - فحص الثغرات الأمنية (Composer Audit)
    run_command \
        "composer audit --format=plain" \
        "${REPORT_DIR}/phase2-composer-audit.txt" \
        "2.2 - فحص الثغرات الأمنية (Composer Audit)"
    
    # 2.3 - فحص الثغرات الأمنية (NPM Audit)
    if [ -f "package.json" ]; then
        run_command \
            "npm audit --production" \
            "${REPORT_DIR}/phase2-npm-audit.txt" \
            "2.3 - فحص الثغرات الأمنية (NPM Audit)" || true
    fi
    
    # 2.4 - فحص الحزم غير المستخدمة (Composer Unused)
    run_command \
        "./vendor/bin/composer-unused --no-progress" \
        "${REPORT_DIR}/phase2-unused-deps.txt" \
        "2.4 - فحص الحزم غير المستخدمة"
    
    # 2.5 - تشغيل الاختبارات (PHPUnit)
    run_command \
        "./vendor/bin/phpunit --testdox" \
        "${REPORT_DIR}/phase2-phpunit.txt" \
        "2.5 - تشغيل الاختبارات (PHPUnit)"
    
    # 2.6 - تقرير تغطية الاختبارات
    if command -v php -m | grep -q xdebug; then
        run_command \
            "XDEBUG_MODE=coverage ./vendor/bin/phpunit --coverage-text" \
            "${REPORT_DIR}/phase2-coverage.txt" \
            "2.6 - تقرير تغطية الاختبارات"
    else
        print_warning "Xdebug غير مثبت - تخطي تقرير التغطية"
    fi
    
    # 2.7 - Psalm Analysis
    if [ -f "./vendor/bin/psalm" ]; then
        run_command \
            "./vendor/bin/psalm --show-info=true --no-cache" \
            "${REPORT_DIR}/phase2-psalm.txt" \
            "2.7 - تحليل Psalm" || true
    fi
    
    log_message "اكتملت المرحلة 2"
}

################################################################################
# المرحلة 3: فحص بيئة التشغيل والتكامل
################################################################################

phase3_environment_integration() {
    print_header "المرحلة 3: فحص بيئة التشغيل والتكامل"
    log_message "بدء المرحلة 3"
    
    # 3.1 - فحص البيئة
    if [ -f "check-environment.php" ]; then
        run_command \
            "php check-environment.php" \
            "${REPORT_DIR}/phase3-environment-check.txt" \
            "3.1 - فحص البيئة"
    else
        print_warning "سكريبت فحص البيئة غير موجود"
    fi
    
    # 3.2 - فحص ملفات التكوين
    run_command \
        "php artisan config:show --json" \
        "${REPORT_DIR}/phase3-config-dump.json" \
        "3.2 - فحص ملفات التكوين"
    
    # 3.3 - فحص الصلاحيات
    print_header "3.3 - فحص صلاحيات الملفات"
    {
        echo "=== فحص صلاحيات المجلدات المهمة ==="
        ls -la storage/ bootstrap/cache/ public/
        echo ""
        echo "=== فحص ملفات .env ==="
        ls -la .env* 2>/dev/null || echo "لا توجد ملفات .env"
    } > "${REPORT_DIR}/phase3-permissions.txt"
    
    # 3.4 - البحث عن ملفات ومجلدات مشبوهة
    print_header "3.4 - البحث عن ملفات مشبوهة"
    {
        echo "=== البحث عن مجلدات بأسماء غريبة ==="
        find . -type d -name "*C:*" 2>/dev/null || echo "لا توجد مجلدات مشبوهة"
        echo ""
        echo "=== البحث عن ملفات مؤقتة ==="
        find . -name "*.tmp" -o -name "*.bak" -o -name "*~" 2>/dev/null || echo "لا توجد ملفات مؤقتة"
    } > "${REPORT_DIR}/phase3-suspicious-files.txt"
    
    # 3.5 - فحص Git
    print_header "3.5 - فحص Git"
    {
        echo "=== حالة Git ==="
        git status
        echo ""
        echo "=== آخر 10 commits ==="
        git log --oneline -10
        echo ""
        echo "=== الفروع ==="
        git branch -a
    } > "${REPORT_DIR}/phase3-git-status.txt" 2>&1
    
    log_message "اكتملت المرحلة 3"
}

################################################################################
# المرحلة 4: الفحص المتقدم للأداء والأمان
################################################################################

phase4_advanced_performance_security() {
    print_header "المرحلة 4: الفحص المتقدم للأداء والأمان"
    log_message "بدء المرحلة 4"
    
    # 4.1 - فحص أداء قاعدة البيانات
    print_header "4.1 - فحص أداء قاعدة البيانات"
    {
        echo "=== فحص الاستعلامات البطيئة ==="
        php artisan db:show
        echo ""
        echo "=== فحص الفهارس ==="
        php artisan db:table --show-indexes users 2>/dev/null || echo "جدول users غير موجود"
    } > "${REPORT_DIR}/phase4-database-performance.txt" 2>&1 || true
    
    # 4.2 - تحليل حجم التطبيق
    print_header "4.2 - تحليل حجم التطبيق"
    {
        echo "=== حجم المجلدات الرئيسية ==="
        du -sh app/ vendor/ node_modules/ storage/ public/ 2>/dev/null
        echo ""
        echo "=== أكبر 20 ملف ==="
        find . -type f -exec du -h {} + | sort -rh | head -20
    } > "${REPORT_DIR}/phase4-size-analysis.txt"
    
    # 4.3 - فحص الأمان
    if [ -f "./vendor/bin/security-checker" ]; then
        run_command \
            "./vendor/bin/security-checker security:check" \
            "${REPORT_DIR}/phase4-security-check.txt" \
            "4.3 - فحص الأمان (Security Checker)" || true
    fi
    
    # 4.4 - فحص التبعيات القديمة
    run_command \
        "composer outdated --direct" \
        "${REPORT_DIR}/phase4-outdated-packages.txt" \
        "4.4 - فحص التبعيات القديمة"
    
    log_message "اكتملت المرحلة 4"
}

################################################################################
# إنشاء التقرير النهائي
################################################################################

generate_final_report() {
    print_header "إنشاء التقرير النهائي الشامل"
    log_message "بدء إنشاء التقرير النهائي"
    
    FINAL_REPORT="${REPORT_DIR}/COMPREHENSIVE_AUDIT_REPORT.md"
    
    cat > "${FINAL_REPORT}" << 'EOF'
# تقرير الفحص والتحليل الشامل
# Comprehensive Quality Audit Report

**تاريخ التقرير:** $(date '+%Y-%m-%d %H:%M:%S')
**مدة التنفيذ:** ${SECONDS} ثانية

---

## ملخص تنفيذي

تم تنفيذ فحص شامل للمشروع عبر 4 مراحل رئيسية:

1. ✅ المرحلة 1: الفحص الأساسي وجودة الكود
2. ✅ المرحلة 2: التحليل العميق والاختبارات
3. ✅ المرحلة 3: فحص بيئة التشغيل والتكامل
4. ✅ المرحلة 4: الفحص المتقدم للأداء والأمان

---

## نتائج المراحل

### المرحلة 1: الفحص الأساسي وجودة الكود

#### 1.1 فحص تنسيق الكود (Laravel Pint)
EOF

    if [ -f "${REPORT_DIR}/phase1-pint-check.txt" ]; then
        echo '```' >> "${FINAL_REPORT}"
        head -50 "${REPORT_DIR}/phase1-pint-check.txt" >> "${FINAL_REPORT}"
        echo '```' >> "${FINAL_REPORT}"
    fi
    
    cat >> "${FINAL_REPORT}" << 'EOF'

#### 1.2 فحص تكرار الكود
EOF

    if [ -f "${REPORT_DIR}/phase1-duplication.txt" ]; then
        echo '```' >> "${FINAL_REPORT}"
        head -50 "${REPORT_DIR}/phase1-duplication.txt" >> "${FINAL_REPORT}"
        echo '```' >> "${FINAL_REPORT}"
    fi
    
    cat >> "${FINAL_REPORT}" << 'EOF'

### المرحلة 2: التحليل العميق والاختبارات

#### 2.1 التحليل الساكن (PHPStan)
EOF

    if [ -f "${REPORT_DIR}/phase2-phpstan-max.txt" ]; then
        echo '```' >> "${FINAL_REPORT}"
        head -100 "${REPORT_DIR}/phase2-phpstan-max.txt" >> "${FINAL_REPORT}"
        echo '```' >> "${FINAL_REPORT}"
    fi
    
    cat >> "${FINAL_REPORT}" << 'EOF'

#### 2.2 فحص الثغرات الأمنية
EOF

    if [ -f "${REPORT_DIR}/phase2-composer-audit.txt" ]; then
        echo '```' >> "${FINAL_REPORT}"
        cat "${REPORT_DIR}/phase2-composer-audit.txt" >> "${FINAL_REPORT}"
        echo '```' >> "${FINAL_REPORT}"
    fi
    
    cat >> "${FINAL_REPORT}" << 'EOF'

---

## التوصيات

### أولويات عالية (High Priority)
- [ ] إصلاح جميع الثغرات الأمنية المكتشفة
- [ ] تحديث الحزم القديمة
- [ ] إصلاح مشاكل PHPStan Level Max

### أولويات متوسطة (Medium Priority)
- [ ] تحسين تغطية الاختبارات
- [ ] إزالة الحزم غير المستخدمة
- [ ] تحسين تنسيق الكود

### أولويات منخفضة (Low Priority)
- [ ] تحسين التوثيق
- [ ] تنظيف الملفات المؤقتة

---

## الملفات المرفقة

جميع التقارير التفصيلية متوفرة في المجلد:
EOF

    echo "\`${REPORT_DIR}\`" >> "${FINAL_REPORT}"
    
    print_success "تم إنشاء التقرير النهائي: ${FINAL_REPORT}"
    log_message "اكتمل إنشاء التقرير النهائي"
}

################################################################################
# Main Execution
################################################################################

main() {
    print_header "بدء الفحص والتحليل الشامل"
    log_message "بدء التنفيذ الشامل"
    
    # Check if we're in a Laravel project
    if [ ! -f "artisan" ]; then
        print_error "هذا ليس مشروع Laravel!"
        exit 1
    fi
    
    # Check if vendor directory exists
    if [ ! -d "vendor" ]; then
        print_error "مجلد vendor غير موجود! يرجى تشغيل: composer install"
        exit 1
    fi
    
    # Execute all phases
    phase1_basic_quality_checks
    phase2_deep_analysis_tests
    phase3_environment_integration
    phase4_advanced_performance_security
    
    # Generate final report
    generate_final_report
    
    print_header "اكتمل الفحص والتحليل الشامل"
    print_success "جميع التقارير متوفرة في: ${REPORT_DIR}"
    log_message "اكتمل التنفيذ بنجاح"
    
    echo ""
    echo "لعرض التقرير النهائي:"
    echo "cat ${REPORT_DIR}/COMPREHENSIVE_AUDIT_REPORT.md"
}

# Run main function
main "$@"


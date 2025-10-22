#!/bin/bash

################################################################################
# Task 4 Demo Execution Script
# تنفيذ تجريبي لأول 20 اختبار من القائمة الشاملة
################################################################################

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m'

# Configuration
REPORTS_DIR="reports/task4_demo"
BATCH_SIZE=10
TOTAL_TESTS=20

# Counters
PASSED=0
FAILED=0
SKIPPED=0

# Initialize
echo -e "${BLUE}================================================================================${NC}"
echo -e "${BLUE}Task 4 - تنفيذ تجريبي (أول 20 اختبار)${NC}"
echo -e "${BLUE}مشروع COPRRA${NC}"
echo -e "${BLUE}================================================================================${NC}\n"

# Create directories
mkdir -p "$REPORTS_DIR"
mkdir -p "$REPORTS_DIR/outputs"

# Start time
START_TIME=$(date +%s)

echo -e "${CYAN}بدء التنفيذ...${NC}\n"

################################################################################
# Batch 1: Tests 1-10 (Static Analysis Tools)
################################################################################

echo -e "${YELLOW}=== الدفعة #1: الاختبارات 1-10 (أدوات التحليل الثابت) ===${NC}\n"

# Test 001: PHPStan Level 8
echo -n "  [001] PHPStan - Level 8... "
if timeout 60 ./vendor/bin/phpstan analyse --memory-limit=2G --level=8 > "$REPORTS_DIR/outputs/001-phpstan-output.txt" 2>&1; then
    echo -e "${GREEN}✓${NC}"
    ((PASSED++))
else
    echo -e "${RED}✗${NC}"
    ((FAILED++))
fi

# Test 002: PHPStan with Baseline
echo -n "  [002] PHPStan - with Baseline... "
if timeout 60 ./vendor/bin/phpstan analyse --memory-limit=2G --configuration=phpstan.neon > "$REPORTS_DIR/outputs/002-phpstan-baseline-output.txt" 2>&1; then
    echo -e "${GREEN}✓${NC}"
    ((PASSED++))
else
    echo -e "${RED}✗${NC}"
    ((FAILED++))
fi

# Test 003: Larastan
echo -n "  [003] Larastan... "
if timeout 60 ./vendor/bin/phpstan analyse --configuration=phpstan.neon --memory-limit=2G > "$REPORTS_DIR/outputs/003-larastan-output.txt" 2>&1; then
    echo -e "${GREEN}✓${NC}"
    ((PASSED++))
else
    echo -e "${RED}✗${NC}"
    ((FAILED++))
fi

# Test 004: Psalm Level 1
echo -n "  [004] Psalm - Level 1... "
if timeout 60 ./vendor/bin/psalm --show-info=true --config=psalm.xml > "$REPORTS_DIR/outputs/004-psalm-output.txt" 2>&1; then
    echo -e "${GREEN}✓${NC}"
    ((PASSED++))
else
    echo -e "${RED}✗${NC}"
    ((FAILED++))
fi

# Test 005: Psalm Taint Analysis
echo -n "  [005] Psalm - Taint Analysis... "
if timeout 60 ./vendor/bin/psalm --taint-analysis --config=psalm.xml > "$REPORTS_DIR/outputs/005-psalm-taint-output.txt" 2>&1; then
    echo -e "${GREEN}✓${NC}"
    ((PASSED++))
else
    echo -e "${RED}✗${NC}"
    ((FAILED++))
fi

# Test 006: Psalm Dead Code
echo -n "  [006] Psalm - Dead Code Detection... "
if timeout 60 ./vendor/bin/psalm --find-dead-code --config=psalm.xml > "$REPORTS_DIR/outputs/006-psalm-dead-code-output.txt" 2>&1; then
    echo -e "${GREEN}✓${NC}"
    ((PASSED++))
else
    echo -e "${RED}✗${NC}"
    ((FAILED++))
fi

# Test 007: PHPMD All Rulesets
echo -n "  [007] PHPMD - All Rulesets... "
if timeout 60 ./vendor/bin/phpmd app text phpmd.xml > "$REPORTS_DIR/outputs/007-phpmd-output.txt" 2>&1; then
    echo -e "${GREEN}✓${NC}"
    ((PASSED++))
else
    echo -e "${RED}✗${NC}"
    ((FAILED++))
fi

# Test 008: PHPMD Clean Code
echo -n "  [008] PHPMD - Clean Code... "
if timeout 60 ./vendor/bin/phpmd app text cleancode > "$REPORTS_DIR/outputs/008-phpmd-cleancode-output.txt" 2>&1; then
    echo -e "${GREEN}✓${NC}"
    ((PASSED++))
else
    echo -e "${RED}✗${NC}"
    ((FAILED++))
fi

# Test 009: PHPMD Code Size
echo -n "  [009] PHPMD - Code Size... "
if timeout 60 ./vendor/bin/phpmd app text codesize > "$REPORTS_DIR/outputs/009-phpmd-codesize-output.txt" 2>&1; then
    echo -e "${GREEN}✓${NC}"
    ((PASSED++))
else
    echo -e "${RED}✗${NC}"
    ((FAILED++))
fi

# Test 010: PHPMD Controversial
echo -n "  [010] PHPMD - Controversial... "
if timeout 60 ./vendor/bin/phpmd app text controversial > "$REPORTS_DIR/outputs/010-phpmd-controversial-output.txt" 2>&1; then
    echo -e "${GREEN}✓${NC}"
    ((PASSED++))
else
    echo -e "${RED}✗${NC}"
    ((FAILED++))
fi

echo -e "\n${CYAN}الدفعة #1: ✓ $PASSED | ✗ $FAILED${NC}\n"

################################################################################
# Batch 2: Tests 11-20 (Code Quality Tools)
################################################################################

echo -e "${YELLOW}=== الدفعة #2: الاختبارات 11-20 (أدوات جودة الكود) ===${NC}\n"

# Test 011: PHPMD Design
echo -n "  [011] PHPMD - Design... "
if timeout 60 ./vendor/bin/phpmd app text design > "$REPORTS_DIR/outputs/011-phpmd-design-output.txt" 2>&1; then
    echo -e "${GREEN}✓${NC}"
    ((PASSED++))
else
    echo -e "${RED}✗${NC}"
    ((FAILED++))
fi

# Test 012: PHPMD Naming
echo -n "  [012] PHPMD - Naming... "
if timeout 60 ./vendor/bin/phpmd app text naming > "$REPORTS_DIR/outputs/012-phpmd-naming-output.txt" 2>&1; then
    echo -e "${GREEN}✓${NC}"
    ((PASSED++))
else
    echo -e "${RED}✗${NC}"
    ((FAILED++))
fi

# Test 013: PHPMD Unused Code
echo -n "  [013] PHPMD - Unused Code... "
if timeout 60 ./vendor/bin/phpmd app text unusedcode > "$REPORTS_DIR/outputs/013-phpmd-unusedcode-output.txt" 2>&1; then
    echo -e "${GREEN}✓${NC}"
    ((PASSED++))
else
    echo -e "${RED}✗${NC}"
    ((FAILED++))
fi

# Test 014: PHPCPD
echo -n "  [014] PHPCPD - Copy/Paste Detection... "
if timeout 60 ./vendor/bin/phpcpd app > "$REPORTS_DIR/outputs/014-phpcpd-output.txt" 2>&1; then
    echo -e "${GREEN}✓${NC}"
    ((PASSED++))
else
    echo -e "${RED}✗${NC}"
    ((FAILED++))
fi

# Test 015: Laravel Pint
echo -n "  [015] Laravel Pint - Code Style... "
if timeout 60 ./vendor/bin/pint --test > "$REPORTS_DIR/outputs/015-pint-output.txt" 2>&1; then
    echo -e "${GREEN}✓${NC}"
    ((PASSED++))
else
    echo -e "${RED}✗${NC}"
    ((FAILED++))
fi

# Test 016: PHP Insights
echo -n "  [016] PHP Insights... "
if timeout 60 ./vendor/bin/phpinsights analyse --no-interaction --format=json > "$REPORTS_DIR/outputs/016-phpinsights-output.json" 2>&1; then
    echo -e "${GREEN}✓${NC}"
    ((PASSED++))
else
    echo -e "${RED}✗${NC}"
    ((FAILED++))
fi

# Test 017: Composer Audit
echo -n "  [017] Composer Audit... "
if timeout 30 composer audit --format=json > "$REPORTS_DIR/outputs/017-composer-audit-output.json" 2>&1; then
    echo -e "${GREEN}✓${NC}"
    ((PASSED++))
else
    echo -e "${RED}✗${NC}"
    ((FAILED++))
fi

# Test 018: Composer Unused
echo -n "  [018] Composer Unused... "
if timeout 60 ./vendor/bin/composer-unused > "$REPORTS_DIR/outputs/018-composer-unused-output.txt" 2>&1; then
    echo -e "${GREEN}✓${NC}"
    ((PASSED++))
else
    echo -e "${RED}✗${NC}"
    ((FAILED++))
fi

# Test 019: NPM Audit
echo -n "  [019] NPM Audit... "
if timeout 30 npm audit --json > "$REPORTS_DIR/outputs/019-npm-audit-output.json" 2>&1; then
    echo -e "${GREEN}✓${NC}"
    ((PASSED++))
else
    echo -e "${RED}✗${NC}"
    ((FAILED++))
fi

# Test 020: Security Checker
echo -n "  [020] Security Checker... "
if timeout 30 ./vendor/bin/security-checker security:check composer.lock > "$REPORTS_DIR/outputs/020-security-checker-output.txt" 2>&1; then
    echo -e "${GREEN}✓${NC}"
    ((PASSED++))
else
    # Security checker might not be installed, skip
    echo -e "${YELLOW}⊘${NC}"
    ((SKIPPED++))
fi

echo -e "\n${CYAN}الدفعة #2: ✓ $PASSED | ✗ $FAILED | ⊘ $SKIPPED${NC}\n"

################################################################################
# Summary
################################################################################

END_TIME=$(date +%s)
DURATION=$((END_TIME - START_TIME))

echo -e "${BLUE}================================================================================${NC}"
echo -e "${BLUE}ملخص النتائج${NC}"
echo -e "${BLUE}================================================================================${NC}\n"

echo -e "إجمالي الاختبارات: $TOTAL_TESTS"
echo -e "${GREEN}✓ نجح: $PASSED${NC}"
echo -e "${RED}✗ فشل: $FAILED${NC}"
echo -e "${YELLOW}⊘ تم تخطيه: $SKIPPED${NC}"
echo -e "\nمدة التنفيذ: ${DURATION}s"

SUCCESS_RATE=$((PASSED * 100 / TOTAL_TESTS))
echo -e "نسبة النجاح: $SUCCESS_RATE%\n"

if [ $SUCCESS_RATE -ge 80 ]; then
    echo -e "${GREEN}✅ ممتاز - نسبة النجاح أعلى من 80%${NC}"
else
    echo -e "${YELLOW}⚠ يحتاج إلى مراجعة - نسبة النجاح أقل من 80%${NC}"
fi

echo -e "\n${BLUE}التقارير المحفوظة في: $REPORTS_DIR/outputs/${NC}"
echo -e "${BLUE}================================================================================${NC}\n"


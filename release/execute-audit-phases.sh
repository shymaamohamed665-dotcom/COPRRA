#!/bin/bash

# Simplified Comprehensive Audit Script
# Executes each phase with proper error handling

set +e  # Don't exit on error

TIMESTAMP=$(date +%Y%m%d_%H%M%S)
REPORT_DIR="reports"
mkdir -p "$REPORT_DIR"

echo "========================================="
echo "COMPREHENSIVE AUDIT - Phase 1"
echo "========================================="
echo ""

# Phase 1.2: Code Formatting Check
echo "[1.2] Checking Code Formatting with Pint..."
if [ -f "vendor/bin/pint" ]; then
    vendor/bin/pint --test > "$REPORT_DIR/pint-$TIMESTAMP.txt" 2>&1
    echo "✓ Pint check complete. See $REPORT_DIR/pint-$TIMESTAMP.txt"
else
    echo "⚠ Pint not found. Checking with php-cs-fixer..."
    if [ -f "vendor/bin/php-cs-fixer" ]; then
        vendor/bin/php-cs-fixer fix --dry-run --diff > "$REPORT_DIR/cs-fixer-$TIMESTAMP.txt" 2>&1
        echo "✓ CS Fixer check complete"
    else
        echo "✗ No code formatter found"
    fi
fi
echo ""

# Phase 1.3: Code Duplication
echo "[1.3] Checking Code Duplication..."
if [ -f "vendor/bin/phpcpd" ]; then
    vendor/bin/phpcpd app --min-lines=3 --min-tokens=40 > "$REPORT_DIR/duplication-$TIMESTAMP.txt" 2>&1
    echo "✓ Duplication check complete. See $REPORT_DIR/duplication-$TIMESTAMP.txt"
else
    echo "⚠ PHPCPD not installed"
fi
echo ""

# Phase 1.4: Code Complexity
echo "[1.4] Analyzing Code Complexity..."
vendor/bin/phpinsights analyse app --no-interaction --format=json > "$REPORT_DIR/phpinsights-$TIMESTAMP.json" 2>&1
echo "✓ PHP Insights analysis complete. See $REPORT_DIR/phpinsights-$TIMESTAMP.json"
echo ""

echo "========================================="
echo "COMPREHENSIVE AUDIT - Phase 2"
echo "========================================="
echo ""

# Phase 2.1: PHPStan Analysis
echo "[2.1] Running PHPStan Static Analysis..."
php -d memory_limit=1G vendor/bin/phpstan analyse --error-format=table > "$REPORT_DIR/phpstan-$TIMESTAMP.txt" 2>&1
echo "✓ PHPStan analysis complete. See $REPORT_DIR/phpstan-$TIMESTAMP.txt"
echo ""

# Phase 2.2: Security Audit
echo "[2.2] Running Composer Security Audit..."
composer audit --format=plain > "$REPORT_DIR/composer-audit-$TIMESTAMP.txt" 2>&1
echo "✓ Security audit complete. See $REPORT_DIR/composer-audit-$TIMESTAMP.txt"
echo ""

# Phase 2.3: Unused Dependencies
echo "[2.3] Detecting Unused Dependencies..."
vendor/bin/composer-unused --no-interaction > "$REPORT_DIR/unused-deps-$TIMESTAMP.txt" 2>&1
echo "✓ Unused dependencies check complete. See $REPORT_DIR/unused-deps-$TIMESTAMP.txt"
echo ""

# Phase 2.4: Run Tests
echo "[2.4] Running PHPUnit Test Suite..."
vendor/bin/phpunit --testdox > "$REPORT_DIR/phpunit-$TIMESTAMP.txt" 2>&1
TEST_EXIT=$?
if [ $TEST_EXIT -eq 0 ]; then
    echo "✓ All tests passed"
else
    echo "⚠ Some tests failed. See $REPORT_DIR/phpunit-$TIMESTAMP.txt"
fi
echo ""

# Phase 2.5: Test Coverage
echo "[2.5] Checking Test Coverage..."
if php -m | grep -q xdebug; then
    echo "  Xdebug detected, generating coverage..."
    XDEBUG_MODE=coverage vendor/bin/phpunit --coverage-text > "$REPORT_DIR/coverage-$TIMESTAMP.txt" 2>&1
    echo "✓ Coverage report generated. See $REPORT_DIR/coverage-$TIMESTAMP.txt"
else
    echo "⚠ Xdebug not available for coverage"
fi
echo ""

echo "========================================="
echo "COMPREHENSIVE AUDIT - Phase 3"
echo "========================================="
echo ""

# Phase 3.1: Find Problematic Directories
echo "[3.1] Checking for Problematic Directory Paths..."
find . -type d -name "*C:*" -o -name "*Users*" 2>/dev/null | grep -v node_modules | grep -v vendor > "$REPORT_DIR/problematic-dirs-$TIMESTAMP.txt"
PROB_COUNT=$(wc -l < "$REPORT_DIR/problematic-dirs-$TIMESTAMP.txt")
if [ "$PROB_COUNT" -gt 0 ]; then
    echo "⚠ Found $PROB_COUNT problematic directories. See $REPORT_DIR/problematic-dirs-$TIMESTAMP.txt"
else
    echo "✓ No problematic directories found"
fi
echo ""

# Phase 3.2: Check CI/CD
echo "[3.2] Checking CI/CD Configuration..."
if [ -f ".github/workflows/ci.yml" ] || [ -f ".github/workflows/tests.yml" ]; then
    echo "✓ GitHub Actions workflow exists"
else
    echo "⚠ No CI/CD workflow found"
fi
echo ""

# Phase 3.3: Check Pre-commit Hooks
echo "[3.3] Checking Pre-commit Hooks..."
if [ -f ".husky/pre-commit" ]; then
    echo "✓ Husky pre-commit hook exists"
else
    echo "⚠ No pre-commit hooks configured"
fi
echo ""

echo "========================================="
echo "COMPREHENSIVE AUDIT - Phase 4"
echo "========================================="
echo ""

# Phase 4.1: Database Performance Tools
echo "[4.1] Checking Database Performance Tools..."
if grep -q "laravel/telescope" composer.json; then
    echo "✓ Laravel Telescope installed"
else
    echo "⚠ Laravel Telescope not installed"
fi
echo ""

# Phase 4.2: NPM Security Audit
echo "[4.2] Running NPM Security Audit..."
if [ -f "package.json" ]; then
    npm audit --production > "$REPORT_DIR/npm-audit-$TIMESTAMP.txt" 2>&1
    echo "✓ NPM audit complete. See $REPORT_DIR/npm-audit-$TIMESTAMP.txt"
else
    echo "⚠ No package.json found"
fi
echo ""

echo "========================================="
echo "AUDIT COMPLETE"
echo "========================================="
echo ""
echo "All reports saved to: $REPORT_DIR/"
echo "Timestamp: $TIMESTAMP"
echo ""


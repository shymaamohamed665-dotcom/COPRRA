#!/bin/bash

# Run All Quality Checks Script
# This script runs all code quality, security, and testing checks

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Counters
PASSED=0
FAILED=0
WARNINGS=0

# Function to print colored output
print_header() {
    echo -e "\n${BLUE}╔════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${BLUE}║  $1${NC}"
    echo -e "${BLUE}╚════════════════════════════════════════════════════════════╝${NC}\n"
}

print_success() {
    echo -e "${GREEN}✓${NC} $1"
    ((PASSED++))
}

print_error() {
    echo -e "${RED}✗${NC} $1"
    ((FAILED++))
}

print_warning() {
    echo -e "${YELLOW}⚠${NC} $1"
    ((WARNINGS++))
}

print_info() {
    echo -e "${BLUE}ℹ${NC} $1"
}

# Create reports directory
mkdir -p reports/checks-$(date +%Y%m%d-%H%M%S)
REPORT_DIR="reports/checks-$(date +%Y%m%d-%H%M%S)"

echo -e "${BLUE}"
echo "╔════════════════════════════════════════════════════════════╗"
echo "║         Comprehensive Quality Checks                       ║"
echo "║                                                            ║"
echo "║  Running all code quality, security, and testing checks   ║"
echo "╚════════════════════════════════════════════════════════════╝"
echo -e "${NC}\n"

# 1. Environment Check
print_header "1. Environment Check"
if php check-environment.php > "$REPORT_DIR/environment-check.txt" 2>&1; then
    print_success "Environment check passed"
else
    print_warning "Environment check has warnings (see $REPORT_DIR/environment-check.txt)"
fi

# 2. Code Style Check (Laravel Pint)
print_header "2. Code Style Check (Laravel Pint)"
print_info "Checking code style with Laravel Pint..."
if timeout 60 ./vendor/bin/pint --test > "$REPORT_DIR/pint-check.txt" 2>&1; then
    print_success "Code style check passed"
else
    print_warning "Code style issues found (see $REPORT_DIR/pint-check.txt)"
    print_info "Run './vendor/bin/pint' to fix automatically"
fi

# 3. Static Analysis (PHPStan)
print_header "3. Static Analysis (PHPStan)"
print_info "Running PHPStan static analysis (Level 5)..."
if timeout 120 php -d memory_limit=2G ./vendor/bin/phpstan analyse app --level=5 --no-progress > "$REPORT_DIR/phpstan.txt" 2>&1; then
    print_success "PHPStan analysis passed"
else
    print_error "PHPStan found issues (see $REPORT_DIR/phpstan.txt)"
fi

# 4. Code Quality (PHP Insights)
print_header "4. Code Quality Analysis (PHP Insights)"
print_info "Analyzing code quality..."
if timeout 120 ./vendor/bin/phpinsights analyse app --no-interaction --format=json > "$REPORT_DIR/phpinsights.json" 2>&1; then
    print_success "PHP Insights analysis completed"
    
    # Extract scores
    if command -v jq &> /dev/null; then
        CODE_SCORE=$(jq -r '.summary.code' "$REPORT_DIR/phpinsights.json" 2>/dev/null || echo "N/A")
        COMPLEXITY_SCORE=$(jq -r '.summary.complexity' "$REPORT_DIR/phpinsights.json" 2>/dev/null || echo "N/A")
        ARCHITECTURE_SCORE=$(jq -r '.summary.architecture' "$REPORT_DIR/phpinsights.json" 2>/dev/null || echo "N/A")
        STYLE_SCORE=$(jq -r '.summary.style' "$REPORT_DIR/phpinsights.json" 2>/dev/null || echo "N/A")
        
        print_info "  Code: $CODE_SCORE | Complexity: $COMPLEXITY_SCORE | Architecture: $ARCHITECTURE_SCORE | Style: $STYLE_SCORE"
    fi
else
    print_warning "PHP Insights analysis had issues (see $REPORT_DIR/phpinsights.json)"
fi

# 5. Security Audit (Composer)
print_header "5. Security Audit (Composer)"
print_info "Checking for security vulnerabilities..."
if composer audit --no-interaction > "$REPORT_DIR/composer-audit.txt" 2>&1; then
    print_success "No security vulnerabilities found"
else
    VULN_COUNT=$(grep -c "vulnerability" "$REPORT_DIR/composer-audit.txt" 2>/dev/null || echo "0")
    if [ "$VULN_COUNT" -gt 0 ]; then
        print_error "Found $VULN_COUNT security vulnerabilities (see $REPORT_DIR/composer-audit.txt)"
    else
        print_success "No security vulnerabilities found"
    fi
fi

# 6. Security Audit (NPM)
print_header "6. Security Audit (NPM)"
print_info "Checking npm packages for vulnerabilities..."
if npm audit --audit-level=moderate > "$REPORT_DIR/npm-audit.txt" 2>&1; then
    print_success "No npm security vulnerabilities found"
else
    print_warning "NPM audit found issues (see $REPORT_DIR/npm-audit.txt)"
fi

# 7. Unused Dependencies
print_header "7. Unused Dependencies Check"
print_info "Checking for unused Composer dependencies..."
if timeout 60 ./vendor/bin/composer-unused --no-progress > "$REPORT_DIR/unused-deps.txt" 2>&1; then
    UNUSED_COUNT=$(grep -c "unused" "$REPORT_DIR/unused-deps.txt" 2>/dev/null || echo "0")
    if [ "$UNUSED_COUNT" -gt 0 ]; then
        print_warning "Found $UNUSED_COUNT unused dependencies (see $REPORT_DIR/unused-deps.txt)"
    else
        print_success "No unused dependencies found"
    fi
else
    print_warning "Unused dependencies check had issues"
fi

# 8. Code Duplication
print_header "8. Code Duplication Check"
print_info "Checking for code duplication..."
if command -v phpcpd &> /dev/null; then
    if phpcpd app --min-lines=5 --min-tokens=50 > "$REPORT_DIR/duplication.txt" 2>&1; then
        print_success "No significant code duplication found"
    else
        DUP_COUNT=$(grep -c "duplicated lines" "$REPORT_DIR/duplication.txt" 2>/dev/null || echo "0")
        if [ "$DUP_COUNT" -gt 0 ]; then
            print_warning "Code duplication found (see $REPORT_DIR/duplication.txt)"
        else
            print_success "No significant code duplication found"
        fi
    fi
else
    print_info "phpcpd not installed, skipping duplication check"
fi

# 9. Run Tests
print_header "9. Running Tests"
print_info "Running PHPUnit tests..."
if timeout 300 ./vendor/bin/phpunit --stop-on-failure > "$REPORT_DIR/phpunit.txt" 2>&1; then
    TEST_COUNT=$(grep -oP '\d+(?= tests)' "$REPORT_DIR/phpunit.txt" | head -1)
    print_success "All tests passed ($TEST_COUNT tests)"
else
    print_error "Some tests failed (see $REPORT_DIR/phpunit.txt)"
fi

# 10. Test Coverage
print_header "10. Test Coverage Analysis"
print_info "Analyzing test coverage..."
if timeout 300 ./vendor/bin/phpunit --coverage-text > "$REPORT_DIR/coverage.txt" 2>&1; then
    COVERAGE=$(grep -oP '\d+\.\d+(?=%)' "$REPORT_DIR/coverage.txt" | head -1)
    if [ -n "$COVERAGE" ]; then
        print_info "Test coverage: $COVERAGE%"
        if (( $(echo "$COVERAGE >= 80" | bc -l) )); then
            print_success "Test coverage is excellent (>= 80%)"
        elif (( $(echo "$COVERAGE >= 60" | bc -l) )); then
            print_warning "Test coverage is acceptable (>= 60%)"
        else
            print_warning "Test coverage needs improvement (< 60%)"
        fi
    else
        print_info "Coverage analysis completed (see $REPORT_DIR/coverage.txt)"
    fi
else
    print_warning "Coverage analysis had issues"
fi

# 11. Database Check
print_header "11. Database Check"
print_info "Checking database connection and migrations..."
if php artisan migrate:status > "$REPORT_DIR/migrations.txt" 2>&1; then
    print_success "Database connection successful"
    PENDING=$(grep -c "Pending" "$REPORT_DIR/migrations.txt" 2>/dev/null || echo "0")
    if [ "$PENDING" -gt 0 ]; then
        print_warning "Found $PENDING pending migrations"
    else
        print_success "All migrations are up to date"
    fi
else
    print_error "Database connection failed (see $REPORT_DIR/migrations.txt)"
fi

# 12. Performance Optimization Check
print_header "12. Performance Optimization"
print_info "Checking if caches are optimized..."
if [ -f "bootstrap/cache/config.php" ]; then
    print_success "Configuration is cached"
else
    print_warning "Configuration is not cached (run: php artisan config:cache)"
fi

if [ -f "bootstrap/cache/routes-v7.php" ]; then
    print_success "Routes are cached"
else
    print_warning "Routes are not cached (run: php artisan route:cache)"
fi

# Summary
echo -e "\n${BLUE}╔════════════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║                      Summary                               ║${NC}"
echo -e "${BLUE}╚════════════════════════════════════════════════════════════╝${NC}\n"

echo -e "${GREEN}Passed:   $PASSED${NC}"
echo -e "${RED}Failed:   $FAILED${NC}"
echo -e "${YELLOW}Warnings: $WARNINGS${NC}"
echo ""

# Calculate score
TOTAL=$((PASSED + FAILED + WARNINGS))
if [ $TOTAL -gt 0 ]; then
    SCORE=$((PASSED * 100 / TOTAL))
    echo -e "Overall Score: ${BLUE}$SCORE/100${NC}"
    echo ""
fi

# Final verdict
if [ $FAILED -eq 0 ]; then
    if [ $WARNINGS -eq 0 ]; then
        echo -e "${GREEN}✅ All checks passed! Your code is in excellent shape!${NC}"
        EXIT_CODE=0
    else
        echo -e "${YELLOW}⚠️  All critical checks passed, but there are some warnings to address.${NC}"
        EXIT_CODE=0
    fi
else
    echo -e "${RED}❌ Some checks failed. Please review the reports in $REPORT_DIR${NC}"
    EXIT_CODE=1
fi

echo ""
echo -e "${BLUE}📁 Detailed reports saved in: $REPORT_DIR${NC}"
echo ""

exit $EXIT_CODE


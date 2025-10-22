#!/bin/bash

# Comprehensive Audit & Analysis Script for PHP/Laravel Project
# This script executes all phases of the audit roadmap systematically

set -e  # Exit on error

# Color codes for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Create reports directory if it doesn't exist
mkdir -p reports
mkdir -p reports/coverage

# Timestamp for this audit run
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
AUDIT_REPORT="reports/comprehensive-audit-${TIMESTAMP}.md"

# Initialize audit report
cat > "$AUDIT_REPORT" << 'EOF'
# Comprehensive Audit Report
Generated: $(date)

## Executive Summary
This report contains findings from a comprehensive audit covering:
- Code Quality & Baseline Checks
- Deep Analysis & Testing
- Environment & Integration Validation
- Performance & Security Testing

---

EOF

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}COMPREHENSIVE AUDIT & ANALYSIS${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""

# Function to log results
log_result() {
    local phase=$1
    local status=$2
    local message=$3
    
    echo -e "${BLUE}[$phase]${NC} $status: $message"
    echo "### $phase" >> "$AUDIT_REPORT"
    echo "**Status:** $status" >> "$AUDIT_REPORT"
    echo "$message" >> "$AUDIT_REPORT"
    echo "" >> "$AUDIT_REPORT"
}

# Function to run command and capture output
run_audit_step() {
    local step_name=$1
    local command=$2
    local output_file=$3
    
    echo -e "${YELLOW}Running: $step_name${NC}"
    
    if eval "$command" > "$output_file" 2>&1; then
        log_result "$step_name" "✓ PASS" "$(cat $output_file | head -20)"
        return 0
    else
        log_result "$step_name" "✗ FAIL" "$(cat $output_file | head -20)"
        return 1
    fi
}

echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}PHASE 1: Core Code Quality & Baseline${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""

# Step 1.1: Verify Tools Installation
echo -e "${YELLOW}Step 1.1: Verifying QA Tools Installation${NC}"
echo "## Phase 1: Core Code Quality & Baseline Checks" >> "$AUDIT_REPORT"
echo "" >> "$AUDIT_REPORT"

if [ -f "vendor/bin/phpinsights" ] && [ -f "vendor/bin/composer-unused" ]; then
    log_result "1.1 QA Tools Installation" "✓ VERIFIED" "PHP Insights and Composer-Unused are installed"
else
    log_result "1.1 QA Tools Installation" "⚠ WARNING" "Some tools missing, attempting installation..."
    composer require --dev nunomaduro/phpinsights icanhazstring/composer-unused --no-interaction
fi

# Step 1.2: Check Code Formatting with Pint
echo -e "${YELLOW}Step 1.2: Checking Code Formatting${NC}"
if [ -f "vendor/bin/pint" ]; then
    run_audit_step "1.2 Code Formatting (Pint)" "./vendor/bin/pint --test" "reports/pint-${TIMESTAMP}.txt" || true
else
    log_result "1.2 Code Formatting" "⚠ SKIPPED" "Laravel Pint not installed"
fi

# Step 1.3: Check Code Duplication
echo -e "${YELLOW}Step 1.3: Checking Code Duplication${NC}"
if [ -f "vendor/bin/phpcpd" ]; then
    run_audit_step "1.3 Code Duplication" "vendor/bin/phpcpd app --min-lines=3 --min-tokens=40" "reports/duplication-${TIMESTAMP}.txt" || true
else
    log_result "1.3 Code Duplication" "⚠ SKIPPED" "PHPCPD not available"
fi

# Step 1.4: Check Code Complexity
echo -e "${YELLOW}Step 1.4: Checking Code Complexity${NC}"
run_audit_step "1.4 Code Complexity (PHP Insights)" "./vendor/bin/phpinsights analyse --no-interaction --format=json" "reports/phpinsights-${TIMESTAMP}.json" || true

echo ""
echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}PHASE 2: Deep Analysis & Testing${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""
echo "## Phase 2: Deep Analysis & Testing" >> "$AUDIT_REPORT"
echo "" >> "$AUDIT_REPORT"

# Step 2.1: Static Code Analysis with PHPStan
echo -e "${YELLOW}Step 2.1: Running PHPStan Analysis${NC}"
run_audit_step "2.1 PHPStan Analysis" "php -d memory_limit=1G ./vendor/bin/phpstan analyse --error-format=table" "reports/phpstan-${TIMESTAMP}.txt" || true

# Step 2.2: Dependency Security Audit
echo -e "${YELLOW}Step 2.2: Running Security Audit${NC}"
run_audit_step "2.2 Composer Security Audit" "composer audit --format=plain" "reports/composer-audit-${TIMESTAMP}.txt" || true

# Step 2.3: Detect Unused Dependencies
echo -e "${YELLOW}Step 2.3: Detecting Unused Dependencies${NC}"
run_audit_step "2.3 Unused Dependencies" "./vendor/bin/composer-unused --no-interaction" "reports/unused-deps-${TIMESTAMP}.txt" || true

# Step 2.4: Run Unit & Feature Tests
echo -e "${YELLOW}Step 2.4: Running Test Suite${NC}"
run_audit_step "2.4 PHPUnit Tests" "./vendor/bin/phpunit --testdox" "reports/phpunit-${TIMESTAMP}.txt" || true

# Step 2.5: Generate Test Coverage Report
echo -e "${YELLOW}Step 2.5: Generating Test Coverage${NC}"
if command -v php -m | grep -q xdebug; then
    run_audit_step "2.5 Test Coverage" "XDEBUG_MODE=coverage ./vendor/bin/phpunit --coverage-text" "reports/coverage-${TIMESTAMP}.txt" || true
else
    log_result "2.5 Test Coverage" "⚠ SKIPPED" "Xdebug not available"
fi

echo ""
echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}PHASE 3: Environment & Integration${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""
echo "## Phase 3: Environment & Integration Validation" >> "$AUDIT_REPORT"
echo "" >> "$AUDIT_REPORT"

# Step 3.1: Check for problematic directory paths
echo -e "${YELLOW}Step 3.1: Checking for Redundant Files${NC}"
PROBLEMATIC_DIRS=$(find . -type d -name "*C:*" 2>/dev/null | wc -l)
if [ "$PROBLEMATIC_DIRS" -gt 0 ]; then
    find . -type d -name "*C:*" > "reports/problematic-dirs-${TIMESTAMP}.txt"
    log_result "3.1 Redundant Files" "⚠ WARNING" "Found $PROBLEMATIC_DIRS problematic directory paths (see reports/problematic-dirs-${TIMESTAMP}.txt)"
else
    log_result "3.1 Redundant Files" "✓ PASS" "No problematic directory paths found"
fi

# Step 3.2: Check CI/CD Configuration
echo -e "${YELLOW}Step 3.2: Checking CI/CD Configuration${NC}"
if [ -f ".github/workflows/ci.yml" ] || [ -f ".github/workflows/tests.yml" ]; then
    log_result "3.2 CI/CD Configuration" "✓ VERIFIED" "GitHub Actions workflow exists"
else
    log_result "3.2 CI/CD Configuration" "⚠ WARNING" "No CI/CD workflow found"
fi

# Step 3.3: Check Pre-commit Hooks
echo -e "${YELLOW}Step 3.3: Checking Pre-commit Hooks${NC}"
if [ -f ".husky/pre-commit" ]; then
    log_result "3.3 Pre-commit Hooks" "✓ VERIFIED" "Husky pre-commit hook exists"
else
    log_result "3.3 Pre-commit Hooks" "⚠ WARNING" "No pre-commit hooks configured"
fi

echo ""
echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}PHASE 4: Advanced Analysis${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""
echo "## Phase 4: Advanced Performance & Security Testing" >> "$AUDIT_REPORT"
echo "" >> "$AUDIT_REPORT"

# Step 4.1: Check for N+1 Query Issues (if Telescope is available)
echo -e "${YELLOW}Step 4.1: Database Performance Analysis${NC}"
if grep -q "laravel/telescope" composer.json; then
    log_result "4.1 Database Performance" "ℹ INFO" "Laravel Telescope is installed for query monitoring"
else
    log_result "4.1 Database Performance" "⚠ WARNING" "Consider installing Laravel Telescope for query monitoring"
fi

# Step 4.2: JavaScript Security Audit
echo -e "${YELLOW}Step 4.2: JavaScript Security Audit${NC}"
if [ -f "package.json" ]; then
    run_audit_step "4.2 NPM Security Audit" "npm audit --production" "reports/npm-audit-${TIMESTAMP}.txt" || true
else
    log_result "4.2 NPM Security Audit" "⚠ SKIPPED" "No package.json found"
fi

# Final Summary
echo "" >> "$AUDIT_REPORT"
echo "## Summary" >> "$AUDIT_REPORT"
echo "Audit completed at: $(date)" >> "$AUDIT_REPORT"
echo "Full report saved to: $AUDIT_REPORT" >> "$AUDIT_REPORT"

echo ""
echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}AUDIT COMPLETE${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""
echo -e "${BLUE}Full report saved to: ${AUDIT_REPORT}${NC}"
echo -e "${BLUE}Individual reports saved in: reports/${NC}"
echo ""


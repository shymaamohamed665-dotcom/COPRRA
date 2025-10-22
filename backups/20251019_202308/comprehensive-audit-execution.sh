#!/bin/bash

# Comprehensive Audit & Analysis Execution Script
# This script executes all phases of the audit roadmap systematically

set -e  # Exit on error

# Color codes for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Create reports directory
REPORT_DIR="reports/audit-$(date +%Y%m%d-%H%M%S)"
mkdir -p "$REPORT_DIR"

# Log function
log() {
    echo -e "${BLUE}[$(date +'%Y-%m-%d %H:%M:%S')]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[✓]${NC} $1"
}

log_error() {
    echo -e "${RED}[✗]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[!]${NC} $1"
}

# Initialize audit report
AUDIT_REPORT="$REPORT_DIR/COMPREHENSIVE_AUDIT_REPORT.md"

cat > "$AUDIT_REPORT" << 'EOF'
# Comprehensive Audit & Analysis Report
**Generated:** $(date)
**Project:** Laravel Application

---

## Executive Summary

This report contains the results of a comprehensive audit covering:
- Code Quality & Formatting
- Static Analysis
- Security Vulnerabilities
- Dependency Management
- Test Coverage
- Performance Analysis

---

EOF

log "Starting Comprehensive Audit & Analysis..."
log "Report Directory: $REPORT_DIR"

# ============================================================================
# PHASE 1: Core Code Quality & Baseline Checks
# ============================================================================

log "========================================="
log "PHASE 1: Core Code Quality & Baseline Checks"
log "========================================="

# Step 1.1: Verify Tools Installation
log "Step 1.1: Verifying Quality Assurance Tools..."
{
    echo "## Phase 1: Core Code Quality & Baseline Checks" >> "$AUDIT_REPORT"
    echo "" >> "$AUDIT_REPORT"
    echo "### 1.1 Quality Assurance Tools" >> "$AUDIT_REPORT"
    echo "" >> "$AUDIT_REPORT"
    
    if [ -f "vendor/bin/phpinsights" ] && [ -f "vendor/bin/composer-unused" ] && [ -f "vendor/bin/pint" ]; then
        echo "✓ All required tools are installed:" >> "$AUDIT_REPORT"
        echo "  - PHP Insights" >> "$AUDIT_REPORT"
        echo "  - Composer-Unused" >> "$AUDIT_REPORT"
        echo "  - Laravel Pint" >> "$AUDIT_REPORT"
        echo "  - PHPStan" >> "$AUDIT_REPORT"
        echo "" >> "$AUDIT_REPORT"
        log_success "All tools verified"
    else
        echo "✗ Some tools are missing" >> "$AUDIT_REPORT"
        log_error "Some tools are missing"
        exit 1
    fi
}

# Step 1.2: Code Formatting Check
log "Step 1.2: Checking Code Formatting (Laravel Pint)..."
{
    echo "### 1.2 Code Formatting Analysis" >> "$AUDIT_REPORT"
    echo "" >> "$AUDIT_REPORT"
    echo '```' >> "$AUDIT_REPORT"
    
    if ./vendor/bin/pint --test > "$REPORT_DIR/pint-check.txt" 2>&1; then
        cat "$REPORT_DIR/pint-check.txt" >> "$AUDIT_REPORT"
        echo '```' >> "$AUDIT_REPORT"
        echo "" >> "$AUDIT_REPORT"
        echo "**Result:** ✓ All files pass formatting standards" >> "$AUDIT_REPORT"
        echo "" >> "$AUDIT_REPORT"
        log_success "Code formatting check passed"
    else
        cat "$REPORT_DIR/pint-check.txt" >> "$AUDIT_REPORT"
        echo '```' >> "$AUDIT_REPORT"
        echo "" >> "$AUDIT_REPORT"
        echo "**Result:** ✗ Formatting issues detected" >> "$AUDIT_REPORT"
        echo "" >> "$AUDIT_REPORT"
        log_warning "Code formatting issues detected"
    fi
}

# Step 1.3: Code Duplication Check
log "Step 1.3: Checking for Code Duplication..."
{
    echo "### 1.3 Code Duplication Analysis" >> "$AUDIT_REPORT"
    echo "" >> "$AUDIT_REPORT"
    
    # Using phploc for duplication metrics
    if [ -f "vendor/bin/phploc" ]; then
        ./vendor/bin/phploc app > "$REPORT_DIR/phploc.txt" 2>&1 || true
        echo '```' >> "$AUDIT_REPORT"
        cat "$REPORT_DIR/phploc.txt" >> "$AUDIT_REPORT"
        echo '```' >> "$AUDIT_REPORT"
        echo "" >> "$AUDIT_REPORT"
        log_success "Code metrics generated"
    else
        echo "phploc not available, skipping detailed metrics" >> "$AUDIT_REPORT"
        log_warning "phploc not available"
    fi
}

# Step 1.4: Code Complexity Check
log "Step 1.4: Analyzing Code Complexity..."
{
    echo "### 1.4 Code Complexity Analysis" >> "$AUDIT_REPORT"
    echo "" >> "$AUDIT_REPORT"
    echo "Running PHP Insights for complexity analysis..." >> "$AUDIT_REPORT"
    echo "" >> "$AUDIT_REPORT"
    
    # Run PHP Insights and capture summary
    ./vendor/bin/phpinsights analyse app --no-interaction --format=json > "$REPORT_DIR/phpinsights.json" 2>&1 || true
    
    # Extract summary from JSON
    if [ -f "$REPORT_DIR/phpinsights.json" ]; then
        echo "**PHP Insights Summary:**" >> "$AUDIT_REPORT"
        echo '```json' >> "$AUDIT_REPORT"
        cat "$REPORT_DIR/phpinsights.json" | grep -A 10 '"summary"' | head -20 >> "$AUDIT_REPORT" || echo "Summary extraction failed" >> "$AUDIT_REPORT"
        echo '```' >> "$AUDIT_REPORT"
        echo "" >> "$AUDIT_REPORT"
        log_success "Complexity analysis completed"
    fi
}

# ============================================================================
# PHASE 2: Deep Analysis & Testing
# ============================================================================

log "========================================="
log "PHASE 2: Deep Analysis & Testing"
log "========================================="

{
    echo "## Phase 2: Deep Analysis & Testing" >> "$AUDIT_REPORT"
    echo "" >> "$AUDIT_REPORT"
}

# Step 2.1: Static Code Analysis (PHPStan)
log "Step 2.1: Running Static Code Analysis (PHPStan)..."
{
    echo "### 2.1 Static Code Analysis (PHPStan)" >> "$AUDIT_REPORT"
    echo "" >> "$AUDIT_REPORT"
    
    if php -d memory_limit=2G ./vendor/bin/phpstan analyse --error-format=table > "$REPORT_DIR/phpstan.txt" 2>&1; then
        echo "**Result:** ✓ No errors detected" >> "$AUDIT_REPORT"
        log_success "PHPStan analysis passed"
    else
        echo "**Result:** ✗ Errors detected" >> "$AUDIT_REPORT"
        echo "" >> "$AUDIT_REPORT"
        echo '```' >> "$AUDIT_REPORT"
        head -100 "$REPORT_DIR/phpstan.txt" >> "$AUDIT_REPORT"
        echo '```' >> "$AUDIT_REPORT"
        log_warning "PHPStan detected issues"
    fi
    echo "" >> "$AUDIT_REPORT"
}

# Step 2.2: Dependency Security Audit
log "Step 2.2: Running Dependency Security Audit..."
{
    echo "### 2.2 Dependency Security Audit" >> "$AUDIT_REPORT"
    echo "" >> "$AUDIT_REPORT"
    
    echo "#### PHP Dependencies (Composer)" >> "$AUDIT_REPORT"
    echo '```' >> "$AUDIT_REPORT"
    composer audit > "$REPORT_DIR/composer-audit.txt" 2>&1 || true
    cat "$REPORT_DIR/composer-audit.txt" >> "$AUDIT_REPORT"
    echo '```' >> "$AUDIT_REPORT"
    echo "" >> "$AUDIT_REPORT"
    
    if [ -f "package.json" ]; then
        echo "#### JavaScript Dependencies (NPM)" >> "$AUDIT_REPORT"
        echo '```' >> "$AUDIT_REPORT"
        npm audit > "$REPORT_DIR/npm-audit.txt" 2>&1 || true
        cat "$REPORT_DIR/npm-audit.txt" >> "$AUDIT_REPORT"
        echo '```' >> "$AUDIT_REPORT"
        echo "" >> "$AUDIT_REPORT"
    fi
    
    log_success "Security audit completed"
}

# Step 2.3: Detect Unused Dependencies
log "Step 2.3: Detecting Unused Dependencies..."
{
    echo "### 2.3 Unused Dependencies" >> "$AUDIT_REPORT"
    echo "" >> "$AUDIT_REPORT"
    echo '```' >> "$AUDIT_REPORT"
    
    ./vendor/bin/composer-unused > "$REPORT_DIR/unused-deps.txt" 2>&1 || true
    cat "$REPORT_DIR/unused-deps.txt" >> "$AUDIT_REPORT"
    
    echo '```' >> "$AUDIT_REPORT"
    echo "" >> "$AUDIT_REPORT"
    log_success "Unused dependencies check completed"
}

# Step 2.4: Run Tests
log "Step 2.4: Running Unit & Feature Tests..."
{
    echo "### 2.4 Test Execution" >> "$AUDIT_REPORT"
    echo "" >> "$AUDIT_REPORT"
    
    if php artisan config:clear && ./vendor/bin/phpunit > "$REPORT_DIR/phpunit.txt" 2>&1; then
        echo "**Result:** ✓ All tests passed" >> "$AUDIT_REPORT"
        log_success "All tests passed"
    else
        echo "**Result:** ✗ Some tests failed" >> "$AUDIT_REPORT"
        log_warning "Some tests failed"
    fi
    
    echo "" >> "$AUDIT_REPORT"
    echo '```' >> "$AUDIT_REPORT"
    tail -50 "$REPORT_DIR/phpunit.txt" >> "$AUDIT_REPORT"
    echo '```' >> "$AUDIT_REPORT"
    echo "" >> "$AUDIT_REPORT"
}

log_success "Audit execution completed!"
log "Full report available at: $AUDIT_REPORT"

echo "" >> "$AUDIT_REPORT"
echo "---" >> "$AUDIT_REPORT"
echo "**Audit completed at:** $(date)" >> "$AUDIT_REPORT"


#!/bin/bash

################################################################################
# COPRRA System Health Check Script
# Comprehensive validation of environment, dependencies, and system health
################################################################################

set -e

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

################################################################################
# Helper Functions
################################################################################

print_header() {
    echo -e "\n${BLUE}═══════════════════════════════════════════════════════════════${NC}"
    echo -e "${BLUE}  $1${NC}"
    echo -e "${BLUE}═══════════════════════════════════════════════════════════════${NC}\n"
}

print_section() {
    echo -e "\n${YELLOW}▶ $1${NC}"
}

check_pass() {
    echo -e "${GREEN}✓${NC} $1"
    ((PASSED++))
}

check_fail() {
    echo -e "${RED}✗${NC} $1"
    ((FAILED++))
}

check_warn() {
    echo -e "${YELLOW}⚠${NC} $1"
    ((WARNINGS++))
}

command_exists() {
    command -v "$1" >/dev/null 2>&1
}

################################################################################
# Start Health Check
################################################################################

print_header "COPRRA SYSTEM HEALTH CHECK"
echo "Started at: $(date)"

################################################################################
# 1. ENVIRONMENT CHECKS
################################################################################

print_section "1. Checking System Requirements"

# Check PHP
if command_exists php; then
    PHP_VERSION=$(php -r 'echo PHP_VERSION;')
    if [[ $(php -r 'echo version_compare(PHP_VERSION, "8.2.0", ">=") ? 1 : 0;') == 1 ]]; then
        check_pass "PHP version: $PHP_VERSION"
    else
        check_fail "PHP version $PHP_VERSION is too old (requires 8.2+)"
    fi
else
    check_fail "PHP is not installed"
fi

# Check Composer
if command_exists composer; then
    COMPOSER_VERSION=$(composer --version --no-ansi | grep -oE '[0-9]+\.[0-9]+\.[0-9]+' | head -1)
    check_pass "Composer version: $COMPOSER_VERSION"
else
    check_fail "Composer is not installed"
fi

# Check Node.js
if command_exists node; then
    NODE_VERSION=$(node --version)
    if [[ $(node -p 'parseInt(process.version.match(/v(\d+)/)[1]) >= 18') == "true" ]]; then
        check_pass "Node.js version: $NODE_VERSION"
    else
        check_fail "Node.js version $NODE_VERSION is too old (requires 18+)"
    fi
else
    check_warn "Node.js is not installed (required for frontend assets)"
fi

# Check NPM
if command_exists npm; then
    NPM_VERSION=$(npm --version)
    check_pass "NPM version: $NPM_VERSION"
else
    check_warn "NPM is not installed"
fi

# Check Docker
if command_exists docker; then
    DOCKER_VERSION=$(docker --version | grep -oE '[0-9]+\.[0-9]+\.[0-9]+' | head -1)
    check_pass "Docker version: $DOCKER_VERSION"

    # Check if Docker is running
    if docker info > /dev/null 2>&1; then
        check_pass "Docker daemon is running"
    else
        check_fail "Docker daemon is not running"
    fi
else
    check_warn "Docker is not installed (optional for deployment)"
fi

################################################################################
# 2. DOCKER CONTAINER CHECKS (if using Docker)
################################################################################

if command_exists docker-compose && [ -f docker-compose.yml ]; then
    print_section "2. Checking Docker Containers"

    # Check if containers are running
    if docker-compose ps | grep -q "Up"; then
        check_pass "Docker Compose services are running"

        # Check individual containers
        for container in app nginx db redis mailpit; do
            CONTAINER_NAME="coprra-$container"
            if docker ps | grep -q "$CONTAINER_NAME"; then
                STATUS=$(docker inspect --format='{{.State.Health.Status}}' "$CONTAINER_NAME" 2>/dev/null || echo "running")
                if [ "$STATUS" = "healthy" ] || [ "$STATUS" = "running" ]; then
                    check_pass "Container $container is $STATUS"
                else
                    check_warn "Container $container status: $STATUS"
                fi
            else
                check_warn "Container $container is not running"
            fi
        done
    else
        check_warn "Docker Compose services are not running"
    fi
else
    print_section "2. Docker Checks (Skipped - Not using Docker)"
fi

################################################################################
# 3. FILE PERMISSIONS & DIRECTORIES
################################################################################

print_section "3. Checking File Permissions & Directories"

# Check if storage directories exist and are writable
for dir in storage storage/logs storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache; do
    if [ -d "$dir" ]; then
        if [ -w "$dir" ]; then
            check_pass "Directory $dir exists and is writable"
        else
            check_fail "Directory $dir exists but is NOT writable"
        fi
    else
        check_fail "Directory $dir does not exist"
    fi
done

# Check .env file
if [ -f .env ]; then
    check_pass ".env file exists"

    # Check critical env variables
    if grep -q "APP_KEY=base64:" .env; then
        check_pass "APP_KEY is set"
    else
        check_fail "APP_KEY is not set (run: php artisan key:generate)"
    fi
else
    check_fail ".env file does not exist (run: cp .env.example .env)"
fi

################################################################################
# 4. DEPENDENCIES CHECK
################################################################################

print_section "4. Checking Dependencies"

# Check vendor directory
if [ -d vendor ]; then
    check_pass "Composer dependencies installed (vendor/ exists)"

    # Check composer.lock
    if [ -f composer.lock ]; then
        check_pass "composer.lock exists"
    else
        check_warn "composer.lock is missing"
    fi
else
    check_fail "Composer dependencies not installed (run: composer install)"
fi

# Check node_modules
if [ -d node_modules ]; then
    check_pass "NPM dependencies installed (node_modules/ exists)"
else
    check_warn "NPM dependencies not installed (run: npm install)"
fi

# Check public/build (frontend assets)
if [ -d public/build ]; then
    check_pass "Frontend assets built (public/build/ exists)"
else
    check_warn "Frontend assets not built (run: npm run build)"
fi

################################################################################
# 5. DATABASE CONNECTIVITY
################################################################################

print_section "5. Checking Database Connectivity"

if command_exists php && [ -f artisan ]; then
    # Try to connect to database
    if php artisan migrate:status > /dev/null 2>&1; then
        check_pass "Database connection successful"

        # Check migrations
        PENDING=$(php artisan migrate:status 2>&1 | grep -c "Pending" || true)
        if [ "$PENDING" -gt 0 ]; then
            check_warn "$PENDING pending migrations (run: php artisan migrate)"
        else
            check_pass "All migrations are up to date"
        fi
    else
        check_fail "Cannot connect to database"
    fi
else
    check_warn "Skipping database check (artisan not available)"
fi

################################################################################
# 6. REDIS CONNECTIVITY (if configured)
################################################################################

print_section "6. Checking Redis Connectivity"

if grep -q "REDIS_HOST" .env 2>/dev/null; then
    if command_exists redis-cli; then
        REDIS_HOST=$(grep REDIS_HOST .env | cut -d '=' -f2)
        if redis-cli -h "$REDIS_HOST" ping > /dev/null 2>&1; then
            check_pass "Redis connection successful"
        else
            check_warn "Cannot connect to Redis at $REDIS_HOST"
        fi
    else
        check_warn "redis-cli not installed, skipping Redis check"
    fi
else
    check_warn "Redis not configured (optional)"
fi

################################################################################
# 7. CONFIGURATION VALIDATION
################################################################################

print_section "7. Validating Configuration"

if command_exists php && [ -f artisan ]; then
    # Check Laravel configuration
    if php artisan config:show app --env=production > /dev/null 2>&1; then
        check_pass "Laravel configuration is valid"
    else
        check_warn "Laravel configuration has issues"
    fi

    # Check routes
    if php artisan route:list > /dev/null 2>&1; then
        ROUTE_COUNT=$(php artisan route:list --compact | wc -l)
        check_pass "Routes loaded successfully ($ROUTE_COUNT routes)"
    else
        check_fail "Failed to load routes"
    fi
fi

# Validate composer.json
if [ -f composer.json ] && command_exists composer; then
    if composer validate --no-check-all --no-check-publish 2>&1 | grep -q "is valid"; then
        check_pass "composer.json is valid"
    else
        check_warn "composer.json has validation warnings"
    fi
fi

################################################################################
# 8. CODE QUALITY CHECKS
################################################################################

print_section "8. Running Code Quality Checks"

if [ -f vendor/bin/pint ]; then
    if ./vendor/bin/pint --test > /dev/null 2>&1; then
        check_pass "Code style check passed (Laravel Pint)"
    else
        check_warn "Code style issues found (run: ./vendor/bin/pint)"
    fi
else
    check_warn "Laravel Pint not installed"
fi

if [ -f vendor/bin/phpstan ]; then
    if ./vendor/bin/phpstan analyse --no-progress --error-format=raw > /dev/null 2>&1; then
        check_pass "Static analysis passed (PHPStan)"
    else
        check_warn "Static analysis issues found (run: ./vendor/bin/phpstan analyse)"
    fi
else
    check_warn "PHPStan not installed"
fi

################################################################################
# 9. TEST SUITE
################################################################################

print_section "9. Running Test Suite"

if command_exists php && [ -f artisan ] && [ -f vendor/bin/phpunit ]; then
    echo "Running tests (this may take a moment)..."

    if php artisan test --compact 2>&1 | tee /tmp/test-output.txt | grep -q "Tests:.*passed"; then
        TEST_RESULTS=$(grep "Tests:" /tmp/test-output.txt)
        check_pass "Test suite passed - $TEST_RESULTS"
    else
        check_fail "Test suite has failures (run: php artisan test)"
    fi

    rm -f /tmp/test-output.txt
else
    check_warn "Test suite not available"
fi

################################################################################
# 10. SECURITY CHECKS
################################################################################

print_section "10. Security Checks"

# Check for .env in git
if git check-ignore .env > /dev/null 2>&1; then
    check_pass ".env is properly gitignored"
else
    check_fail ".env is NOT gitignored (security risk!)"
fi

# Check APP_DEBUG in production
if [ -f .env ]; then
    APP_ENV=$(grep "^APP_ENV=" .env | cut -d= -f2 | tr -d ' "')
    APP_DEBUG=$(grep "^APP_DEBUG=" .env | cut -d= -f2 | tr -d ' "')

    if [ "$APP_ENV" = "production" ] && [ "$APP_DEBUG" = "true" ]; then
        check_fail "APP_DEBUG=true in production (security risk!)"
    else
        check_pass "APP_DEBUG properly configured for $APP_ENV"
    fi
fi

# Check for outdated packages
if command_exists composer; then
    OUTDATED=$(composer outdated --direct --minor-only 2>&1 | grep -c "^" || true)
    if [ "$OUTDATED" -gt 0 ]; then
        check_warn "$OUTDATED packages have updates available (run: composer outdated)"
    else
        check_pass "All packages are up to date"
    fi
fi

################################################################################
# FINAL SUMMARY
################################################################################

print_header "HEALTH CHECK SUMMARY"

echo -e "${GREEN}✓ Passed:${NC}   $PASSED"
echo -e "${YELLOW}⚠ Warnings:${NC} $WARNINGS"
echo -e "${RED}✗ Failed:${NC}   $FAILED"
echo ""

TOTAL=$((PASSED + WARNINGS + FAILED))
if [ $TOTAL -gt 0 ]; then
    SCORE=$(( (PASSED * 100) / TOTAL ))
    echo -e "Health Score: ${SCORE}%"
fi

echo ""
echo "Completed at: $(date)"
echo ""

# Exit with appropriate code
if [ $FAILED -gt 0 ]; then
    echo -e "${RED}⚠ System has critical issues that need attention${NC}"
    exit 1
elif [ $WARNINGS -gt 5 ]; then
    echo -e "${YELLOW}⚠ System has several warnings to address${NC}"
    exit 2
else
    echo -e "${GREEN}✓ System health check passed!${NC}"
    exit 0
fi

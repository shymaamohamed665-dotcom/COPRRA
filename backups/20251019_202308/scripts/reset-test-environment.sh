#!/bin/bash

###############################################################################
# Reset Test Environment Script
#
# This script ensures a clean, isolated test environment by:
# - Clearing all Laravel caches (config, route, view, application)
# - Cleaning up temporary test files
# - Resetting database migrations (optional)
# - Cleaning PHPUnit cache
# - Verifying environment variables
#
# Usage:
#   ./reset-test-environment.sh              # Basic reset
#   ./reset-test-environment.sh --db         # Reset with database migration
#   ./reset-test-environment.sh --storage    # Reset with storage cleanup
#   ./reset-test-environment.sh --db --storage --verbose  # Full reset
###############################################################################

set -e  # Exit on error

# Colors
CYAN='\033[0;36m'
GREEN='\033[0;32m'
YELLOW='\033[0;33m'
RED='\033[0;31m'
GRAY='\033[0;37m'
NC='\033[0m' # No Color

# Parse arguments
RESET_DATABASE=false
CLEAN_STORAGE=false
VERBOSE=false

for arg in "$@"; do
    case $arg in
        --db|--database)
            RESET_DATABASE=true
            shift
            ;;
        --storage|--clean-storage)
            CLEAN_STORAGE=true
            shift
            ;;
        --verbose|-v)
            VERBOSE=true
            shift
            ;;
        --help|-h)
            echo "Usage: $0 [OPTIONS]"
            echo ""
            echo "Options:"
            echo "  --db, --database     Reset test database with fresh migrations"
            echo "  --storage            Clean up storage directories"
            echo "  --verbose, -v        Show detailed output"
            echo "  --help, -h           Show this help message"
            exit 0
            ;;
    esac
done

# Helper functions
write_step() {
    echo -e "${CYAN}[$(date +%H:%M:%S)] $1${NC}"
}

write_success() {
    echo -e "  ${GREEN}✓${NC} ${GRAY}$1${NC}"
}

write_warning() {
    echo -e "  ${YELLOW}⚠${NC} ${GRAY}$1${NC}"
}

write_error() {
    echo -e "  ${RED}✗${NC} ${GRAY}$1${NC}"
}

echo -e "\n${CYAN}═══════════════════════════════════════════════════════════════${NC}"
echo -e "${CYAN}  Test Environment Reset Script${NC}"
echo -e "${CYAN}═══════════════════════════════════════════════════════════════${NC}\n"

# Step 1: Clear Laravel caches
write_step "Clearing Laravel caches..."
if php artisan cache:clear >/dev/null 2>&1; then
    write_success "Application cache cleared"
else
    write_warning "Failed to clear application cache"
fi

if php artisan config:clear >/dev/null 2>&1; then
    write_success "Configuration cache cleared"
else
    write_warning "Failed to clear configuration cache"
fi

if php artisan route:clear >/dev/null 2>&1; then
    write_success "Route cache cleared"
else
    write_warning "Failed to clear route cache"
fi

if php artisan view:clear >/dev/null 2>&1; then
    write_success "View cache cleared"
else
    write_warning "Failed to clear view cache"
fi

# Step 2: Clean PHPUnit cache
write_step "Cleaning PHPUnit cache..."
if [ -d ".phpunit.cache" ]; then
    rm -rf .phpunit.cache
    write_success "PHPUnit cache directory removed"
fi

# Step 3: Clean temporary test files
write_step "Cleaning temporary test files..."
temp_paths=(
    "storage/framework/cache/*"
    "storage/framework/sessions/*"
    "storage/framework/views/*"
    "storage/framework/testing/*"
)

for path in "${temp_paths[@]}"; do
    if [ -e "$path" ]; then
        rm -rf $path 2>/dev/null || true
        write_success "Cleaned: $path"
    fi
done

# Step 4: Clean storage directories (optional)
if [ "$CLEAN_STORAGE" = true ]; then
    write_step "Cleaning storage directories..."
    storage_paths=(
        "storage/logs/*.log"
        "storage/app/testing/*"
        "storage/debugbar/*"
    )

    for path in "${storage_paths[@]}"; do
        if [ -e "$path" ]; then
            rm -rf $path 2>/dev/null || true
            write_success "Cleaned: $path"
        fi
    done
fi

# Step 5: Reset database (optional)
if [ "$RESET_DATABASE" = true ]; then
    write_step "Resetting test database..."
    if php artisan migrate:fresh --env=testing --force >/dev/null 2>&1; then
        write_success "Database reset completed"
    else
        write_error "Failed to reset database"
    fi
fi

# Step 6: Verify environment
write_step "Verifying environment..."
if [ -f ".env" ]; then
    write_success ".env file exists"
else
    write_error ".env file missing"
fi

if [ -f "phpunit.xml" ]; then
    write_success "phpunit.xml configuration exists"
else
    write_error "phpunit.xml missing"
fi

# Step 7: Check composer dependencies
if php -r "require 'vendor/autoload.php'; echo 'OK';" 2>/dev/null | grep -q "OK"; then
    write_success "Composer autoload verified"
else
    write_error "Composer autoload failed"
fi

echo -e "\n${CYAN}═══════════════════════════════════════════════════════════════${NC}"
echo -e "${GREEN}  Environment reset completed!${NC}"
echo -e "${CYAN}═══════════════════════════════════════════════════════════════${NC}\n"

echo -e "${GRAY}You can now run tests with clean environment:${NC}\n"
echo -e "${CYAN}  ./vendor/bin/phpunit${NC}"
echo -e "${CYAN}  ./vendor/bin/phpunit --testsuite Feature${NC}"
echo -e "${CYAN}  ./vendor/bin/phpunit --testsuite Unit${NC}\n"

exit 0

#!/bin/bash

# ==============================================================================
# VENDOR PROTECTION SCRIPT
# سكريبت حماية مجلد vendor من الحذف العرضي
# ==============================================================================
# This script ensures vendor directory exists and prevents accidental deletion
# ==============================================================================

set -e

PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
VENDOR_DIR="$PROJECT_DIR/vendor"
COMPOSER_LOCK="$PROJECT_DIR/composer.lock"

echo "========================================="
echo "VENDOR PROTECTION & VERIFICATION"
echo "حماية والتحقق من مجلد vendor"
echo "========================================="
echo ""

# Function to check if vendor exists
check_vendor() {
    if [ -d "$VENDOR_DIR" ]; then
        echo "✅ vendor directory exists"
        return 0
    else
        echo "❌ vendor directory NOT found"
        return 1
    fi
}

# Function to check if composer.lock exists
check_composer_lock() {
    if [ -f "$COMPOSER_LOCK" ]; then
        echo "✅ composer.lock exists (dependencies locked)"
        return 0
    else
        echo "⚠️  composer.lock NOT found (dependencies not locked)"
        return 1
    fi
}

# Function to install vendor
install_vendor() {
    echo ""
    echo "📦 Installing Composer dependencies..."
    echo "هذا قد يستغرق عدة دقائق..."

    if command -v composer &> /dev/null; then
        composer install --no-interaction --prefer-dist --optimize-autoloader

        if [ $? -eq 0 ]; then
            echo "✅ Dependencies installed successfully!"
            echo "تم تثبيت الاعتماديات بنجاح!"
            return 0
        else
            echo "❌ Failed to install dependencies"
            echo "فشل تثبيت الاعتماديات"
            return 1
        fi
    else
        echo "❌ Composer not found. Please install Composer first."
        echo "Composer غير موجود. يرجى تثبيت Composer أولاً."
        return 1
    fi
}

# Function to create vendor marker file
create_vendor_marker() {
    local MARKER_FILE="$VENDOR_DIR/.vendor-protected"

    if [ -d "$VENDOR_DIR" ]; then
        echo "Created by vendor protection script" > "$MARKER_FILE"
        echo "Date: $(date)" >> "$MARKER_FILE"
        echo "✅ Created vendor protection marker"
    fi
}

# Main protection logic
main() {
    echo "🔍 Checking vendor status..."
    echo ""

    # Check composer.lock first
    if ! check_composer_lock; then
        echo ""
        echo "⚠️  WARNING: No composer.lock found!"
        echo "This means dependencies are not locked and may vary between installations."
        echo ""
    fi

    # Check vendor directory
    if ! check_vendor; then
        echo ""
        echo "❌ CRITICAL: vendor directory is missing!"
        echo ""
        echo "This is required for the project to function."
        echo "Attempting automatic installation..."
        echo ""

        if install_vendor; then
            create_vendor_marker
            echo ""
            echo "✅ Vendor protection complete!"
        else
            echo ""
            echo "❌ Automatic installation failed."
            echo "Please run: composer install"
            exit 1
        fi
    else
        # Vendor exists, just verify it's complete
        echo "✅ vendor directory is present"

        # Check if autoload.php exists (critical file)
        if [ -f "$VENDOR_DIR/autoload.php" ]; then
            echo "✅ vendor/autoload.php exists (vendor is functional)"
        else
            echo "⚠️  vendor/autoload.php missing - vendor may be corrupted"
            echo "📦 Reinstalling dependencies..."

            if install_vendor; then
                create_vendor_marker
                echo "✅ Vendor reinstalled successfully!"
            else
                echo "❌ Failed to reinstall vendor"
                exit 1
            fi
        fi

        create_vendor_marker
    fi

    echo ""
    echo "========================================="
    echo "VENDOR HEALTH STATUS"
    echo "حالة صحة vendor"
    echo "========================================="
    echo ""

    # Count packages
    if [ -d "$VENDOR_DIR" ]; then
        PACKAGE_COUNT=$(find "$VENDOR_DIR" -mindepth 2 -maxdepth 2 -type d 2>/dev/null | wc -l)
        echo "📦 Installed packages: $PACKAGE_COUNT"

        # Check vendor size
        if command -v du &> /dev/null; then
            VENDOR_SIZE=$(du -sh "$VENDOR_DIR" 2>/dev/null | cut -f1)
            echo "💾 vendor size: $VENDOR_SIZE"
        fi
    fi

    echo ""
    echo "✅ All checks complete!"
    echo "تم إكمال جميع الفحوصات!"
    echo ""
}

# Run main function
main

# Exit successfully
exit 0

#!/bin/bash

# Cleanup Problematic Windows Path Directories
# This script removes directories with Windows absolute paths

set -e

echo "========================================="
echo "Cleaning Up Problematic Directories"
echo "========================================="
echo ""

# Find and remove directories with Windows paths
echo "Searching for problematic directories..."

# Method 1: Remove specific known problematic directories
if [ -d "C:\\Users\\Gaser\\Desktop\\كوبرا\\storage\\logs" ]; then
    echo "Removing: C:\\Users\\Gaser\\Desktop\\كوبرا\\storage\\logs"
    rm -rf "C:\\Users\\Gaser\\Desktop\\كوبرا\\storage\\logs"
    echo "✓ Removed"
fi

if [ -d "public/C:\\Users\\Gaser\\Desktop\\COPRRA\\storage\\framework\\views" ]; then
    echo "Removing: public/C:\\Users\\Gaser\\Desktop\\COPRRA\\storage\\framework\\views"
    rm -rf "public/C:\\Users\\Gaser\\Desktop\\COPRRA\\storage\\framework\\views"
    echo "✓ Removed"
fi

# Method 2: Find and remove all directories containing "C:" in their path
echo ""
echo "Searching for all directories with Windows paths..."
find . -type d -path "*C:*" 2>/dev/null | while read -r dir; do
    if [ -d "$dir" ]; then
        echo "Found: $dir"
        rm -rf "$dir"
        echo "✓ Removed: $dir"
    fi
done

# Method 3: Clean up any remaining problematic paths in public directory
if [ -d "public" ]; then
    echo ""
    echo "Cleaning public directory..."
    find public -type d -name "*Users*" 2>/dev/null | while read -r dir; do
        if [ -d "$dir" ]; then
            echo "Found: $dir"
            rm -rf "$dir"
            echo "✓ Removed: $dir"
        fi
    done
fi

echo ""
echo "========================================="
echo "Cleanup Complete"
echo "========================================="
echo ""

# Verify cleanup
echo "Verifying cleanup..."
REMAINING=$(find . -type d -path "*C:*" 2>/dev/null | wc -l)
if [ "$REMAINING" -eq 0 ]; then
    echo "✓ All problematic directories removed successfully"
else
    echo "⚠ Warning: $REMAINING problematic directories still remain"
    find . -type d -path "*C:*" 2>/dev/null
fi

echo ""
echo "Next steps:"
echo "1. Clear view cache: php artisan view:clear"
echo "2. Regenerate view cache: php artisan view:cache"
echo "3. Verify storage link: php artisan storage:link"
echo ""


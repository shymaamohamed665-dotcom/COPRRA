<#
.SYNOPSIS
    Reset and prepare test environment for clean test execution

.DESCRIPTION
    This script ensures a clean, isolated test environment by:
    - Clearing all Laravel caches (config, route, view, application)
    - Cleaning up temporary test files
    - Resetting database migrations (optional)
    - Cleaning PHPUnit cache
    - Verifying environment variables

.PARAMETER ResetDatabase
    If specified, runs fresh migrations on the test database

.PARAMETER CleanStorage
    If specified, cleans up storage directories (logs, cache, testing)

.PARAMETER Verbose
    Show detailed output

.EXAMPLE
    .\reset-test-environment.ps1
    Basic reset without database migration

.EXAMPLE
    .\reset-test-environment.ps1 -ResetDatabase -CleanStorage
    Full reset including database and storage cleanup
#>

param(
    [switch]$ResetDatabase,
    [switch]$CleanStorage,
    [switch]$Verbose
)

$ErrorActionPreference = "Continue"
$ProgressPreference = "SilentlyContinue"

function Write-Step {
    param([string]$Message)
    Write-Host "[$([DateTime]::Now.ToString('HH:mm:ss'))] " -NoNewline -ForegroundColor Cyan
    Write-Host $Message -ForegroundColor White
}

function Write-Success {
    param([string]$Message)
    Write-Host "  ✓ " -NoNewline -ForegroundColor Green
    Write-Host $Message -ForegroundColor Gray
}

function Write-Warning {
    param([string]$Message)
    Write-Host "  ⚠ " -NoNewline -ForegroundColor Yellow
    Write-Host $Message -ForegroundColor Gray
}

function Write-Error {
    param([string]$Message)
    Write-Host "  ✗ " -NoNewline -ForegroundColor Red
    Write-Host $Message -ForegroundColor Gray
}

Write-Host "`n═══════════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host "  Test Environment Reset Script" -ForegroundColor Cyan
Write-Host "═══════════════════════════════════════════════════════════════`n" -ForegroundColor Cyan

# Step 1: Clear Laravel caches
Write-Step "Clearing Laravel caches..."
try {
    php artisan cache:clear 2>&1 | Out-Null
    Write-Success "Application cache cleared"
} catch {
    Write-Warning "Failed to clear application cache"
}

try {
    php artisan config:clear 2>&1 | Out-Null
    Write-Success "Configuration cache cleared"
} catch {
    Write-Warning "Failed to clear configuration cache"
}

try {
    php artisan route:clear 2>&1 | Out-Null
    Write-Success "Route cache cleared"
} catch {
    Write-Warning "Failed to clear route cache"
}

try {
    php artisan view:clear 2>&1 | Out-Null
    Write-Success "View cache cleared"
} catch {
    Write-Warning "Failed to clear view cache"
}

# Step 2: Clean PHPUnit cache
Write-Step "Cleaning PHPUnit cache..."
if (Test-Path ".phpunit.cache") {
    try {
        Remove-Item -Path ".phpunit.cache" -Recurse -Force -ErrorAction SilentlyContinue
        Write-Success "PHPUnit cache directory removed"
    } catch {
        Write-Warning "Failed to remove PHPUnit cache"
    }
}

# Step 3: Clean temporary test files
Write-Step "Cleaning temporary test files..."
$tempPaths = @(
    "storage/framework/cache/*",
    "storage/framework/sessions/*",
    "storage/framework/views/*",
    "storage/framework/testing/*"
)

foreach ($path in $tempPaths) {
    if (Test-Path $path) {
        try {
            Get-ChildItem -Path $path -File -ErrorAction SilentlyContinue | Remove-Item -Force -ErrorAction SilentlyContinue
            Write-Success "Cleaned: $path"
        } catch {
            Write-Warning "Failed to clean: $path"
        }
    }
}

# Step 4: Clean storage directories (optional)
if ($CleanStorage) {
    Write-Step "Cleaning storage directories..."
    $storagePaths = @(
        "storage/logs/*.log",
        "storage/app/testing/*",
        "storage/debugbar/*"
    )

    foreach ($path in $storagePaths) {
        if (Test-Path $path) {
            try {
                Remove-Item -Path $path -Force -Recurse -ErrorAction SilentlyContinue
                Write-Success "Cleaned: $path"
            } catch {
                Write-Warning "Failed to clean: $path"
            }
        }
    }
}

# Step 5: Reset database (optional)
if ($ResetDatabase) {
    Write-Step "Resetting test database..."
    try {
        php artisan migrate:fresh --env=testing --force 2>&1 | Out-Null
        Write-Success "Database reset completed"
    } catch {
        Write-Error "Failed to reset database"
    }
}

# Step 6: Verify environment
Write-Step "Verifying environment..."
if (Test-Path ".env") {
    Write-Success ".env file exists"
} else {
    Write-Error ".env file missing"
}

if (Test-Path "phpunit.xml") {
    Write-Success "phpunit.xml configuration exists"
} else {
    Write-Error "phpunit.xml missing"
}

# Step 7: Check composer dependencies
try {
    $composerCheck = php -r "require 'vendor/autoload.php'; echo 'OK';" 2>&1
    if ($composerCheck -match "OK") {
        Write-Success "Composer autoload verified"
    } else {
        Write-Warning "Composer autoload may have issues"
    }
} catch {
    Write-Error "Composer autoload failed"
}

Write-Host "`n═══════════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host "  Environment reset completed!" -ForegroundColor Green
Write-Host "═══════════════════════════════════════════════════════════════`n" -ForegroundColor Cyan

Write-Host "You can now run tests with clean environment:" -ForegroundColor Gray
Write-Host ""
Write-Host "  vendor\bin\phpunit" -ForegroundColor Cyan
Write-Host "  vendor\bin\phpunit --testsuite Feature" -ForegroundColor Cyan
Write-Host "  vendor\bin\phpunit --testsuite Unit" -ForegroundColor Cyan
Write-Host ""

exit 0

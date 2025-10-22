# ==============================================================================
# VENDOR PROTECTION SCRIPT (PowerShell)
# سكريبت حماية مجلد vendor من الحذف العرضي
# ==============================================================================
# This script ensures vendor directory exists and prevents accidental deletion
# ==============================================================================

$ProjectDir = $PSScriptRoot
$VendorDir = Join-Path $ProjectDir "vendor"
$ComposerLock = Join-Path $ProjectDir "composer.lock"

Write-Host "=========================================" -ForegroundColor Cyan
Write-Host "VENDOR PROTECTION & VERIFICATION" -ForegroundColor Cyan
Write-Host "حماية والتحقق من مجلد vendor" -ForegroundColor Cyan
Write-Host "=========================================" -ForegroundColor Cyan
Write-Host ""

# Function to check if vendor exists
function Test-VendorExists {
    if (Test-Path $VendorDir) {
        Write-Host "✅ vendor directory exists" -ForegroundColor Green
        return $true
    } else {
        Write-Host "❌ vendor directory NOT found" -ForegroundColor Red
        return $false
    }
}

# Function to check if composer.lock exists
function Test-ComposerLockExists {
    if (Test-Path $ComposerLock) {
        Write-Host "✅ composer.lock exists (dependencies locked)" -ForegroundColor Green
        return $true
    } else {
        Write-Host "⚠️  composer.lock NOT found (dependencies not locked)" -ForegroundColor Yellow
        return $false
    }
}

# Function to install vendor
function Install-Vendor {
    Write-Host ""
    Write-Host "📦 Installing Composer dependencies..." -ForegroundColor Cyan
    Write-Host "هذا قد يستغرق عدة دقائق..." -ForegroundColor Cyan

    if (Get-Command composer -ErrorAction SilentlyContinue) {
        $process = Start-Process -FilePath "composer" -ArgumentList "install --no-interaction --prefer-dist --optimize-autoloader" -Wait -PassThru -NoNewWindow

        if ($process.ExitCode -eq 0) {
            Write-Host "✅ Dependencies installed successfully!" -ForegroundColor Green
            Write-Host "تم تثبيت الاعتماديات بنجاح!" -ForegroundColor Green
            return $true
        } else {
            Write-Host "❌ Failed to install dependencies" -ForegroundColor Red
            Write-Host "فشل تثبيت الاعتماديات" -ForegroundColor Red
            return $false
        }
    } else {
        Write-Host "❌ Composer not found. Please install Composer first." -ForegroundColor Red
        Write-Host "Composer غير موجود. يرجى تثبيت Composer أولاً." -ForegroundColor Red
        return $false
    }
}

# Function to create vendor marker file
function New-VendorMarker {
    if (Test-Path $VendorDir) {
        $markerFile = Join-Path $VendorDir ".vendor-protected"
        @"
Created by vendor protection script
Date: $(Get-Date)
"@ | Out-File -FilePath $markerFile -Encoding UTF8
        Write-Host "✅ Created vendor protection marker" -ForegroundColor Green
    }
}

# Main protection logic
Write-Host "🔍 Checking vendor status..." -ForegroundColor Cyan
Write-Host ""

# Check composer.lock first
if (-not (Test-ComposerLockExists)) {
    Write-Host ""
    Write-Host "⚠️  WARNING: No composer.lock found!" -ForegroundColor Yellow
    Write-Host "This means dependencies are not locked and may vary between installations." -ForegroundColor Yellow
    Write-Host ""
}

# Check vendor directory
if (-not (Test-VendorExists)) {
    Write-Host ""
    Write-Host "❌ CRITICAL: vendor directory is missing!" -ForegroundColor Red
    Write-Host ""
    Write-Host "This is required for the project to function." -ForegroundColor Yellow
    Write-Host "Attempting automatic installation..." -ForegroundColor Cyan
    Write-Host ""

    if (Install-Vendor) {
        New-VendorMarker
        Write-Host ""
        Write-Host "✅ Vendor protection complete!" -ForegroundColor Green
    } else {
        Write-Host ""
        Write-Host "❌ Automatic installation failed." -ForegroundColor Red
        Write-Host "Please run: composer install" -ForegroundColor Yellow
        exit 1
    }
} else {
    # Vendor exists, just verify it's complete
    Write-Host "✅ vendor directory is present" -ForegroundColor Green

    # Check if autoload.php exists (critical file)
    $autoloadFile = Join-Path $VendorDir "autoload.php"
    if (Test-Path $autoloadFile) {
        Write-Host "✅ vendor/autoload.php exists (vendor is functional)" -ForegroundColor Green
    } else {
        Write-Host "⚠️  vendor/autoload.php missing - vendor may be corrupted" -ForegroundColor Yellow
        Write-Host "📦 Reinstalling dependencies..." -ForegroundColor Cyan

        if (Install-Vendor) {
            New-VendorMarker
            Write-Host "✅ Vendor reinstalled successfully!" -ForegroundColor Green
        } else {
            Write-Host "❌ Failed to reinstall vendor" -ForegroundColor Red
            exit 1
        }
    }

    New-VendorMarker
}

Write-Host ""
Write-Host "=========================================" -ForegroundColor Cyan
Write-Host "VENDOR HEALTH STATUS" -ForegroundColor Cyan
Write-Host "حالة صحة vendor" -ForegroundColor Cyan
Write-Host "=========================================" -ForegroundColor Cyan
Write-Host ""

# Count packages
if (Test-Path $VendorDir) {
    $packageCount = (Get-ChildItem -Path $VendorDir -Directory | Get-ChildItem -Directory).Count
    Write-Host "📦 Installed packages: $packageCount" -ForegroundColor Cyan

    # Check vendor size
    $vendorSize = (Get-ChildItem -Path $VendorDir -Recurse -File | Measure-Object -Property Length -Sum).Sum / 1MB
    Write-Host "💾 vendor size: $([math]::Round($vendorSize, 2)) MB" -ForegroundColor Cyan
}

Write-Host ""
Write-Host "✅ All checks complete!" -ForegroundColor Green
Write-Host "تم إكمال جميع الفحوصات!" -ForegroundColor Green
Write-Host ""

exit 0

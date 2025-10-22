# Xdebug Installation Script for PHP 8.4.13 NTS x64 VS17
# This script automates Xdebug installation for code coverage support

Write-Host "================================" -ForegroundColor Cyan
Write-Host "Xdebug Installation for PHP 8.4" -ForegroundColor Cyan
Write-Host "================================" -ForegroundColor Cyan
Write-Host ""

# Configuration
$xdebugUrl = "https://xdebug.org/files/php_xdebug-3.5.0alpha2-8.4-nts-vs17-x86_64.dll"
$phpExtDir = "C:\tools\php84\ext"
$phpIniPath = "C:\tools\php84\php.ini"
$xdebugDllPath = Join-Path $phpExtDir "php_xdebug.dll"

# Step 1: Verify PHP installation
Write-Host "[1/5] Verifying PHP installation..." -ForegroundColor Yellow
try {
    $phpVersion = & php -v 2>&1 | Select-String "PHP 8.4"
    if ($phpVersion) {
        Write-Host "  [OK] PHP 8.4 detected" -ForegroundColor Green
    } else {
        Write-Host "  [ERROR] PHP 8.4 not found" -ForegroundColor Red
        exit 1
    }
} catch {
    Write-Host "  [ERROR] PHP not found in PATH" -ForegroundColor Red
    exit 1
}

# Step 2: Download Xdebug
Write-Host "[2/5] Downloading Xdebug 3.5.0alpha2..." -ForegroundColor Yellow
try {
    # Enable TLS 1.2 and 1.3
    [Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12 -bor [Net.SecurityProtocolType]::Tls13

    # Download with progress
    $ProgressPreference = 'SilentlyContinue'
    Invoke-WebRequest -Uri $xdebugUrl -OutFile $xdebugDllPath -UseBasicParsing -ErrorAction Stop

    Write-Host "  [OK] Downloaded successfully" -ForegroundColor Green
    Write-Host "    Location: $xdebugDllPath" -ForegroundColor Gray
} catch {
    Write-Host "  [ERROR] Download failed: $_" -ForegroundColor Red
    Write-Host ""
    Write-Host "Alternative: Manual Download" -ForegroundColor Cyan
    Write-Host "1. Open: $xdebugUrl" -ForegroundColor White
    Write-Host "2. Save to: $xdebugDllPath" -ForegroundColor White
    Write-Host "3. Run this script again" -ForegroundColor White
    exit 1
}

# Step 3: Verify DLL file
Write-Host "[3/5] Verifying DLL file..." -ForegroundColor Yellow
if (Test-Path $xdebugDllPath) {
    $fileSize = (Get-Item $xdebugDllPath).Length
    Write-Host "  [OK] DLL file exists ($fileSize bytes)" -ForegroundColor Green
} else {
    Write-Host "  [ERROR] DLL file not found" -ForegroundColor Red
    exit 1
}

# Step 4: Configure php.ini
Write-Host "[4/5] Configuring php.ini..." -ForegroundColor Yellow
if (Test-Path $phpIniPath) {
    # Check if Xdebug is already configured
    $iniContent = Get-Content $phpIniPath -Raw

    if ($iniContent -match "zend_extension.*xdebug") {
        Write-Host "  [INFO] Xdebug already configured in php.ini" -ForegroundColor Cyan
    } else {
        # Add Xdebug configuration
        $xdebugConfig = @"

[Xdebug]
zend_extension=php_xdebug.dll
xdebug.mode=coverage
xdebug.output_dir="C:\Users\Gaser\Desktop\COPRRA\storage\logs"
"@
        Add-Content -Path $phpIniPath -Value $xdebugConfig
        Write-Host "  [OK] Xdebug configuration added to php.ini" -ForegroundColor Green
    }
} else {
    Write-Host "  [ERROR] php.ini not found at: $phpIniPath" -ForegroundColor Red
    Write-Host "    Please configure manually" -ForegroundColor Yellow
}

# Step 5: Verify installation
Write-Host "[5/5] Verifying Xdebug installation..." -ForegroundColor Yellow
$xdebugCheck = & php -m 2>&1 | Select-String -Pattern "xdebug" -CaseSensitive:$false
if ($xdebugCheck) {
    Write-Host "  [OK] Xdebug successfully installed and loaded!" -ForegroundColor Green

    # Show Xdebug version
    $xdebugVersion = & php -r "echo phpversion('xdebug');" 2>&1
    Write-Host "    Version: $xdebugVersion" -ForegroundColor Gray
} else {
    Write-Host "  [WARNING] Xdebug not loaded" -ForegroundColor Red
    Write-Host "    Try restarting your terminal/IDE" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "================================" -ForegroundColor Cyan
Write-Host "Installation Complete!" -ForegroundColor Green
Write-Host "================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Next steps:" -ForegroundColor Cyan
Write-Host "1. Restart your terminal/IDE" -ForegroundColor White
Write-Host "2. Verify: php -m | findstr xdebug" -ForegroundColor White
Write-Host "3. Run tests with coverage:" -ForegroundColor White
Write-Host "   ./vendor/bin/phpunit --coverage-html=storage/logs/coverage" -ForegroundColor Gray
Write-Host ""

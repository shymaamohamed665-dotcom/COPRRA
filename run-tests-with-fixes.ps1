# PowerShell - COPRRA Test Runner with Auto-Fixing
# Runs code formatting, static analysis, and all test suites

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  COPRRA - Test Runner with Auto-Fix" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Step 1: Check for PHP and PHPUnit
Write-Host "[1/4] Checking requirements..." -ForegroundColor Yellow
if (-not (Get-Command php -ErrorAction SilentlyContinue)) {
    Write-Error "PHP is not installed or not in PATH"
    exit 1
}

if (-not (Test-Path "vendor\bin\phpunit")) {
    Write-Error "PHPUnit not found. Please run: composer install"
    exit 1
}

Write-Host "  OK - PHP and PHPUnit are available" -ForegroundColor Green
Write-Host ""

# Step 2: Format code using Laravel Pint
Write-Host "[2/4] Formatting code with Laravel Pint..." -ForegroundColor Yellow
if (-not (Test-Path "vendor\bin\pint")) {
    Write-Warning "Laravel Pint not found. Skipping formatting step."
} else {
    php .\vendor\bin\pint
    if ($LASTEXITCODE -eq 0) {
        Write-Host "  OK - Code formatted successfully" -ForegroundColor Green
    } else {
        Write-Warning "Code formatting failed, but continuing with tests"
    }
}
Write-Host ""

# Step 3: Run static analysis with PHPStan
Write-Host "[3/4] Running static analysis with PHPStan..." -ForegroundColor Yellow
if (-not (Test-Path "vendor\bin\phpstan")) {
    Write-Warning "PHPStan not found. Skipping analysis step."
} else {
    php .\vendor\bin\phpstan analyse --memory-limit=1G
    if ($LASTEXITCODE -eq 0) {
        Write-Host "  OK - Static analysis passed" -ForegroundColor Green
    } else {
        Write-Warning "Static analysis found issues, but continuing with tests"
    }
}
Write-Host ""

# Step 4: Run test suites
Write-Host "[4/4] Running test suites..." -ForegroundColor Yellow
Write-Host ""

$testSuites = @(
    @{Name="AI"; Filter="AITest"; Description="AI Tests"},
    @{Name="Security"; Filter="SecurityTest"; Description="Security Tests"},
    @{Name="Performance"; Filter="PerformanceTest"; Description="Performance Tests"},
    @{Name="Integration"; Filter="IntegrationTest"; Description="Integration Tests"},
    @{Name="Unit"; Filter="UnitTest"; Description="Unit Tests"},
    @{Name="Feature"; Filter="FeatureTest"; Description="Feature Tests"}
)

$passedTests = 0
$totalTests = $testSuites.Count

foreach ($suite in $testSuites) {
    Write-Host "  Running: $($suite.Description) ($($suite.Name))..." -ForegroundColor Cyan

    $command = "php .\vendor\bin\phpunit --testsuite $($suite.Name) --filter $($suite.Filter) --stop-on-failure"
    Invoke-Expression $command

    if ($LASTEXITCODE -eq 0) {
        Write-Host "    PASSED" -ForegroundColor Green
        $passedTests++
    } else {
        Write-Host "    FAILED" -ForegroundColor Red
        Write-Error "Test suite $($suite.Name) failed. Stopping execution."
        break
    }
    Write-Host ""
}

# Final results
Write-Host "========================================" -ForegroundColor Cyan
if ($passedTests -eq $totalTests) {
    Write-Host "  SUCCESS: All tests passed! ($passedTests/$totalTests)" -ForegroundColor Green
    Write-Host "========================================" -ForegroundColor Cyan
    exit 0
} else {
    Write-Host "  FAILURE: Some tests failed ($passedTests/$totalTests)" -ForegroundColor Red
    Write-Host "========================================" -ForegroundColor Cyan
    exit 1
}

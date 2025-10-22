<#
.SYNOPSIS
    Run PHPUnit tests with isolated, clean environment

.DESCRIPTION
    This script ensures each test suite runs in a clean, isolated environment by:
    1. Resetting environment before tests
    2. Running requested test suite(s)
    3. Reporting results
    4. Optional: Running all suites sequentially with isolation

.PARAMETER Suite
    Specific test suite to run (Unit, Feature, AI, Security, Performance, Integration)
    If omitted, runs all suites

.PARAMETER StopOnFailure
    Stop execution on first test failure

.PARAMETER Coverage
    Generate code coverage report (slower)

.PARAMETER Verbose
    Show detailed output

.EXAMPLE
    .\run-tests-isolated.ps1 -Suite Feature
    Run only Feature tests with environment reset

.EXAMPLE
    .\run-tests-isolated.ps1
    Run all test suites sequentially with isolation

.EXAMPLE
    .\run-tests-isolated.ps1 -Suite Unit -StopOnFailure -Verbose
    Run Unit tests, stop on failure, with verbose output
#>

param(
    [string]$Suite = "",
    [switch]$StopOnFailure,
    [switch]$Coverage,
    [switch]$Verbose
)

$ErrorActionPreference = "Continue"
$ProgressPreference = "SilentlyContinue"

# Colors
function Write-Title {
    param([string]$Message)
    Write-Host "`n═══════════════════════════════════════════════════════════════" -ForegroundColor Cyan
    Write-Host "  $Message" -ForegroundColor Cyan
    Write-Host "═══════════════════════════════════════════════════════════════`n" -ForegroundColor Cyan
}

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

function Write-Failure {
    param([string]$Message)
    Write-Host "  ✗ " -NoNewline -ForegroundColor Red
    Write-Host $Message -ForegroundColor Gray
}

Write-Title "Isolated Test Runner"

# Define test suites
$testSuites = @("AI", "Security", "Performance", "Integration", "Unit", "Feature")

# If specific suite requested, use that
if ($Suite) {
    if ($testSuites -contains $Suite) {
        $testSuites = @($Suite)
        Write-Host "Running test suite: $Suite`n" -ForegroundColor Yellow
    } else {
        Write-Host "Invalid suite: $Suite" -ForegroundColor Red
        Write-Host "Valid suites: $($testSuites -join ', ')`n" -ForegroundColor Gray
        exit 1
    }
} else {
    Write-Host "Running all test suites sequentially with isolation`n" -ForegroundColor Yellow
}

# Results tracking
$results = @{}
$totalTests = 0
$totalFailures = 0
$totalErrors = 0
$totalSkipped = 0

foreach ($testSuite in $testSuites) {
    Write-Title "Test Suite: $testSuite"

    # Step 1: Reset environment
    Write-Step "Resetting test environment..."
    try {
        & "$PSScriptRoot\reset-test-environment.ps1" -ErrorAction SilentlyContinue
        Write-Success "Environment reset completed"
    } catch {
        Write-Failure "Environment reset failed"
    }

    # Step 2: Run tests
    Write-Step "Running $testSuite tests..."

    $phpunitArgs = @("--testsuite", $testSuite)

    if ($StopOnFailure) {
        $phpunitArgs += "--stop-on-failure"
    }

    if ($Coverage) {
        $coverageDir = "storage\logs\coverage\$testSuite"
        $phpunitArgs += @("--coverage-html", $coverageDir)
    }

    if ($Verbose) {
        $phpunitArgs += "--verbose"
    }

    $startTime = Get-Date
    $output = & ".\vendor\bin\phpunit.bat" @phpunitArgs 2>&1

    $duration = ((Get-Date) - $startTime).TotalSeconds

    # Parse results
    $testsMatch = [regex]::Match($output -join "`n", "Tests:\s+(\d+)")
    $failuresMatch = [regex]::Match($output -join "`n", "Failures:\s+(\d+)")
    $errorsMatch = [regex]::Match($output -join "`n", "Errors:\s+(\d+)")
    $skippedMatch = [regex]::Match($output -join "`n", "Skipped:\s+(\d+)")

    $tests = if ($testsMatch.Success) { [int]$testsMatch.Groups[1].Value } else { 0 }
    $failures = if ($failuresMatch.Success) { [int]$failuresMatch.Groups[1].Value } else { 0 }
    $errors = if ($errorsMatch.Success) { [int]$errorsMatch.Groups[1].Value } else { 0 }
    $skipped = if ($skippedMatch.Success) { [int]$skippedMatch.Groups[1].Value } else { 0 }

    $results[$testSuite] = @{
        Tests = $tests
        Failures = $failures
        Errors = $errors
        Skipped = $skipped
        Duration = $duration
        Success = ($failures -eq 0 -and $errors -eq 0)
    }

    $totalTests += $tests
    $totalFailures += $failures
    $totalErrors += $errors
    $totalSkipped += $skipped

    # Report suite result
    if ($results[$testSuite].Success) {
        Write-Success "$testSuite: $tests tests passed in $([math]::Round($duration, 2))s"
    } else {
        Write-Failure "$testSuite: $failures failures, $errors errors out of $tests tests"
    }

    # Stop if requested and there are failures
    if ($StopOnFailure -and -not $results[$testSuite].Success) {
        Write-Host "`nStopping due to failures in $testSuite suite`n" -ForegroundColor Red
        break
    }
}

# Final summary
Write-Title "Test Results Summary"

Write-Host "Suite Results:" -ForegroundColor Yellow
Write-Host ("=" * 70) -ForegroundColor Gray

foreach ($suite in $results.Keys | Sort-Object) {
    $result = $results[$suite]
    $status = if ($result.Success) { "✓ PASS" } else { "✗ FAIL" }
    $color = if ($result.Success) { "Green" } else { "Red" }

    Write-Host "  $status " -NoNewline -ForegroundColor $color
    Write-Host "$suite".PadRight(20) -NoNewline -ForegroundColor White
    Write-Host "Tests: $($result.Tests)".PadRight(15) -NoNewline -ForegroundColor Gray

    if ($result.Failures -gt 0) {
        Write-Host "Failures: $($result.Failures) " -NoNewline -ForegroundColor Red
    }
    if ($result.Errors -gt 0) {
        Write-Host "Errors: $($result.Errors) " -NoNewline -ForegroundColor Red
    }
    if ($result.Skipped -gt 0) {
        Write-Host "Skipped: $($result.Skipped) " -NoNewline -ForegroundColor Yellow
    }

    Write-Host "($([math]::Round($result.Duration, 2))s)" -ForegroundColor Cyan
}

Write-Host ("=" * 70) -ForegroundColor Gray

# Overall stats
$overallSuccess = ($totalFailures -eq 0 -and $totalErrors -eq 0)
$successColor = if ($overallSuccess) { "Green" } else { "Red" }

Write-Host "`nOverall:" -ForegroundColor Yellow
Write-Host "  Total Tests: $totalTests" -ForegroundColor White
Write-Host "  Failures: $totalFailures" -ForegroundColor $(if ($totalFailures -eq 0) { "Green" } else { "Red" })
Write-Host "  Errors: $totalErrors" -ForegroundColor $(if ($totalErrors -eq 0) { "Green" } else { "Red" })
Write-Host "  Skipped: $totalSkipped" -ForegroundColor Yellow

if ($overallSuccess) {
    Write-Host "`n  ✓ All tests passed successfully!" -ForegroundColor Green
} else {
    Write-Host "`n  ✗ Some tests failed" -ForegroundColor Red
}

Write-Host ""

exit $(if ($overallSuccess) { 0 } else { 1 })

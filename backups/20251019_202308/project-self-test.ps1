# PROJECT SELF-TESTING SCRIPT
# سكريبت فحص المشروع باستخدام اختباراته الخاصة
# Using Project's Own Tests and Tools for Analysis
# Version: 1.0 - Ready to Copy to Notepad

$ReportFile = "PROJECT_SELF_ANALYSIS_REPORT.md"
$StartTime = Get-Date

Write-Host "========================================" -ForegroundColor Magenta
Write-Host "PROJECT SELF-ANALYSIS USING OWN TESTS" -ForegroundColor Magenta
Write-Host "Using 300+ tests from tests/ directory" -ForegroundColor Magenta
Write-Host "========================================" -ForegroundColor Magenta

# Initialize report
Set-Content -Path $ReportFile -Value "# PROJECT SELF-ANALYSIS REPORT" -Force
Add-Content -Path $ReportFile -Value "## Using Project's Own Tests and Tools"
Add-Content -Path $ReportFile -Value "## Generated: $($StartTime.ToString('yyyy-MM-dd HH:mm:ss'))"
Add-Content -Path $ReportFile -Value ""
Add-Content -Path $ReportFile -Value "---"

function Write-TestSection {
    param(
        [string]$Title,
        [object]$TestResult,
        [string]$AdditionalInfo = ""
    )

    $status = if ($TestResult.Success) { "✅ PASSED" } else { "❌ FAILED" }
    $color = if ($TestResult.Success) { "Green" } else { "Red" }

    Write-Host "[$status] $Title" -ForegroundColor $color

    $section = @"
## $Title
**Status:** $status
**Exit Code:** $($TestResult.ExitCode)
**Duration:** $(if ($TestResult.Duration) { $TestResult.Duration } else { "N/A" })

$AdditionalInfo

### Output:
``````
$($TestResult.Output | Out-String)
``````

---

"@

    Add-Content -Path $ReportFile -Value $section
}

function Run-ProjectTest {
    param(
        [string]$Command,
        [string]$Description
    )

    $startTime = Get-Date
    try {
        Write-Host "Running: $Description..." -ForegroundColor Cyan
        $output = Invoke-Expression -Command $Command 2>&1
        $exitCode = $LASTEXITCODE
        $endTime = Get-Date
        $duration = New-TimeSpan -Start $startTime -End $endTime

        return @{
            Output      = $output
            ExitCode    = $exitCode
            Success     = ($exitCode -eq 0)
            Description = $Description
            Duration    = "$($duration.TotalSeconds) seconds"
        }
    }
    catch {
        $endTime = Get-Date
        $duration = New-TimeSpan -Start $startTime -End $endTime
        return @{
            Output      = "EXCEPTION: $($_.Exception.Message)"
            ExitCode    = -1
            Success     = $false
            Description = $Description
            Duration    = "$($duration.TotalSeconds) seconds"
            Error       = $true
        }
    }
}

Write-Host "`n=== DISCOVERING PROJECT'S TESTS ===" -ForegroundColor Blue

# 1. Discover all test files in the project
Write-Host "Discovering test files..." -ForegroundColor Yellow
$testFiles = Get-ChildItem -Path tests -Recurse -Filter "*Test.php" -ErrorAction SilentlyContinue
$featureTests = Get-ChildItem -Path "tests/Feature" -Recurse -Filter "*Test.php" -ErrorAction SilentlyContinue
$unitTests = Get-ChildItem -Path "tests/Unit" -Recurse -Filter "*Test.php" -ErrorAction SilentlyContinue

$uniqueTestFiles = $testFiles | Group-Object Name | ForEach-Object { $_.Group[0] }
$uniqueFeatureTests = $featureTests | Group-Object Name | ForEach-Object { $_.Group[0] }
$uniqueUnitTests = $unitTests | Group-Object Name | ForEach-Object { $_.Group[0] }

$discoveryInfo = @"
### Test Discovery Results
- **Total Test Files Found:** $($uniqueTestFiles.Count)
- **Feature Tests:** $($uniqueFeatureTests.Count)
- **Unit Tests:** $($uniqueUnitTests.Count)

### Test Files Structure:
``````
$(if ($uniqueTestFiles.Count -gt 0) { $uniqueTestFiles | Sort-Object Name | Format-Table @{Name="Test File";Expression={$_.Name}}, @{Name="Type";Expression={if ($_.FullName -like "*Feature*") {"Feature"} elseif ($_.FullName -like "*Unit*") {"Unit"} else {"Other"}}}, @{Name="Size(KB)";Expression={[math]::Round($_.Length/1KB,1)}} -AutoSize | Out-String } else { "❌ No test files found in tests/ directory!" })
``````
"@

Add-Content -Path $ReportFile -Value $discoveryInfo

Write-Host "Found $($testFiles.Count) test files" -ForegroundColor Green

# 2. Run all tests to verify project functionality
Write-Host "`n=== RUNNING PROJECT'S OWN TESTS ===" -ForegroundColor Blue

# Test 1: Run all tests together
$allTests = Run-ProjectTest "php vendor/bin/phpunit --testdox" "Complete Test Suite Execution"
Write-TestSection -Title "COMPLETE PROJECT TEST SUITE" -TestResult $allTests -AdditionalInfo "This runs ALL your 300+ tests to verify project functionality"

# Test 2: Run Feature Tests separately
$featureTestsRun = Run-ProjectTest "php vendor/bin/phpunit tests/Feature --testdox" "Feature Tests Only"
Write-TestSection -Title "FEATURE TESTS ANALYSIS" -TestResult $featureTestsRun -AdditionalInfo "Tests that verify complete features work correctly"

# Test 3: Run Unit Tests separately
$unitTestsRun = Run-ProjectTest "php vendor/bin/phpunit tests/Unit --testdox" "Unit Tests Only"
Write-TestSection -Title "UNIT TESTS ANALYSIS" -TestResult $unitTestsRun -AdditionalInfo "Tests that verify individual components work correctly"

# 3. Test specific critical components based on typical Laravel structure
Write-Host "`n=== TESTING CRITICAL PROJECT COMPONENTS ===" -ForegroundColor Blue

# Test Controllers
$controllerTests = Run-ProjectTest "php vendor/bin/phpunit tests/Feature/Http/Controllers --testdox" "Controller Tests"
Write-TestSection -Title "CONTROLLER FUNCTIONALITY TESTS" -TestResult $controllerTests -AdditionalInfo "Verifies all your API endpoints and web controllers work correctly"

# Test Models
$modelTests = Run-ProjectTest "php vendor/bin/phpunit tests/Unit/Models --testdox" "Model Tests"
Write-TestSection -Title "MODEL FUNCTIONALITY TESTS" -TestResult $modelTests -AdditionalInfo "Verifies all your Eloquent models and database interactions work correctly"

# Test Middleware
$middlewareTests = Run-ProjectTest "php vendor/bin/phpunit tests/Feature/Http/Middleware --testdox" "Middleware Tests"
Write-TestSection -Title "MIDDLEWARE FUNCTIONALITY TESTS" -TestResult $middlewareTests -AdditionalInfo "Verifies all your custom middleware works correctly"

# Test Console Commands
$consoleTests = Run-ProjectTest "php vendor/bin/phpunit tests/Feature/Console --testdox" "Console Command Tests"
Write-TestSection -Title "CONSOLE COMMANDS TESTS" -TestResult $consoleTests -AdditionalInfo "Verifies all your Artisan commands work correctly"

# 4. Run tests with coverage to see what's actually being tested
Write-Host "`n=== ANALYZING TEST COVERAGE ===" -ForegroundColor Blue

$coverageTest = Run-ProjectTest "php vendor/bin/phpunit --coverage-text --coverage-html=coverage-report" "Test Coverage Analysis"
Write-TestSection -Title "TEST COVERAGE ANALYSIS" -TestResult $coverageTest -AdditionalInfo "Shows which parts of your code are being tested by your own tests"

# 5. Run tests in different modes to catch different issues
Write-Host "`n=== RUNNING TESTS IN DIFFERENT MODES ===" -ForegroundColor Blue

# Test with stop on failure to see first failure
$stopOnFailure = Run-ProjectTest "php vendor/bin/phpunit --stop-on-failure" "Tests with Stop on Failure"
Write-TestSection -Title "FIRST FAILURE DETECTION" -TestResult $stopOnFailure -AdditionalInfo "Stops at first failure to identify the root cause quickly"

# Test with verbose output for detailed information
$verboseTest = Run-ProjectTest "php vendor/bin/phpunit --debug" "Tests with Debug Mode"
Write-TestSection -Title "DEBUG MODE TESTING" -TestResult $verboseTest -AdditionalInfo "Runs tests with maximum debugging information"

# 6. Analyze specific test groups if they exist
Write-Host "`n=== ANALYZING TEST GROUPS ===" -ForegroundColor Blue

$testGroups = Run-ProjectTest "php vendor/bin/phpunit --list-groups" "Available Test Groups"
Write-TestSection -Title "TEST GROUPS ANALYSIS" -TestResult $testGroups -AdditionalInfo "Shows all test groups defined in your project"

# 7. Test Database functionality specifically
Write-Host "`n=== DATABASE-RELATED TESTS ===" -ForegroundColor Blue

$databaseTests = Run-ProjectTest "php vendor/bin/phpunit --filter Database" "Database Tests"
Write-TestSection -Title "DATABASE FUNCTIONALITY TESTS" -TestResult $databaseTests -AdditionalInfo "Tests specifically related to database operations"

# 8. API Tests if they exist
Write-Host "`n=== API FUNCTIONALITY TESTS ===" -ForegroundColor Blue

$apiTests = Run-ProjectTest "php vendor/bin/phpunit tests/Feature/Api --testdox" "API Tests"
Write-TestSection -Title "API ENDPOINTS TESTS" -TestResult $apiTests -AdditionalInfo "Tests all your API endpoints to ensure they work correctly"

# 9. Performance Tests
Write-Host "`n=== PERFORMANCE ANALYSIS THROUGH TESTS ===" -ForegroundColor Blue

$performanceTests = Run-ProjectTest "php vendor/bin/phpunit --filter Performance" "Performance Tests"
Write-TestSection -Title "PERFORMANCE TESTS" -TestResult $performanceTests -AdditionalInfo "Tests that check performance aspects of your application"

# 10. Generate Final Analysis
Write-Host "`n=== GENERATING FINAL ANALYSIS ===" -ForegroundColor Blue

$EndTime = Get-Date
$Duration = New-TimeSpan -Start $StartTime -End $EndTime

# Count results
$allTestResults = @($allTests, $featureTestsRun, $unitTestsRun, $controllerTests, $modelTests, $middlewareTests, $consoleTests, $coverageTest)
$passedTests = ($allTestResults | Where-Object { $_.Success }).Count
$failedTests = ($allTestResults | Where-Object { !$_.Success }).Count

$finalAnalysis = @"

# FINAL PROJECT SELF-ANALYSIS SUMMARY

## Test Execution Statistics
- **Total Analysis Duration:** $($Duration.TotalMinutes) minutes ($($Duration.TotalSeconds) seconds)
- **Test Files Analyzed:** $($testFiles.Count)
- **Test Categories Passed:** $passedTests
- **Test Categories Failed:** $failedTests

## Project Health Assessment (Based on Your Own Tests)

### ✅ STRENGTHS DETECTED BY YOUR TESTS:
$(if ($allTests.Success) { "- Complete test suite passes - your project works correctly!" } else { "- Some tests are failing - needs attention" })
$(if ($featureTestsRun.Success) { "- All features work as expected" } else { "- Some features have issues" })
$(if ($unitTestsRun.Success) { "- All individual components work correctly" } else { "- Some components need fixing" })

### 🔍 ISSUES DETECTED BY YOUR TESTS:
$(if (!$allTests.Success) { "- Main test suite failures indicate core functionality problems" } else { "" })
$(if (!$featureTestsRun.Success) { "- Feature test failures indicate user-facing issues" } else { "" })
$(if (!$unitTestsRun.Success) { "- Unit test failures indicate component-level problems" } else { "" })

### 📊 TEST COVERAGE INSIGHTS:
- Your tests are covering the functionality they were designed to test
- Coverage report generated in: coverage-report/ directory
- Use this to see which parts of your code need more tests

## GITHUB ACTIONS READINESS:
$(if ($allTests.Success -and $featureTestsRun.Success -and $unitTestsRun.Success) {
    "🎉 **READY FOR GITHUB ACTIONS!** Your tests pass, indicating your project is stable for deployment."
} else {
    "⚠️ **NOT READY YET** - Fix the failing tests before deploying to GitHub Actions."
})

## RECOMMENDED GITHUB ACTIONS WORKFLOW:
``````yaml
name: Laravel Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
    - uses: actions/checkout@v3

    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.2'

    - name: Install Dependencies
      run: composer install --no-dev --optimize-autoloader

    - name: Run Your Feature Tests
      run: php vendor/bin/phpunit tests/Feature --testdox

    - name: Run Your Unit Tests
      run: php vendor/bin/phpunit tests/Unit --testdox

    - name: Run Complete Test Suite
      run: php vendor/bin/phpunit --testdox
``````

## NEXT STEPS:
1. **Fix any failing tests** - your tests know your project best
2. **Review coverage report** - see what's not being tested
3. **Add tests for uncovered areas** - strengthen your test suite
4. **Run this analysis again** - ensure all tests pass before deployment

---
*Analysis completed using your project's own testing framework*
*Your tests are the best indicator of your project's health!*
"@

Add-Content -Path $ReportFile -Value $finalAnalysis

Write-Host "`n========================================" -ForegroundColor Magenta
Write-Host "SELF-ANALYSIS COMPLETE!" -ForegroundColor Green
Write-Host "Report saved to: $ReportFile" -ForegroundColor Green
Write-Host "Tests Passed: $passedTests" -ForegroundColor $(if ($passedTests -gt $failedTests) { "Green" } else { "Yellow" })
Write-Host "Tests Failed: $failedTests" -ForegroundColor $(if ($failedTests -gt 0) { "Red" } else { "Green" })
Write-Host "Your tests are the best judge of your project!" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Magenta

# Show immediate actionable results
if ($allTests.Success) {
    Write-Host "`n🎉 GREAT NEWS! Your main test suite passes!" -ForegroundColor Green
    Write-Host "This means your project functionality is working correctly." -ForegroundColor Green
}
else {
    Write-Host "`n⚠️ ATTENTION NEEDED: Some tests are failing." -ForegroundColor Yellow
    Write-Host "Your own tests have detected issues that need fixing." -ForegroundColor Yellow
}

Write-Host "`nCheck the detailed report for complete analysis." -ForegroundColor Cyan

# ============================
# audit-full.ps1 - Ultimate Full Project Audit
# ============================

# ---------------------------
# Variables
# ---------------------------
$ProjectDir = Get-Location
$timestamp = Get-Date -Format "yyyyMMdd-HHmmss"
$ReportFile = Join-Path $ProjectDir "audit-full-report-$timestamp.txt"

# ---------------------------
# Helper Functions
# ---------------------------
function Log-Write {
    param([string]$Text)
    Write-Host $Text
    Add-Content -Path $ReportFile -Value $Text
}

function Has-Command {
    param([string]$cmd)
    return Get-Command $cmd -ErrorAction SilentlyContinue -CommandType Application, Cmdlet, Function
}

function Run-Tool {
    param(
        [string]$Title,
        [scriptblock]$Command
    )
    Log-Write "`n--- Running: $Title ---`n"
    try {
        & $Command
    } catch {
        $msg = $_.Exception.Message
        Log-Write ("Error running " + $Title + ": " + $msg)
    }
}

# ---------------------------
# Secrets Scan (Gitleaks)
# ---------------------------
Run-Tool -Title "Secrets Scan (Gitleaks)" -Command {
    if (Has-Command gitleaks) {
        gitleaks detect --no-git --report-format json | ForEach-Object { Log-Write $_ }
    } else {
        Log-Write "Gitleaks not found. Please install it."
    }
}

# ---------------------------
# Composer Audit
# ---------------------------
Run-Tool -Title "Composer Audit (Security)" -Command {
    if (Has-Command composer) {
        composer audit --locked | ForEach-Object { Log-Write $_ }
    } else {
        Log-Write "Composer not found"
    }
}

# ---------------------------
# NPM Audit
# ---------------------------
Run-Tool -Title "NPM Audit (Security)" -Command {
    if ((Test-Path "package.json") -and (Has-Command npm)) {
        npm audit | ForEach-Object { Log-Write $_ }
    } else {
        Log-Write "npm or package.json not found"
    }
}

# ---------------------------
# PHP Syntax Check (All PHP files)
# ---------------------------
Run-Tool -Title "PHP Syntax Check (Fatal Errors)" -Command {
    if (Has-Command php) {
        Get-ChildItem -Path $ProjectDir -Recurse -Include *.php -File | ForEach-Object {
            $out = php -l $_.FullName 2>&1
            if ($out -notmatch "No syntax errors detected") { Log-Write $out }
        }
    } else {
        Log-Write "php not found"
    }
}

# ---------------------------
# Missing .env file
# ---------------------------
Run-Tool -Title "Missing .env file" -Command {
    if (-not (Test-Path ".env")) {
        Log-Write ".env file is missing. Copy .env.example to .env and configure it."
    }
}

# ---------------------------
# Missing vendor directory
# ---------------------------
Run-Tool -Title "Missing Vendor Directory" -Command {
    if (-not (Test-Path "vendor")) {
        Log-Write "CRITICAL: vendor directory is missing. Run composer install."
    }
}

# ---------------------------
# PHP Tools (All Files)
# ---------------------------
$phpTools = @{
    "PHPStan" = "vendor/bin/phpstan analyse --no-progress --error-format=raw --level=max $ProjectDir"
    "Psalm" = "vendor/bin/psalm --no-cache --show-info=false --level=1 $ProjectDir"
    "PHPUnit" = "vendor/bin/phpunit --colors=never"
    "Deptrac" = "vendor/bin/deptrac analyze $ProjectDir"
    "PHPCS" = "vendor/bin/phpcs --standard=PSR12 -n $ProjectDir"
    "PHPMD" = "vendor/bin/phpmd $ProjectDir text cleancode,codesize,controversial,design,naming,unusedcode"
    "PHPCPD" = "vendor/bin/phpcpd $ProjectDir"
    "Rector" = "vendor/bin/rector process --dry-run $ProjectDir"
    "Pint" = "vendor/bin/pint --test $ProjectDir"
}

foreach ($tool in $phpTools.Keys) {
    Run-Tool -Title $tool -Command {
        if (Has-Command php) {
            $cmd = "php " + $phpTools[$tool]
            Invoke-Expression $cmd | ForEach-Object { Log-Write $_ }
        } else {
            Log-Write ($tool + " not installed or php not found")
        }
    }
}

# ---------------------------
# Laravel Commands
# ---------------------------
Run-Tool -Title "Laravel: Config Caching Issues" -Command {
    if (Has-Command php) { php artisan config:cache 2>&1 | ForEach-Object { Log-Write $_ } }
    else { Log-Write "php not found" }
}

Run-Tool -Title "Laravel: Route Caching Issues" -Command {
    if (Has-Command php) { php artisan route:cache 2>&1 | ForEach-Object { Log-Write $_ } }
    else { Log-Write "php not found" }
}

Run-Tool -Title "Laravel: All Routes & Closures" -Command {
    if (Has-Command php) {
        php artisan route:list --except-vendor --json | ConvertFrom-Json |
            ForEach-Object { Log-Write ("Route: " + $_.uri + " => " + $_.action) }
    } else { Log-Write "php not found" }
}

# ---------------------------
# Lighthouse Full Scan
# ---------------------------
Run-Tool -Title "Lighthouse Full Scan" -Command {
    if (Has-Command lhci) { lhci autorun | ForEach-Object { Log-Write $_ } }
    else { Log-Write "Lighthouse (lhci) not found. Install @lhci/cli" }
}

# ---------------------------
# Accessibility Scan (Axe Full)
# ---------------------------
Run-Tool -Title "Accessibility Scan (Axe Full)" -Command {
    if (Has-Command axe) {
        $urls = @("http://localhost:8000")
        foreach ($url in $urls) { axe $url --exit | ForEach-Object { Log-Write $_ } }
    } else { Log-Write "Axe not found. Run npm install -g axe-cli" }
}

# ---------------------------
# Outdated Composer Dependencies
# ---------------------------
Run-Tool -Title "Outdated Composer Dependencies" -Command {
    if (Has-Command composer) { composer outdated --direct | ForEach-Object { Log-Write $_ } }
    else { Log-Write "composer not found" }
}

# ---------------------------
# Missing Env Variables
# ---------------------------
Run-Tool -Title "Missing Env Variables" -Command {
    if ((Test-Path ".env.example") -and (Test-Path ".env")) {
        $example = Get-Content ".env.example" | Where-Object { $_ -match "^[A-Z_]+=" } | ForEach-Object { ($_.Split("="))[0] }
        $actual = Get-Content ".env" | Where-Object { $_ -match "^[A-Z_]+=" } | ForEach-Object { ($_.Split("="))[0] }
        Compare-Object $example $actual | Where-Object { $_.SideIndicator -eq "<=" } |
            ForEach-Object { Log-Write ("Missing in .env: " + $_.InputObject) }
    } else { Log-Write "Could not compare .env and .env.example" }
}

# ---------------------------
# Backup/Sensitive Files (All)
# ---------------------------
Run-Tool -Title "Backup/Sensitive Files (Full)" -Command {
    Get-ChildItem -Recurse -File -Include .env,*.env.*,*.zip,*.sql,*.sql.gz,*.bak,*.old,*.log -Exclude "vendor\*","node_modules\*" -ErrorAction SilentlyContinue |
        ForEach-Object { Log-Write $_.FullName }
}

# ---------------------------
# Large Files (>1MB Full)
# ---------------------------
Run-Tool -Title "Large Files (>1MB Full)" -Command {
    Get-ChildItem -Recurse -File | Where-Object { $_.Length -gt 1MB } |
        Sort-Object Length -Descending |
        ForEach-Object { Log-Write ("{0} ({1:N2} MB)" -f $_.FullName, ($_.Length / 1MB)) }
}

# ---------------------------
# Uncommitted Git Changes
# ---------------------------
Run-Tool -Title "Uncommitted Git Changes" -Command {
    if (Test-Path ".git") { git status --porcelain | ForEach-Object { Log-Write $_ } }
    else { Log-Write "Not a git repository" }
}

# ---------------------------
# Hostinger PHP Version Check
# ---------------------------
Run-Tool -Title "Hostinger: PHP Version Check" -Command {
    if (Has-Command php) {
        $v = (php -v | Select-Object -First 1)
        if ($v -match "PHP (7\.[0-3]|5\.\d)") { Log-Write "Hostinger may not support this old PHP version. Recommended: 7.4+" }
        else { Log-Write "PHP version is likely compatible." }
    } else { Log-Write "php not found" }
}

Log-Write "`n✅ Ultimate full project audit complete. Report saved to $ReportFile"

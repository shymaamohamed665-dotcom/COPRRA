<#
Usage:
  Run as Administrator (recommended) to add Defender exclusions.
  powershell -ExecutionPolicy Bypass -File .\system-support\setup-all-protections.ps1

This script orchestrates recommended protections:
 - Adds Windows Defender exclusions for vendor and node_modules
 - Shows and disables Storage Sense (optional, defaults to disable)
 - Runs OneDrive diagnostic to warn about sync conflicts
#>

param(
    [bool]$DisableStorageSense = $true,
    [bool]$AddDefenderExclusions = $true
)

function Require-Admin {
    $isAdmin = ([Security.Principal.WindowsPrincipal] [Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)
    if (-not $isAdmin) {
        Write-Warning "Please run as Administrator to add Windows Defender exclusions successfully."
    }
}

$scriptRoot = $PSScriptRoot
$projectRoot = (Resolve-Path (Join-Path $scriptRoot "..")).Path

Write-Host "Starting setup of system protections..." -ForegroundColor Cyan
Write-Host ("Project Root: {0}" -f $projectRoot)

# 1) OneDrive Diagnostic (safe, no admin required)
Write-Host "\nRunning OneDrive diagnostic..." -ForegroundColor Yellow
try {
    & (Join-Path $scriptRoot 'onedrive-check.ps1') -ProjectRoot $projectRoot
} catch {
    Write-Warning "Failed to run OneDrive diagnostic: $($_.Exception.Message)"
}

# 2) Storage Sense Helper (optional)
if ($DisableStorageSense) {
    Write-Host "\nDisabling Storage Sense for current user..." -ForegroundColor Yellow
    try {
        & (Join-Path $scriptRoot 'storage-sense-helper.ps1') -Disable
    } catch {
        Write-Warning "Failed to disable Storage Sense: $($_.Exception.Message)"
    }
} else {
    Write-Host "\nSkipping Storage Sense disable (flag not set)." -ForegroundColor DarkYellow
    try { & (Join-Path $scriptRoot 'storage-sense-helper.ps1') } catch {}
}

# 3) Windows Defender Exclusions (requires admin)
if ($AddDefenderExclusions) {
    Write-Host "\nAdding Windows Defender exclusions..." -ForegroundColor Yellow
    Require-Admin
    try {
        & (Join-Path $scriptRoot 'add-defender-exclusions.ps1') -ProjectRoot $projectRoot
    } catch {
        Write-Warning "Failed to add Defender exclusions: $($_.Exception.Message)"
        Write-Host "Hint: Re-run this script as Administrator." -ForegroundColor DarkYellow
    }
} else {
    Write-Host "\nSkipping Defender exclusions (flag not set)." -ForegroundColor DarkYellow
}

Write-Host "\nSystem protections setup complete." -ForegroundColor Green
Write-Host "What's next:" -ForegroundColor Cyan
Write-Host " - Run project protections: protect-vendor.ps1 or .sh when working."
Write-Host " - Avoid dangerous git clean commands (fdx/fdX)."
Write-Host " - Prefer project paths outside OneDrive, e.g., C:\\Projects\\COPRRA."
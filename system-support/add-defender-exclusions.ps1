param(
    [string]$ProjectRoot = (Resolve-Path (Join-Path $PSScriptRoot "..")).Path
)

function Require-Admin {
    $isAdmin = [bool]([Security.Principal.WindowsPrincipal][Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)
    if (-not $isAdmin) {
        Write-Error "This script requires Administrator privileges. Please open PowerShell as Administrator and re-run."
        exit 1
    }
}

Require-Admin

$paths = @(
    (Join-Path $ProjectRoot "vendor"),
    (Join-Path $ProjectRoot "node_modules")
)

Write-Host "Windows Defender exclusions will be added for the following paths:" -ForegroundColor Cyan
$paths | ForEach-Object { Write-Host (" - {0}" -f $_) }

foreach ($p in $paths) {
    try {
        Add-MpPreference -ExclusionPath $p
        Write-Host ("Added exclusion: {0}" -f $p) -ForegroundColor Green
    } catch {
        Write-Warning ("Failed to add exclusion for path {0}: {1}" -f $p, $_.Exception.Message)
    }
}

Write-Host "\nCurrent exclusions:" -ForegroundColor Yellow
try {
    Get-MpPreference | Select-Object -ExpandProperty ExclusionPath | Format-Table
} catch {
    Write-Warning "Unable to display exclusions. Ensure PowerShell is running as Administrator."
}

Write-Host "\nTip: You can remove exclusions later using: Remove-MpPreference -ExclusionPath <path>" -ForegroundColor DarkYellow
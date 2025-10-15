param(
    [string]$ProjectRoot = (Resolve-Path (Join-Path $PSScriptRoot "..")).Path
)

Write-Host "[OneDrive Diagnostic]" -ForegroundColor Cyan
$oneDrive = $env:OneDrive
$desktopPath = [Environment]::GetFolderPath('Desktop')
$oneDriveDisplay = if ($oneDrive) { $oneDrive } else { '(not set)' }
Write-Host ("OneDrive path: {0}" -f $oneDriveDisplay)
Write-Host ("Desktop path: {0}" -f $desktopPath)
Write-Host ("Project path: {0}" -f $ProjectRoot)

function Is-UnderPath($path, $root) {
    if (-not $path -or -not $root) { return $false }
    $p = (Resolve-Path -LiteralPath $path).Path
    $r = (Resolve-Path -LiteralPath $root).Path
    return $p.ToLower().StartsWith($r.ToLower())
}

if ($oneDrive) {
    $odDesktop = Join-Path $oneDrive 'Desktop'
    if (Test-Path $odDesktop -PathType Container) {
        if (Is-UnderPath $desktopPath $oneDrive) {
            Write-Host ("Desktop is under OneDrive sync: {0}" -f $odDesktop) -ForegroundColor Yellow
        } else {
            Write-Host "Desktop not under OneDrive." -ForegroundColor Green
        }
    } else {
        Write-Host ("OneDrive Desktop folder not found under: {0}" -f $oneDrive) -ForegroundColor Yellow
    }

    if (Is-UnderPath $ProjectRoot $oneDrive) {
        Write-Warning "Project directory is inside OneDrive. Sync conflicts can delete dependencies (vendor/node_modules)."
        Write-Host "Recommendation: Move the project to a local path e.g. 'C:\\Projects\\COPRRA' and exclude it from OneDrive." -ForegroundColor DarkYellow
    } else {
        Write-Host "Project directory is outside OneDrive." -ForegroundColor Green
    }
} else {
    Write-Host "OneDrive environment variable not set. Likely not using OneDrive sync." -ForegroundColor Green
}

Write-Host "\n[Desktop reparse point]" -ForegroundColor Cyan
try {
    fsutil reparsepoint query $desktopPath | Out-Host
} catch {
    Write-Host "fsutil not available or requires admin. Skipping reparse check." -ForegroundColor Yellow
}

Write-Host "\nNext steps:" -ForegroundColor Cyan
Write-Host " - If inside OneDrive, move the project out and re-run this check."
Write-Host " - Add Defender exclusions using: system-support/add-defender-exclusions.ps1 (run as Administrator)."
Write-Host " - Consider disabling Storage Sense using: system-support/storage-sense-helper.ps1 -Disable"
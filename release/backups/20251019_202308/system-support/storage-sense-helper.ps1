param([switch]$Disable)

$regPath = "HKCU:\Software\Microsoft\Windows\CurrentVersion\StorageSense\Parameters\StoragePolicy"

Write-Host "[Storage Sense Status]" -ForegroundColor Cyan
try {
    $current = Get-ItemProperty -Path $regPath
    $enabled = ($current."01" -eq 1)
    Write-Host ("Enabled: {0}" -f $enabled)
    Write-Host "Current values:" -ForegroundColor Yellow
    $current | Format-List
} catch {
    Write-Warning "Storage Sense policy not found for current user; may be disabled by default or managed by system."
}

if ($Disable) {
    Write-Host "\nDisabling Storage Sense (set key 01 = 0)" -ForegroundColor Magenta
    try {
        Set-ItemProperty -Path $regPath -Name "01" -Value 0
        Write-Host "Disabled successfully." -ForegroundColor Green
    } catch {
        Write-Warning ("Failed to change setting: {0}" -f $_.Exception.Message)
    }
}

Write-Host "\nNote: You can also disable via Windows UI: Settings > System > Storage > Turn off Storage Sense." -ForegroundColor DarkYellow
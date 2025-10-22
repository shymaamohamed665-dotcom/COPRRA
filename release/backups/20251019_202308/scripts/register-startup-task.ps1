#Requires -Version 5.1
param()

$TaskName   = 'COPRRA Startup Sync Check'
$ScriptPath = 'C:\Users\Gaser\Desktop\COPRRA\scripts\startup-sync-check.ps1'

if (-not (Test-Path $ScriptPath)) {
    Write-Error "Script not found: $ScriptPath"
    exit 1
}

try {
    Unregister-ScheduledTask -TaskName $TaskName -Confirm:$false -ErrorAction SilentlyContinue | Out-Null
} catch {}

$action    = New-ScheduledTaskAction -Execute 'powershell.exe' -Argument "-NoProfile -ExecutionPolicy Bypass -File `"$ScriptPath`""
$trigger   = New-ScheduledTaskTrigger -AtStartup
$principal = New-ScheduledTaskPrincipal -UserId 'SYSTEM' -RunLevel Highest
$settings  = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -StartWhenAvailable -RestartCount 3 -RestartInterval (New-TimeSpan -Minutes 1)

try {
    Register-ScheduledTask -TaskName $TaskName -Action $action -Trigger $trigger -Principal $principal -Settings $settings -Description 'Verifies and repairs Docker Windows container sync at startup.' -ErrorAction Stop | Out-Null
    Write-Output "Scheduled Task '$TaskName' registered to run at startup."
} catch {
    Write-Error "Failed to register scheduled task: $($_.Exception.Message). Try running PowerShell as Administrator."
    exit 1
}
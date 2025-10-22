param(
    [string]$ProjectRoot = (Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)),
    [string]$ToolsDir = (Split-Path -Parent $MyInvocation.MyCommand.Path),
    [int]$MaxItems = 0,
    [switch]$DryRun = $false
)

$scriptPath = Join-Path $ToolsDir 'fix_tools.ps1'
Write-Host "Launching: $scriptPath"

$dryArg = if ($DryRun) { "-DryRun" } else { "" }

powershell -ExecutionPolicy Bypass -File $scriptPath -ProjectRoot $ProjectRoot -ToolsDir $ToolsDir -MaxItems $MaxItems $dryArg

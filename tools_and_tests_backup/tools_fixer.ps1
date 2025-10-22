param(
    [string]$ProjectRoot = (Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)),
    [string]$ToolsDir = (Split-Path -Parent $MyInvocation.MyCommand.Path),
    [int]$MaxItems = 0
)

$scriptPath = Join-Path $ToolsDir 'fix_tools.ps1'
Write-Host "Launching: $scriptPath"

powershell -ExecutionPolicy Bypass -File $scriptPath -ProjectRoot $ProjectRoot -ToolsDir $ToolsDir -MaxItems $MaxItems

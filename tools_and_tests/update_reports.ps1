param(
  [Parameter(Mandatory=$true)][string]$TestName,
  [Parameter(Mandatory=$true)][int]$Tests,
  [Parameter(Mandatory=$true)][int]$Assertions
)
$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
$timestamp = Get-Date -Format 'yyyy-MM-dd HH:mm:ss'
$msg = "[{0}] Cycle: {1} -> LINT OK, PHPUnit OK ({2} tests, {3} assertions)" -f $timestamp, $TestName, $Tests, $Assertions
Add-Content -Path (Join-Path $PSScriptRoot 'script_report.txt') -Value $msg
$fixedPath = Join-Path $PSScriptRoot 'fixed_tools_and_tests.txt'
if (!(Test-Path $fixedPath)) { New-Item -ItemType File -Path $fixedPath -Force | Out-Null }
if (-not (Select-String -Path $fixedPath -Pattern ('^' + [regex]::Escape($TestName) + '$') -Quiet)) { Add-Content -Path $fixedPath -Value $TestName }
$fullList = Join-Path $root 'COPRRA_Full_867_List.txt'
if (Test-Path $fullList) { (Get-Content $fullList) | Where-Object { $_ -notmatch ('^' + [regex]::Escape($TestName) + '$') } | Set-Content $fullList }
Write-Host "Reports updated for $TestName."

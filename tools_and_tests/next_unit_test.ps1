$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
$listPath = Join-Path $root 'COPRRA_Full_867_List.txt'
$outPath = Join-Path $PSScriptRoot 'next_item.txt'
$result = ''
if (!(Test-Path $listPath)) { $result = 'LIST_NOT_FOUND' }
else {
  $lines = Get-Content $listPath
  foreach ($raw in $lines) {
    $line = $raw.Trim()
    if ($line.Length -eq 0) { continue }
    if ($line.StartsWith('#')) { continue }
    $candidate = Join-Path $root (Join-Path 'tests\Unit' ($line + '.php'))
    if (Test-Path $candidate) { $result = $line; break }
  }
  if ([string]::IsNullOrEmpty($result)) { $result = 'NO_UNIT_TEST_FOUND' }
}
Set-Content -Path $outPath -Value $result
Write-Host $result

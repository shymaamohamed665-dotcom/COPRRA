Param([int]$MaxTries=40,[int]$DelaySeconds=15)
$tries=0
while ($true) {
  $out = & "$PSScriptRoot\check_latest_run.ps1" 2>$null
  $status = ($out | Where-Object { $_ -like 'status=*' }) -replace 'status=',''
  $conclusion = ($out | Where-Object { $_ -like 'conclusion=*' }) -replace 'conclusion=',''
  $url = ($out | Where-Object { $_ -like 'html_url=*' }) -replace 'html_url=',''
  Write-Output ("status=" + $status + " conclusion=" + $conclusion + " url=" + $url)
  if ($status -eq 'completed') {
    if ($conclusion -eq 'success') { exit 0 } else { exit 1 }
  }
  $tries++
  if ($tries -ge $MaxTries) { Write-Output 'timeout'; exit 2 }
  Start-Sleep -Seconds $DelaySeconds
}

Param([string]$RunId)
if (-not $RunId) { Write-Output "error: RunId required"; exit 2 }
$owner='shymaamohamed665-dotcom'
$repo='COPRRA'
$jobsUri="https://api.github.com/repos/$owner/$repo/actions/runs/$RunId/jobs?per_page=100"
try { $data = Invoke-RestMethod -Uri $jobsUri -Headers @{ 'User-Agent' = 'TraeAI' } }
catch { Write-Output "error: $($_.Exception.Message)"; exit 2 }
if (-not $data.jobs) { Write-Output "error: no_jobs"; exit 3 }
$failed = $data.jobs | Where-Object { $_.conclusion -eq 'failure' -or $_.conclusion -eq 'cancelled' }
if (-not $failed) { Write-Output "no_failed_jobs"; exit 0 }
foreach ($j in $failed) {
  Write-Output ("job=" + $j.name + " id=" + $j.id)
  $logsZip = "scripts\\job-" + $j.id + "-logs.zip"
  Invoke-WebRequest -Uri $j.logs_url -Headers @{ 'User-Agent' = 'TraeAI' } -OutFile $logsZip
  $extractDir = "scripts\\job-" + $j.id + "-logs"
  if (Test-Path $extractDir) { Remove-Item -Recurse -Force $extractDir }
  Expand-Archive -Path $logsZip -DestinationPath $extractDir
  $patterns = @('nginx: [emerg]','php-parser','Rector','APP_KEY','storage\\framework','bootstrap\\cache','Could not find driver','SQLSTATE','composer:.*audit','PHP Fatal error','PHPUnit','No such file or directory','Trivy','Enlightn','Gitleaks','Infection','Coverage','dusk')
  foreach ($p in $patterns) {
    Get-ChildItem -Path $extractDir -Recurse -Include *.txt,*.log | ForEach-Object {
      $matches = Select-String -Path $_.FullName -Pattern $p
      foreach ($m in $matches) { Write-Output ($_.Name + ': ' + $m.Line) }
    }
  }
}

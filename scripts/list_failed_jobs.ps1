Param([string]$RunId)
if (-not $RunId) { Write-Output "error: RunId required"; exit 2 }
$owner='shymaamohamed665-dotcom'
$repo='COPRRA'
$jobsUri="https://api.github.com/repos/$owner/$repo/actions/runs/$RunId/jobs?per_page=100"
try { $data = Invoke-RestMethod -Uri $jobsUri -Headers @{ 'User-Agent' = 'TraeAI' } }
catch { Write-Output "error: $($_.Exception.Message)"; exit 2 }
if (-not $data.jobs) { Write-Output "error: no_jobs"; exit 3 }
foreach ($j in $data.jobs) {
  Write-Output ("job=" + $j.name + " conclusion=" + $j.conclusion)
  if ($j.steps) {
    $failedSteps = $j.steps | Where-Object { $_.conclusion -eq 'failure' }
    foreach ($s in $failedSteps) { Write-Output ("  step_failed=" + $s.name) }
  }
}

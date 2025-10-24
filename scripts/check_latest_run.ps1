Param([string]$Branch)
if (-not $Branch) { $Branch = (git rev-parse --abbrev-ref HEAD).Trim() }
$owner = 'shymaamohamed665-dotcom'
$repo = 'COPRRA'
$uri = "https://api.github.com/repos/$owner/$repo/actions/runs?branch=$Branch&per_page=1"

$token = $env:GITHUB_TOKEN
if (-not $token) { $token = $env:GH_TOKEN }
$headers = @{ 'User-Agent' = 'TraeAI'; Accept = 'application/vnd.github+json'; 'X-GitHub-Api-Version' = '2022-11-28' }
if ($token) { $headers.Authorization = "Bearer $token" }

try {
  $data = Invoke-RestMethod -Uri $uri -Headers $headers -Method GET
} catch {
  Write-Output "error: $($_.Exception.Message)"
  exit 2
}
if (-not $data.workflow_runs -or $data.workflow_runs.Count -eq 0) {
  Write-Output "no_runs"
  exit 3
}
$run = $data.workflow_runs[0]
Write-Output ("id=" + $run.id)
Write-Output ("status=" + $run.status)
Write-Output ("conclusion=" + $run.conclusion)
Write-Output ("html_url=" + $run.html_url)
if ($run.status -eq 'completed' -and $run.conclusion -eq 'success') { exit 0 }
else { exit 1 }
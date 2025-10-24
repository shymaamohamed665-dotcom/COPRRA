param(
    [string]$Owner = 'shymaamohamed665-dotcom',
    [string]$Repo = 'COPRRA',
    [string]$Branch,
    [string]$OutFile = 'latest_run.json'
)

function PrintAndExit([string]$msg, [int]$code) {
    Write-Host $msg
    exit $code
}

if (-not $Branch) {
    try { $Branch = (& git rev-parse --abbrev-ref HEAD).Trim() } catch { PrintAndExit ("ERR branch: " + $_.Exception.Message) 3 }
}

$token = $env:GITHUB_TOKEN
if (-not $token) { $token = $env:GH_TOKEN }

$headers = @{ Accept = "application/vnd.github+json"; 'X-GitHub-Api-Version' = '2022-11-28' }
if ($token) { $headers.Authorization = "Bearer $token" }

$url = "https://api.github.com/repos/$Owner/$Repo/actions/runs?branch=$Branch&per_page=1"

try { $resp = Invoke-RestMethod -Uri $url -Headers $headers -Method GET } catch { PrintAndExit ("ERR api: " + $_.Exception.Message) 3 }
if (-not $resp.workflow_runs -or $resp.workflow_runs.Count -eq 0) { PrintAndExit "ERR no runs for branch=$Branch" 4 }

$run = $resp.workflow_runs[0]
$run | ConvertTo-Json -Depth 10 | Set-Content -Path $OutFile -Encoding UTF8
Write-Host "Wrote $OutFile"
exit 0

param(
    [string]$Owner = 'shymaamohamed665-dotcom',
    [string]$Repo = 'COPRRA',
    [string]$Branch
)

function PrintAndExit([string]$msg, [int]$code) {
    Write-Host $msg
    exit $code
}

if (-not $Branch) {
    try {
        $Branch = (& git rev-parse --abbrev-ref HEAD).Trim()
    } catch {
        PrintAndExit ("ERR branch: " + $_.Exception.Message) 3
    }
}

$token = $env:GITHUB_TOKEN
$tokSrc = 'GITHUB_TOKEN'
if (-not $token) { $token = $env:GH_TOKEN; $tokSrc = 'GH_TOKEN' }
if (-not $token) {
    PrintAndExit "ERR token: not found in GITHUB_TOKEN or GH_TOKEN" 2
}

$headers = @{
    Authorization = "Bearer $token"
    Accept = "application/vnd.github+json"
    'X-GitHub-Api-Version' = '2022-11-28'
}

$url = "https://api.github.com/repos/$Owner/$Repo/actions/runs?branch=$Branch&per_page=1"
try {
    $resp = Invoke-RestMethod -Uri $url -Headers $headers -Method GET
} catch {
    PrintAndExit ("ERR api: " + $_.Exception.Message) 3
}

if (-not $resp.workflow_runs -or $resp.workflow_runs.Count -eq 0) {
    PrintAndExit "ERR no runs for branch=$Branch" 4
}

$run = $resp.workflow_runs[0]
Write-Host ("run_id={0} status={1} conclusion={2} url={3}" -f $run.id, $run.status, $run.conclusion, $run.html_url)

if ($run.status -eq 'completed') {
    if ($run.conclusion -eq 'success') { exit 0 } else { exit 1 }
} else {
    exit 5
}

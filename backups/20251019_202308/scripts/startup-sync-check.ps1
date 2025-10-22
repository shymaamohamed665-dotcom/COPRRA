#Requires -Version 5.1
param()

$ErrorActionPreference = 'Stop'

$ProjectPath = 'C:\Users\Gaser\Desktop\COPRRA'
$ReportDir = Join-Path $ProjectPath 'reports'
$ReportPath = Join-Path $ReportDir 'startup-sync-check.txt'

if (-not (Test-Path $ReportDir)) { New-Item -ItemType Directory -Path $ReportDir -Force | Out-Null }

function Write-Line {
    param([string]$Message)
    $ts = (Get-Date).ToString('s')
    $line = "[$ts] $Message"
    Write-Output $line
    Add-Content -Path $ReportPath -Value $line
}

function Write-Section {
    param([string]$Title)
    Write-Line "==== $Title ===="
}

function Try-StartDockerService {
    try {
        $svc = Get-Service -Name 'com.docker.service' -ErrorAction SilentlyContinue
        if ($null -ne $svc) {
            if ($svc.Status -ne 'Running') {
                Write-Line 'Starting Docker Desktop service (com.docker.service)...'
                Start-Service -Name 'com.docker.service'
                $svc.WaitForStatus('Running','00:00:20')
                Write-Line "Service status: $($svc.Status)"
            } else {
                Write-Line 'Docker Desktop service already running.'
            }
        } else {
            $desktopExe = Join-Path $Env:ProgramFiles 'Docker\Docker\Docker Desktop.exe'
            if (Test-Path $desktopExe) {
                Write-Line 'Docker service not found; starting Docker Desktop app.'
                Start-Process -FilePath $desktopExe -ArgumentList '' -WindowStyle Minimized
            } else {
                Write-Line 'Docker Desktop service/app not found in default location.'
            }
        }
    } catch {
        Write-Line "Failed to start Docker service: $($_.Exception.Message)"
    }
}

function Get-DockerInfoRaw {
    try {
        $out = & docker info 2>&1 | Out-String
        return $out
    } catch {
        return $null
    }
}

function Get-DockerOSType {
    try {
        $out = & docker info --format '{{.OSType}}' 2>&1 | Out-String
        $val = $out.Trim()
        if ([string]::IsNullOrWhiteSpace($val)) {
            $raw = Get-DockerInfoRaw
            if ($raw) {
                $m = [regex]::Match($raw, 'OSType\s*:\s*(\w+)', [System.Text.RegularExpressions.RegexOptions]::IgnoreCase)
                if ($m.Success) { return $m.Groups[1].Value.Trim().ToLowerInvariant() }
            }
            return $null
        }
        return $val.ToLowerInvariant()
    } catch {
        return $null
    }
}

function Switch-ToWindowsContainers {
    $cli = Join-Path $Env:ProgramFiles 'Docker\Docker\DockerCli.exe'
    if (Test-Path $cli) {
        Write-Line 'Switching Docker Desktop to Windows Containers engine...'
        try {
            & $cli -SwitchWindowsEngine | Out-Null
            Start-Sleep -Seconds 8
        } catch {
            Write-Line "Failed to switch engine: $($_.Exception.Message)"
        }
    } else {
        Write-Line 'DockerCli.exe not found; cannot switch engine automatically.'
    }
}

function Ensure-WindowsFeatures {
    Write-Section 'Windows Features'
    $features = 'Microsoft-Hyper-V','Containers'
    foreach ($f in $features) {
        try {
            $info = Get-WindowsOptionalFeature -Online -FeatureName $f
            Write-Line "${f}: State=$($info.State)"
            if ($info.State -ne 'Enabled') {
                Write-Line "Enabling feature: ${f}"
                $res = Enable-WindowsOptionalFeature -Online -FeatureName $f -All -NoRestart
                Write-Line "${f} enable result: State=$($res.State) RestartRequired=$($res.RestartRequired)"
            }
        } catch {
            Write-Line "Failed to query/enable ${f}: $($_.Exception.Message)"
        }
    }
}

function Validate-DockerHealth {
    Write-Section 'Docker Engine Health'
    try {
        $v = & docker version 2>&1 | Out-String
        Write-Line 'docker version:'
        ($v.Trim().Split("`n")) | ForEach-Object { Write-Line $_ }
    } catch {
        Write-Line "docker version failed: $($_.Exception.Message)"
    }
    try {
        $i = Get-DockerInfoRaw
        if ($i) {
            Write-Line 'docker info:'
            ($i.Trim().Split("`n")) | ForEach-Object { Write-Line $_ }
        } else {
            Write-Line 'docker info failed.'
        }
    } catch {
        Write-Line "docker info error: $($_.Exception.Message)"
    }
}

function Test-VolumeSync {
    Write-Section 'Volume Sync Test'
    $image = 'mcr.microsoft.com/windows/servercore:20H2'
    try { & docker pull $image | Out-Null } catch { Write-Line "Image pull failed: $($_.Exception.Message)" }
    $cmd = "Get-ChildItem C:\app | Select-Object -First 5"
    # Use --mount to avoid colon parsing issues in Windows volume specs
    $mountSpec = "type=bind,source=$ProjectPath,target=C:\app"
    $runArgs = @('run','--rm','--mount',$mountSpec,$image,'powershell','-Command',$cmd)
    try {
        $out = (& docker @runArgs 2>&1 | Out-String)
        $code = $LASTEXITCODE
        if ($code -eq 0) {
            Write-Line 'Volume sync test succeeded. Sample listing:'
            ($out.Trim().Split("`n") | Select-Object -First 10) | ForEach-Object { Write-Line $_ }
            return $true
        } else {
            Write-Line "Volume sync test failed (exit $code):"
            ($out.Trim().Split("`n") | Select-Object -First 20) | ForEach-Object { Write-Line $_ }
            return $false
        }
    } catch {
        Write-Line "docker run failed: $($_.Exception.Message)"
        return $false
    }
}

function Attempt-Repair {
    Write-Section 'Auto-Repair'
    $repaired = $false
    try {
        Write-Line 'Restarting Docker Desktop service...'
        Restart-Service -Name 'com.docker.service' -ErrorAction Stop
        Start-Sleep -Seconds 6
        $repaired = $true
    } catch {
        Write-Line "Service restart failed: $($_.Exception.Message)"
    }
    try {
        Write-Line 'Pruning dangling Docker volumes...'
        & docker volume prune -f | Out-Null
        $repaired = $true
    } catch {
        Write-Line "Volume prune failed: $($_.Exception.Message)"
    }
    return $repaired
}

# Start report with header
"COPRRA Startup Sync Check" | Set-Content -Path $ReportPath -Encoding UTF8
Write-Line "Project path: $ProjectPath"

Write-Section 'Docker Mode'
Try-StartDockerService
$osType = Get-DockerOSType
Write-Line "Detected OSType: $osType"
if ($osType -ne 'windows' -and $osType) {
    Switch-ToWindowsContainers
    $osType = Get-DockerOSType
    Write-Line "OSType after switch attempt: $osType"
}
if (-not $osType) { Write-Line 'Unable to determine Docker OSType.' }

Ensure-WindowsFeatures
Validate-DockerHealth

$syncOk = $false
$repaired = $false

if (Test-Path $ProjectPath) {
    $syncOk = Test-VolumeSync
    if (-not $syncOk) {
        $repaired = Attempt-Repair
        $syncOk = Test-VolumeSync
    }
} else {
    Write-Section 'Volume Sync Test'
    Write-Line "Local path not found: $ProjectPath"
}

Write-Section 'Final Verdict'
if ($osType -eq 'windows' -and $syncOk) {
    if ($repaired) {
        Write-Line '✅ Fully synchronized after auto-repair actions.'
    } else {
        Write-Line '✅ Fully synchronized.'
    }
} else {
    Write-Line '❌ Issues detected.'
}

Write-Section 'Summary'
$osType = if ($osType) { $osType } else { 'unknown' }
Write-Line "Docker mode: $osType"
try {
    $hv = Get-WindowsOptionalFeature -Online -FeatureName 'Microsoft-Hyper-V'
    $ct = Get-WindowsOptionalFeature -Online -FeatureName 'Containers'
    Write-Line "Hyper-V: $($hv.State) | Containers: $($ct.State)"
} catch { Write-Line 'Feature status summary unavailable.' }
Write-Line "Docker engine health: checked (see details above)"
Write-Line "Volume sync test: $([string]($syncOk))"
Write-Line "Auto-repair performed: $([string]($repaired))"

exit 0
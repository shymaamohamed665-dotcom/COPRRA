param(
    [string]$Dir = "C:\Users\Gaser\Desktop\COPRRA\tests",
    [switch]$DryRun,
    [switch]$Backup,
    [switch]$RunSuite,
    [string]$Filter
)

[Console]::OutputEncoding = [System.Text.Encoding]::UTF8
$Utf8NoBom = New-Object System.Text.UTF8Encoding($false)

# خريطة استبدال مبنية بأكواد Unicode لتجنب مشاكل التفسير
$ReplaceMap = @{}

# â†’ (garbled for →)
$gArrow = ([string][char]0x00E2) + ([string][char]0x2020) + ([string][char]0x2019)
$ReplaceMap[$gArrow] = ([string][char]0x2192)

# ðŸ’° (garbled for 💰)
$gMoney = ([string][char]0x00F0) + ([string][char]0x0178) + ([string][char]0x2019) + ([string][char]0x00B0)
$ReplaceMap[$gMoney] = ([string][char]0xD83D) + ([string][char]0xDCB0)

# ðŸ†• (garbled for 🆕)
$gNew = ([string][char]0x00F0) + ([string][char]0x0178) + ([string][char]0x2020) + ([string][char]0x2022)
$ReplaceMap[$gNew] = ([string][char]0xD83C) + ([string][char]0xDD95)

# âš™ï¸ (garbled for ⚙️)
$gGearVs = ([string][char]0x00E2) + ([string][char]0x0161) + ([string][char]0x2122) + ([string][char]0x00EF) + ([string][char]0x00B8) + ([string][char]0x008F)
$ReplaceMap[$gGearVs] = ([string][char]0x2699) + ([string][char]0xFE0F)

# ðŸ“¢ (garbled for 📢)
$gSpeaker = ([string][char]0x00F0) + ([string][char]0x0178) + ([string][char]0x201C) + ([string][char]0x00A2)
$ReplaceMap[$gSpeaker] = ([string][char]0xD83D) + ([string][char]0xDCE2)

function Fix-File([string]$Path) {
    $c = [IO.File]::ReadAllText($Path, [Text.Encoding]::UTF8)
    $o = $c
    $c = [Regex]::Replace($c, '(?m)^\s*->assertTrue\(true\);', '$this->assertTrue(true);')
    foreach ($k in $ReplaceMap.Keys) { $c = $c.Replace($k, $ReplaceMap[$k]) }
    if ($c -ne $o) {
        if ($Backup) { Copy-Item -LiteralPath $Path -Destination "$Path.bak" -Force }
        [IO.File]::WriteAllText($Path, $c, $Utf8NoBom)
        Write-Output "Fixed: $Path"
    }
}

function Scan() {
    Write-Output "Scanning: $Dir"
    $files = Get-ChildItem -Path $Dir -Filter '*.php' -File -Recurse | ForEach-Object { $_.FullName }
    $badAssert = Select-String -Path $files -Pattern '->assertTrue\(true\);' -AllMatches -ErrorAction SilentlyContinue
    $countAssert = ($badAssert | ForEach-Object { $_.Matches.Count } | Measure-Object -Sum).Sum; if (-not $countAssert) { $countAssert = 0 }
    Write-Output ("Bad assertTrue: {0}" -f $countAssert)
    foreach ($k in $ReplaceMap.Keys) {
        $m = Select-String -Path $files -Pattern ([Regex]::Escape($k)) -AllMatches -ErrorAction SilentlyContinue
        $c = ($m | ForEach-Object { $_.Matches.Count } | Measure-Object -Sum).Sum; if (-not $c) { $c = 0 }
        Write-Output ("Pattern count: {0} => {1}" -f $k, $c)
    }
}

function Apply() { Get-ChildItem -Path $Dir -Filter '*.php' -File -Recurse | ForEach-Object { Fix-File $_.FullName } }

function Run-PhpUnit([string]$flt) {
    $root = Split-Path $Dir -Parent; if (-not $root) { $root = (Get-Location).Path }
    Push-Location $root
    $args = @('--colors=never'); if ($flt) { $args += @('--filter', $flt) }
    if (Test-Path "$root\phpunit.phar") { & php "$root\phpunit.phar" @args } elseif (Test-Path "$root\vendor\bin\phpunit") { & "$root\vendor\bin\phpunit" @args } else { & phpunit @args }
    Pop-Location
}

if ($DryRun) { Scan } else { Apply }
if ($RunSuite) { Run-PhpUnit $Filter }

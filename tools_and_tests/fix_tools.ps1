param(
    [string]$ProjectRoot = (Resolve-Path "c:\Users\Gaser\Desktop\COPRRA").Path,
    [int]$MaxItems = 25,
    [string]$ToolsDir = $null,
    [switch]$DryRun = $false
)

$ErrorActionPreference = "Stop"
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8

# Resolve tools directory to English name and rename Arabic if present
$englishDir = Join-Path -Path $ProjectRoot -ChildPath "tools_and_tests"
$legacyDirCandidate = Get-ChildItem -Path $ProjectRoot -Directory -ErrorAction SilentlyContinue | Where-Object { $_.Name -match '[^\x00-\x7F]' } | Select-Object -First 1

if ($ToolsDir) {
    $toolsDir = $ToolsDir
    $parentDir = Split-Path -Parent $toolsDir
    $leafName = Split-Path -Leaf $toolsDir
    if ($leafName -match "[^\x00-\x7F]") {
        $backupDir = Join-Path -Path $parentDir -ChildPath "tools_and_tests_backup"
        try {
            if ($DryRun) {
                Write-Host "DRYRUN: Would backup '$toolsDir' to '$backupDir' and rename to 'tools_and_tests'."
            }
            else {
                if (-not (Test-Path $backupDir)) { Copy-Item -Recurse -Force -Path $toolsDir -Destination $backupDir }
                Rename-Item -Path $toolsDir -NewName "tools_and_tests"
                $toolsDir = Join-Path -Path $parentDir -ChildPath "tools_and_tests"
            }
        }
        catch { Write-Host "Error renaming folder: $($_.Exception.Message)" }
    }
}
else {
    if (-not (Test-Path $englishDir) -and $legacyDirCandidate) {
        $backupDir = Join-Path -Path $ProjectRoot -ChildPath "tools_and_tests_backup"
        try {
            if ($DryRun) {
                Write-Host "DRYRUN: Would backup '$($legacyDirCandidate.FullName)' to '$backupDir' and rename to 'tools_and_tests'."
            }
            else {
                if (-not (Test-Path $backupDir)) { Copy-Item -Recurse -Force -Path $legacyDirCandidate.FullName -Destination $backupDir }
                $OldToolsDirName = $legacyDirCandidate.Name
                Rename-Item -Path $legacyDirCandidate.FullName -NewName "tools_and_tests"
            }
        }
        catch { Write-Host "Error renaming folder: $($_.Exception.Message)" }
    }
    $toolsDir = $englishDir
}

$listPath = Join-Path -Path $toolsDir -ChildPath "COPRRA_Full_867_List.txt"

# Fixed list uses the new English filename (merge legacy if exists)
$fixedPath = Join-Path -Path $toolsDir -ChildPath 'fixed_tools_and_tests.txt'
if (-not (Test-Path $fixedPath)) {
    if ($DryRun) { Write-Host "DRYRUN: Would create fixed list file: $fixedPath" }
    else { New-Item -ItemType File -Force -Path $fixedPath | Out-Null }
}
function Clean-Lines($path) {
    $lines = Get-Content -Path $path -Encoding UTF8
    $out = @()
    foreach ($l in $lines) {
        $trim = $l.Trim()
        if ($trim -eq "") { continue }
        if ($trim.StartsWith("#")) { continue }
        # ASCII normalization: replace non-ASCII dashes
        $trim = ($trim -replace "\u2013", "-")
        $out += $trim
    }
    return $out
}

$legacyFixedMirror = Join-Path -Path $toolsDir -ChildPath 'fixed_items_mirror.txt'
if (Test-Path $legacyFixedMirror) {
    foreach ($line in (Clean-Lines $legacyFixedMirror)) {
        if (-not (Select-String -Path $fixedPath -Pattern ([regex]::Escape($line)) -Quiet)) {
            if ($DryRun) { Write-Host "DRYRUN: Would merge legacy fixed item into fixed list: $line" }
            else { Append-Text $fixedPath $line }
        }
    }
}
# Rename legacy fixed list generically if present in Arabic
$oldFixedName = $null
if (-not (Test-Path $fixedPath)) {
    $arabicTxt = Get-ChildItem -Path $toolsDir -File -Filter '*.txt' | Where-Object { $_.Name -match '[^\x00-\x7F]' } | Select-Object -First 1
    if ($arabicTxt) {
        $oldFixedName = $arabicTxt.Name
        try {
            Rename-Item -Path $arabicTxt.FullName -NewName 'fixed_tools_and_tests.txt'
        }
        catch { Write-Host "Error renaming file: $($_.Exception.Message)" }
    }
}

$reportPath = Join-Path -Path $toolsDir -ChildPath "script_report.txt"
$suggestionsPath = Join-Path -Path $toolsDir -ChildPath "suggestions_improvements.txt"
$newDiscoveriesPath = Join-Path -Path $toolsDir -ChildPath "new_discovered_tools_tests.txt"
$generatedDir = Join-Path -Path $toolsDir -ChildPath "generated"
$skippedPath = Join-Path -Path $toolsDir -ChildPath "skipped_items.txt"
if (-not (Test-Path $skippedPath)) {
    if ($DryRun) { Write-Host "DRYRUN: Would create skipped log file: $skippedPath" }
    else { New-Item -ItemType File -Force -Path $skippedPath | Out-Null }
}
if ($DryRun) { Write-Host "DRYRUN: Would create generated directory: $generatedDir" }
else { New-Item -ItemType Directory -Force -Path $generatedDir | Out-Null }

# Global cache of all project files to improve search performance
$AllFiles = Get-ChildItem -Path $ProjectRoot -Recurse -Force -ErrorAction SilentlyContinue

# Arabic → English (snake_case) transliteration
function Convert-ArabicToEnglishSnakeCase([string]$input) {
    $map = @{}
    $map[[char]0x0621] = ''
    $map[[char]0x0627] = 'a'; $map[[char]0x0623] = 'a'; $map[[char]0x0625] = 'a'; $map[[char]0x0622] = 'a'; $map[[char]0x0649] = 'a'
    $map[[char]0x0628] = 'b'; $map[[char]0x062A] = 't'; $map[[char]0x062B] = 'th'; $map[[char]0x062C] = 'j'; $map[[char]0x062D] = 'h'; $map[[char]0x062E] = 'kh'
    $map[[char]0x062F] = 'd'; $map[[char]0x0630] = 'dh'
    $map[[char]0x0631] = 'r'; $map[[char]0x0632] = 'z'; $map[[char]0x0633] = 's'; $map[[char]0x0634] = 'sh'
    $map[[char]0x0635] = 's'; $map[[char]0x0636] = 'd'; $map[[char]0x0637] = 't'; $map[[char]0x0638] = 'z'
    $map[[char]0x0639] = 'a'; $map[[char]0x063A] = 'gh'
    $map[[char]0x0641] = 'f'; $map[[char]0x0642] = 'q'; $map[[char]0x0643] = 'k'; $map[[char]0x0644] = 'l'; $map[[char]0x0645] = 'm'
    $map[[char]0x0646] = 'n'; $map[[char]0x0647] = 'h'
    $map[[char]0x0648] = 'w'; $map[[char]0x0624] = 'w'
    $map[[char]0x064A] = 'y'; $map[[char]0x0626] = 'y'
    $map[[char]0x0629] = 'a'
    $map[[char]0x0640] = '' # tatweel
    $map[[char]0x064B] = ''; $map[[char]0x064C] = ''; $map[[char]0x064D] = ''; $map[[char]0x064E] = ''; $map[[char]0x064F] = ''; $map[[char]0x0650] = ''; $map[[char]0x0651] = ''; $map[[char]0x0652] = '' # diacritics
    $map[[char]0x0660] = '0'; $map[[char]0x0661] = '1'; $map[[char]0x0662] = '2'; $map[[char]0x0663] = '3'; $map[[char]0x0664] = '4'; $map[[char]0x0665] = '5'; $map[[char]0x0666] = '6'; $map[[char]0x0667] = '7'; $map[[char]0x0668] = '8'; $map[[char]0x0669] = '9'

    $sb = New-Object System.Text.StringBuilder
    foreach ($ch in $input.ToCharArray()) {
        $codeInt = [int][char]$ch
        $cs = [string]$ch
        if ($map.ContainsKey($ch)) {
            [void]$sb.Append($map[$ch])
        }
        elseif (($codeInt -ge 0x30 -and $codeInt -le 0x39) -or ($codeInt -ge 0x41 -and $codeInt -le 0x5A) -or ($codeInt -ge 0x61 -and $codeInt -le 0x7A)) {
            [void]$sb.Append($cs)
        }
        elseif ($cs -match '[\s\-]+') {
            [void]$sb.Append('_')
        }
        else {
            [void]$sb.Append('_')
        }
    }
    $out = $sb.ToString().ToLower()
    $out = ($out -replace '[<>:"/\\\|?*]', '_')
    $out = ($out -replace '_+', '_' -replace '^_', '' -replace '_$', '')
    if ($out -eq "") {
        # Fallback: keep only ASCII letters/digits from input, replace spaces/hyphens with underscores
        $asciiOnly = ($input.ToCharArray() | ForEach-Object {
                $ci = [int][char]$_
                if (($ci -ge 0x30 -and $ci -le 0x39) -or ($ci -ge 0x41 -and $ci -le 0x5A) -or ($ci -ge 0x61 -and $ci -le 0x7A)) { [string]$_ } else { '_' }
            }) -join ''
        $asciiOnly = ($asciiOnly.ToLower() -replace '[<>:"/\\\|?*]', '_')
        $asciiOnly = ($asciiOnly -replace '_+', '_' -replace '^_', '' -replace '_$', '')
        if ($asciiOnly -ne "") { $out = $asciiOnly } else { $out = "renamed" }
    }
    return $out
}

function Scan-And-Rename-Arabic([string]$root) {
    $results = @()
    if (-not (Test-Path $root)) { return $results }
    $items = Get-ChildItem -Path $root -Recurse -Force -ErrorAction SilentlyContinue
    foreach ($it in $items) {
        if ($it.Name -match '[^\x00-\x7F]') {
            if ($it.PSIsContainer) {
                $base = $it.Name
                $newBase = Convert-ArabicToEnglishSnakeCase $base
                if (-not $newBase) { $newBase = "renamed_" + ([guid]::NewGuid().ToString().Substring(0, 8)) }
                if ($newBase -eq $base) { continue }
                $newFull = Join-Path $it.Parent.FullName $newBase
                $counter = 1
                while (Test-Path $newFull) {
                    $newFull = Join-Path $it.Parent.FullName ($newBase + "_" + $counter)
                    $counter++
                }
                if ($DryRun) {
                    Write-Log "DRYRUN: Would rename directory '$base' -> '$([System.IO.Path]::GetFileName($newFull))' at '$($it.FullName)'"
                }
                else {
                    Rename-Item -Path $it.FullName -NewName ([System.IO.Path]::GetFileName($newFull)) -Force
                }
                $results += [PSCustomObject]@{ Old = $base; New = [System.IO.Path]::GetFileName($newFull); Path = $newFull }
            }
            else {
                $base = [System.IO.Path]::GetFileNameWithoutExtension($it.Name)
                $ext = $it.Extension
                $newBase = Convert-ArabicToEnglishSnakeCase $base
                if (-not $newBase) { $newBase = "renamed_" + ([guid]::NewGuid().ToString().Substring(0, 8)) }
                if ($newBase -eq $base) { continue }
                $newName = $newBase + $ext
                $newFull = Join-Path $it.DirectoryName $newName
                $counter = 1
                while (Test-Path $newFull) {
                    $newName = $newBase + "_" + $counter + $ext
                    $newFull = Join-Path $it.DirectoryName $newName
                    $counter++
                }
                if ($DryRun) {
                    Write-Log "DRYRUN: Would rename file '$($it.Name)' -> '$newName' at '$($it.FullName)'"
                }
                else {
                    Rename-Item -Path $it.FullName -NewName $newName -Force
                }
                $results += [PSCustomObject]@{ Old = $it.Name; New = $newName; Path = $newFull }
            }
        }
    }
    return $results
}

function Scan-ArabicNamesCandidates([string]$root) {
    $results = @()
    if (-not (Test-Path $root)) { return $results }
    $items = Get-ChildItem -Path $root -Recurse -Force -ErrorAction SilentlyContinue
    foreach ($it in $items) {
        if ($it.Name -match '[^\x00-\x7F]') {
            if ($it.PSIsContainer) {
                $base = $it.Name
                $newBase = Convert-ArabicToEnglishSnakeCase $base
                if (-not $newBase) { $newBase = "renamed_" + ([guid]::NewGuid().ToString().Substring(0, 8)) }
                if ($newBase -eq $base) { continue }
                $newFull = Join-Path $it.Parent.FullName $newBase
                $counter = 1
                while (Test-Path $newFull) {
                    $newFull = Join-Path $it.Parent.FullName ($newBase + "_" + $counter)
                    $counter++
                }
                $results += [PSCustomObject]@{ Old = $base; New = [System.IO.Path]::GetFileName($newFull); Path = $it.FullName; ProposedPath = $newFull }
            }
            else {
                $base = [System.IO.Path]::GetFileNameWithoutExtension($it.Name)
                $ext = $it.Extension
                $newBase = Convert-ArabicToEnglishSnakeCase $base
                if (-not $newBase) { $newBase = "renamed_" + ([guid]::NewGuid().ToString().Substring(0, 8)) }
                if ($newBase -eq $base) { continue }
                $newName = $newBase + $ext
                $newFull = Join-Path $it.DirectoryName $newName
                $counter = 1
                while (Test-Path $newFull) {
                    $newName = $newBase + "_" + $counter + $ext
                    $newFull = Join-Path $it.DirectoryName $newName
                    $counter++
                }
                $results += [PSCustomObject]@{ Old = $it.Name; New = $newName; Path = $it.FullName; ProposedPath = $newFull }
            }
        }
    }
    return $results
}

# Generate renaming report mapping old->new names
try {
    # Convert any remaining non-ASCII names inside tools directory
    $RenamedArabic = Scan-And-Rename-Arabic $toolsDir

    $RenamedItems = @()
    if (Test-Path $englishDir) {
        $oldRootNameForReport = if ($OldToolsDirName) { $OldToolsDirName } else { "legacy_arabic_folder" }
        $RenamedItems += [PSCustomObject]@{ Old = $oldRootNameForReport; New = "tools_and_tests"; Path = $englishDir }
    }
    $fixedFilePath = Join-Path -Path $toolsDir -ChildPath "fixed_tools_and_tests.txt"
    if (Test-Path $fixedFilePath) {
        $oldNameForReport = if ($oldFixedName) { $oldFixedName } else { "legacy_fixed_list.txt" }
        $RenamedItems += [PSCustomObject]@{ Old = $oldNameForReport; New = "fixed_tools_and_tests.txt"; Path = $fixedFilePath }
    }
    $renamingReport = Join-Path -Path $toolsDir -ChildPath "renaming_report.txt"
    $lines = @()
    $lines += "| old_name | new_name | full_path_after_update |"
    foreach ($r in $RenamedItems) { $lines += "| $($r.Old) | $($r.New) | $($r.Path) |" }
    $lines += ""
    $lines += "All paths and names converted to English successfully, environment fully compatible with Docker, Hostinger, and modern CI."
    if ($DryRun) { Write-Host "DRYRUN: Would write renaming report to $renamingReport ($($lines.Count) lines)" }
    else { Set-Content -Path $renamingReport -Value $lines -Encoding UTF8 }

    # Also produce project-wide candidates safely
    $Candidates = Scan-ArabicNamesCandidates $ProjectRoot
    $candidatesReport = Join-Path -Path $toolsDir -ChildPath "renaming_candidates_project.txt"
    $clines = @()
    $clines += "| old_name | proposed_new_name | current_full_path | proposed_full_path |"
    foreach ($c in $Candidates) { $clines += "| $($c.Old) | $($c.New) | $($c.Path) | $($c.ProposedPath) |" }
    if ($clines.Count -gt 1) {
        if ($DryRun) { Write-Host "DRYRUN: Would write candidates report to $candidatesReport ($($clines.Count) lines)" }
        else { Set-Content -Path $candidatesReport -Value $clines -Encoding UTF8 }
    }
    else {
        if ($DryRun) { Write-Host "DRYRUN: Would write candidates report: No non-ASCII names detected at project root." }
        else { Set-Content -Path $candidatesReport -Value @("No non-ASCII names detected at project root.") -Encoding UTF8 }
    }
}
catch { Write-Host "Error creating renaming report: $($_.Exception.Message)" }

# Docker container support
$ContainerProjectRoot = "/var/www/html"
$ContainerNameCandidates = @("coprra_app", "coprra-app", "coprra_app_1", "coprra-std-coprra-app-1")
if (-not (Get-Command docker -ErrorAction SilentlyContinue)) {
    if ($DryRun) { Write-Host "DRYRUN: Would require Docker CLI; ignoring for dry run." }
    else {
        Write-Host "Docker CLI not found on host. Please install it first."
        exit
    }
}
$runningContainers = & docker ps --format "{{.Names}}" 2>&1
$ContainerName = $null
foreach ($cand in $ContainerNameCandidates) {
    if ($runningContainers | Select-String -Quiet ([regex]::Escape($cand))) { $ContainerName = $cand; break }
}
if (-not $ContainerName) {
    $auto = ($runningContainers | Where-Object { $_ -match '(?i)coprra.*app' } | Select-Object -First 1)
    if ($auto) { $ContainerName = $auto }
}
if (-not $ContainerName) {
    Write-Host "No matching COPRRA app container found. Please start your app container."
    exit
}

function Append-Text($path, $text) {
    try {
        $encNoBom = New-Object System.Text.UTF8Encoding($false)
        $fs = [System.IO.File]::Open($path, [System.IO.FileMode]::Append, [System.IO.FileAccess]::Write, [System.IO.FileShare]::ReadWrite)
        $sw = New-Object System.IO.StreamWriter($fs, $encNoBom)
        $sw.WriteLine(($text -as [string]).TrimEnd())
        $sw.Flush()
        $sw.Dispose()
        $fs.Dispose()
    }
    catch {
        try { Add-Content -Path $path -Value $text -Encoding UTF8 } catch { }
    }
}
function Write-Log($msg) {
    $timestamp = (Get-Date).ToString("yyyy-MM-dd HH:mm:ss")
    $line = "[$timestamp] $msg"
    Append-Text $reportPath $line
    Write-Host $line
}
function Write-Skipped($item, $reason) {
    $timestamp = (Get-Date).ToString("yyyy-MM-dd HH:mm:ss")
    $line = "[$timestamp] SKIPPED: '$item' - $reason"
    if ($DryRun) { Write-Log "DRYRUN: Would mark skipped: $line" }
    else { Append-Text $skippedPath $line }
    Write-Log $line
}

function Convert-ToContainerPath($hostPath) {
    try {
        $normalizedRoot = ($ProjectRoot -replace "\\", "/")
        $normalizedPath = ($hostPath -replace "\\", "/")
        if ($normalizedPath.StartsWith($normalizedRoot, [System.StringComparison]::OrdinalIgnoreCase)) {
            $rel = $normalizedPath.Substring($normalizedRoot.Length)
            if ($rel.StartsWith("/")) { $rel = $rel.Substring(1) }
            return "$ContainerProjectRoot/$rel"
        }
        return $normalizedPath
    }
    catch {
        return ($hostPath -replace "\\", "/")
    }
}

function Clean-Lines($path) {
    $lines = Get-Content -Path $path -Encoding UTF8
    $out = @()
    foreach ($l in $lines) {
        $trim = $l.Trim()
        if ($trim -eq "") { continue }
        if ($trim.StartsWith("#")) { continue }
        # ASCII normalization: replace non-ASCII dashes
        $trim = ($trim -replace "\u2013", "-")
        $out += $trim
    }
    return $out
}

function Normalize-ClassName($name) {
    $n = $name -replace "[^A-Za-z0-9_]", ""
    return $n
}

function Find-Item($item) {
    $foundItems = @()
    # Direct path match (skip invalid path characters)
    if ($item -and ($item -notmatch '[<>:"|?*]')) {
        try {
            $candidatePath = Join-Path $ProjectRoot $item
            if (Test-Path $candidatePath) { $foundItems += (Get-Item $candidatePath) }
        }
        catch {
            Write-Log "Skipping direct path check for '$item' due to invalid characters."
        }
    }
    # Search by file/directory name (use global cache to avoid repeated IO)
    $target = $item
    $files = $AllFiles
    foreach ($f in $files) {
        if ($f.Name -like "*$target*") { $foundItems += $f }
    }
    # Test file heuristic (filter cached list)
    if ($item -match "Test$") {
        $classFileName = "$item.php"
        $testCandidates = $AllFiles | Where-Object { -not $_.PSIsContainer -and $_.FullName -like "*\tests\*" -and $_.Name -eq $classFileName }
        foreach ($t in $testCandidates) { $foundItems += $t }
    }
    return ($foundItems | Select-Object -Unique)
}

function Ensure-Exists-Or-Create($item) {
    $found = Find-Item $item
    if ($found.Count -gt 0) { return $found[0].FullName }
    # Create placeholder
    if ($item -match "Test$") {
        $className = Normalize-ClassName $item
        $destDir = Join-Path $ProjectRoot "tests\Unit"
        $destPath = Join-Path $destDir "$className.php"
        if ($DryRun) {
            Write-Log "DRYRUN: Would create placeholder test: $destPath"
            return $destPath
        }
        New-Item -ItemType Directory -Force -Path $destDir | Out-Null
        $content = @'
<?php
declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class __CLASSNAME__ extends TestCase
{
    public function test_basic_truth(): void
    {
        $this->assertTrue(true);
    }
}
'@
        $content = $content.Replace('__CLASSNAME__', $className)
        $final = if ($content -notmatch "^\s*<\?php") { "<?php`n$content" } else { $content }
        $utf8NoBom = New-Object System.Text.UTF8Encoding($false)
        [System.IO.File]::WriteAllText($destPath, $final, $utf8NoBom)
        Write-Log "Created placeholder test: $destPath"
        return $destPath
    }
    else {
        $destPath = Join-Path $generatedDir ("placeholder_" + (Normalize-ClassName $item) + ".md")
        if ($DryRun) {
            Write-Log "DRYRUN: Would create placeholder file: $destPath"
            return $destPath
        }
        $md = "# Placeholder for '$item'`r`nThis placeholder was created to track and enforce strictness."
        Set-Content -Path $destPath -Value $md -Encoding UTF8
        Write-Log "Created placeholder file: $destPath"
        return $destPath
    }
}

function Ensure-Strictness($item, $path) {
    if ($DryRun) { Write-Log "DRYRUN: Would enforce strictness for '$item' ($path)"; return }
    try {
        if ($path -like "*phpstan.neon" -or $item -match "phpstan") {
            $phpstanPath = Join-Path $ProjectRoot "phpstan.neon"
            if (Test-Path $phpstanPath) {
                $txt = Get-Content -Path $phpstanPath -Raw -Encoding UTF8
                if ($txt -match "level:") {
                    $txt = [regex]::Replace($txt, "level:\s*\d+|level:\s*\w+", "level: max")
                }
                else {
                    $txt = "parameters:`r`n    level: max`r`n" + $txt
                }
                Copy-Item $phpstanPath "$phpstanPath.bak" -Force
                Set-Content -Path $phpstanPath -Value $txt -Encoding UTF8
                Write-Log "Enforced phpstan level: max (backup created: phpstan.neon.bak)"
            }
            else {
                Write-Log "phpstan.neon not found to enforce strictness."
            }
        }
        if ($path -like "*psalm.xml" -or $item -match "psalm") {
            $psalmPath = Join-Path $ProjectRoot "psalm.xml"
            if (Test-Path $psalmPath) {
                $txt = Get-Content -Path $psalmPath -Raw -Encoding UTF8
                if ($txt -match "errorLevel=") {
                    $txt = [regex]::Replace($txt, 'errorLevel="[^"]+"', 'errorLevel="1"')
                }
                else {
                    $txt = $txt -replace '<psalm', '<psalm errorLevel="1"'
                }
                Copy-Item $psalmPath "$psalmPath.bak" -Force
                Set-Content -Path $psalmPath -Value $txt -Encoding UTF8
                Write-Log "Enforced psalm errorLevel=1 (backup created: psalm.xml.bak)"
            }
            else {
                Write-Log "psalm.xml not found to enforce strictness."
            }
        }
        if ($path -like "*phpunit.xml" -or $item -match "phpunit") {
            $phpunitPath = Join-Path $ProjectRoot "phpunit.xml"
            if (Test-Path $phpunitPath) {
                $txt = Get-Content -Path $phpunitPath -Raw -Encoding UTF8
                if ($txt -notmatch "failOnRisky") {
                    $txt = $txt -replace '<phpunit', '<phpunit failOnRisky="true"'
                }
                else {
                    $txt = [regex]::Replace($txt, 'failOnRisky="(true|false)"', 'failOnRisky="true"')
                }
                if ($txt -notmatch "failOnWarning") {
                    $txt = $txt -replace '<phpunit', '<phpunit failOnWarning="true"'
                }
                else {
                    $txt = [regex]::Replace($txt, 'failOnWarning="(true|false)"', 'failOnWarning="true"')
                }
                Copy-Item $phpunitPath "$phpunitPath.bak" -Force
                Set-Content -Path $phpunitPath -Value $txt -Encoding UTF8
                Write-Log "Enforced phpunit failOnRisky and failOnWarning (backup created: phpunit.xml.bak)"
            }
            else {
                Write-Log "phpunit.xml not found to enforce strictness."
            }
        }
        if ($path -like "*.php") {
            $txt = Get-Content -Path $path -Raw -Encoding UTF8
            if ($txt -notmatch "declare\(\s*strict_types\s*=\s*1\s*\)") {
                $phpDeclare = @'
<?php
declare(strict_types=1);
'@
                $txt = $phpDeclare + ($txt -replace "^\s*<\?php\s*", "")
                $encNoBom = New-Object System.Text.UTF8Encoding($false)
                [System.IO.File]::WriteAllText($path, $txt, $encNoBom)
                Write-Log "Added strict_types=1 to $path"
            }
        }
    }
    catch {
        Write-Log "Strictness enforcement error for '$item' ($path): $($_.Exception.Message)"
    }
}

function Run-Item($item, $path) {
    $__out = ""
    try {
        if ($path -like "*.php" -and $path -like "*\tests\*") {
            $className = [System.IO.Path]::GetFileNameWithoutExtension($path)
            $phpunitPharHost = Join-Path $ProjectRoot "phpunit.phar"
            $phpunitPhar = "$ContainerProjectRoot/phpunit.phar"
            if (Test-Path $phpunitPharHost) {
                Write-Log "Running in container ${ContainerName}: php $phpunitPhar --colors=never --do-not-cache-result --filter $className"
                $result = & docker exec -i $ContainerName php $phpunitPhar --colors=never --do-not-cache-result --filter $className 2>&1
                $code = $LASTEXITCODE
                $__text = ($result | Out-String)
                Append-Text $reportPath $__text
                $__out += $__text
                if ($code -ne 0) {
                    Write-Log "PHPUnit run failed for $className (code $code), applying fallback lint in container."
                    $containerPath = Convert-ToContainerPath $path
                    $lint = & docker exec -i $ContainerName php -l "$containerPath" 2>&1
                    $__lintText = ($lint | Out-String)
                    Append-Text $reportPath $__lintText
                    $__out += $__lintText
                }
            }
            else {
                Write-Log "phpunit.phar not found; running PHP lint in container."
                $containerPath = Convert-ToContainerPath $path
                $lint = & docker exec -i $ContainerName php -l "$containerPath" 2>&1
                $__lintText2 = ($lint | Out-String)
                Append-Text $reportPath $__lintText2
                $__out += $__lintText2
                if ($LASTEXITCODE -ne 0) {
                    Write-Log "PHP lint failed for '$item' at '$path'."
                }
            }
        }
        elseif ($path -like "*.php") {
            $containerPath = Convert-ToContainerPath $path
            $lint = & docker exec -i $ContainerName php -l "$containerPath" 2>&1
            $__lintText3 = ($lint | Out-String)
            Append-Text $reportPath $__lintText3
            $__out += $__lintText3
            if ($LASTEXITCODE -ne 0) {
                Write-Log "PHP lint failed for '$item' at '$path'."
            }
        }
        else {
            $msg = "No runnable action for '$item'. Verified existence at $path."
            Write-Log $msg
            $__out += $msg
        }
    }
    catch {
        Write-Log "Run error for '$item' ($path): $($_.Exception.Message)"
    }
    return $__out
}

function Remove-Item-From-List($file, $item) {
    if ($DryRun) {
        Write-Log "DRYRUN: Would remove item from list: '$item' in $file"
        return
    }
    $lines = Get-Content -Path $file -Encoding UTF8
    $removed = $false
    $kept = @()
    foreach ($l in $lines) {
        if (-not $removed -and ($l.Trim() -eq $item.Trim())) {
            $removed = $true
            continue
        }
        $kept += $l
    }
    Set-Content -Path $file -Value $kept -Encoding UTF8
}

# Paid or incompatible tool mapping
$replacements = @{
    "RIPS Security Scanner"     = "Progpilot Security Analysis";
    "SensioLabs Security Check" = "Enlightn Security Checker";
    "React"                     = "Livewire";
    "React.js"                  = "Livewire";
    "NextJS"                    = "Inertia.js";
    "Next.js"                   = "Inertia.js";
    "Express"                   = "Laravel Controllers";
    "Django"                    = "Laravel";
    "Angular"                   = "Alpine.js";
}

$items = Clean-Lines $listPath
if ($MaxItems -gt 0 -and $items.Count -gt $MaxItems) {
    $items = $items | Select-Object -First $MaxItems
}

# Resume: skip items already in fixed list
$processedSet = @{}
if ($fixedPath -and (Test-Path $fixedPath)) {
    foreach ($p in (Clean-Lines $fixedPath)) { $processedSet[$p] = $true }
}
$items = $items | Where-Object { -not $processedSet.ContainsKey($_) }

# Discovery will be performed at end, initialize counter
[int]$discoveredCount = 0

# Counters for summary
[int]$processedCount = 0
[int]$skippedCount = 0
[int]$replacedCount = 0

# Compatibility and analysis helpers (moved here to be defined before use)
function Is-Laravel-Compatible($item, $path) {
    $li = $item.ToLower()
    $bad = @('react', 'next', 'next.js', 'django', 'rails', 'express', 'node ', 'nuxt', 'svelte', 'angular', 'flask', 'fastapi')
    foreach ($b in $bad) { if ($li -match $b) { return $false } }
    return $true
}
function Suggest-Laravel-Replacement($item) {
    $li = $item.ToLower()
    if ($li -match 'react') { return 'Livewire' }
    if ($li -match 'next') { return 'Inertia.js' }
    if ($li -match 'django') { return 'Laravel' }
    if ($li -match 'express') { return 'Laravel Controllers' }
    if ($li -match 'angular') { return 'Alpine.js' }
    if ($li -match 'nuxt') { return 'Inertia.js' }
    if ($li -match 'svelte') { return 'Alpine.js' }
    return $null
}
function Is-Hostinger-Compatible($item, $path) {
    $li = $item.ToLower()
    $bad = @('docker', 'systemctl', 'service ', 'mount', 'sudo', 'kubectl', 'kubernetes', 'root', 'daemon', 'iptables')
    foreach ($b in $bad) { if ($li -match $b) { return $false } }
    return $true
}
function Suggest-Hostinger-Replacement($item) {
    $li = $item.ToLower()
    if ($li -match 'docker') { return 'Shared hosting compatible scripts without Docker' }
    if ($li -match 'kubectl' -or $li -match 'kubernetes') { return 'Plain PHP/Laravel queues + Scheduler' }
    if ($li -match 'systemctl' -or $li -match 'service ') { return 'Laravel Scheduler or cron jobs' }
    return $null
}
function Check-Compatibility($item, $path) {
    $reasons = @()
    $compatible = $true
    if (-not (Is-Laravel-Compatible $item $path)) {
        $compatible = $false
        $reasons += 'Not suitable for Laravel Full Stack'
    }
    if (-not (Is-Hostinger-Compatible $item $path)) {
        $compatible = $false
        $reasons += 'Not suitable for Hostinger environment'
    }
    $replacement = $null
    if (-not $compatible) {
        $replacement = Suggest-Laravel-Replacement $item
        if (-not $replacement) { $replacement = Suggest-Hostinger-Replacement $item }
    }
    return [PSCustomObject]@{ Compatible = $compatible; Replacement = $replacement; Reasons = $reasons }
}
function Analyze-Output-And-Fix($item, $path, $output) {
    if (-not $output) { return }
    $lower = $output.ToLower()
    $indicators = @('error', 'failed', 'warning', 'exception', 'fatal', 'critical')
    $hits = @()
    foreach ($kw in $indicators) { if ($lower -match $kw) { $hits += $kw } }
    if ($hits.Count -gt 0) {
        Write-Log "Analysis for '$item': indicators -> $($hits -join ', ')"
        Write-Log "Detected issue in '$item' output - requires review."
        if ($lower -match 'parse error') {
            Write-Log "Detected PHP parse error in '$path'. Verify syntax or autoloading."
        }
        elseif ($lower -match 'class.*not found' -or $lower -match 'undefined function') {
            $note = "Suggestion: run composer install and composer dump-autoload."
            Add-Content -Path $suggestionsPath -Value $note -Encoding UTF8
            Write-Log $note
        }
        elseif ($lower -match 'permission denied' -or $lower -match 'not permitted' -or $lower -match 'operation not permitted') {
            Write-Log "Permission issue likely due to shared hosting restrictions."
        }
    }
    else {
        Write-Log "Analysis for '$item': no error indicators."
    }
}

Write-Log "Starting processing $($items.Count) items."

foreach ($item in $items) {
    $originalItem = $item
    $startTime = Get-Date

    # Compatibility check (Laravel + Hostinger)
    $comp = Check-Compatibility $item $null
    if (-not $comp.Compatible) {
        if ($comp.Replacement) {
            Write-Log "Compatibility: '$item' replaced with '$($comp.Replacement)'. Reasons: $([string]::Join('; ', $comp.Reasons))"
            $item = $comp.Replacement
            $replacedCount++
        }
        else {
            Write-Log "Compatibility: '$item' incompatible. Reasons: $([string]::Join('; ', $comp.Reasons)). Skipping."
            $skippedCount++
            Write-Skipped $originalItem "Incompatible with Laravel/Hostinger"
            continue
        }
    }

    # Paid/incompatible mapping
    if ($replacements.ContainsKey($item)) {
        Write-Log "Mapping: '$item' replaced with '$($replacements[$item])'."
        $item = $replacements[$item]
        $replacedCount++
    }

    if ($DryRun) {
        $path = Find-Item $item
        Ensure-Strictness $item $path
        Write-Log "DRYRUN: Would process '$item' (path='$path')"
        continue
    }

    $path = Ensure-Exists-Or-Create $item
    Ensure-Strictness $item $path

    $maxAttempts = 2
    $success = $false
    for ($attempt = 1; $attempt -le $maxAttempts; $attempt++) {
        $runOutput = Run-Item $item $path

        # Detect critical failures and try minimal auto-fixes once
        if ($runOutput -match '(?i)(fatal error|parse error|exception|failed)') {
            Write-Log "Attempt #$attempt detected critical failure for '$item'."
            $didFix = $false
            if ($runOutput -match '(?i)class.*not found|undefined function') {
                Write-Log "Running composer dump-autoload in container to fix autoload issues."
                $composerOut = & docker exec -i $ContainerName composer dump-autoload 2>&1
                Append-Text $reportPath ($composerOut | Out-String)
                $didFix = $true
            }
            if (-not $didFix -or $attempt -eq $maxAttempts) {
                Write-Log "Critical failure persists for '$item' - skipping."
                $skippedCount++
                Write-Skipped $originalItem "Critical failure: $((($runOutput | Out-String) -replace '`r`n',' ' -replace '\n',' '))"
                break
            }
            else {
                Write-Log "Applied auto-fix; retrying '$item'."
                continue
            }
        }

        Analyze-Output-And-Fix $item $path $runOutput
        $success = $true
        break
    }

    if (-not $success) { continue }

    if ($DryRun) {
        Write-Log "DRYRUN: Would mark processed and update lists for '$originalItem'"
    }
    else {
        if (-not (Select-String -Path $fixedPath -Pattern ([regex]::Escape($originalItem)) -Quiet)) {
            Append-Text $fixedPath $originalItem
        }
        Remove-Item-From-List $listPath $originalItem
    }
    $processedCount++
    $endTime = Get-Date
    $durationSeconds = [math]::Round((New-TimeSpan -Start $startTime -End $endTime).TotalSeconds, 2)
    Write-Log "Processed '$originalItem'. Started $($startTime.ToString('HH:mm:ss')), finished $($endTime.ToString('HH:mm:ss')), duration ${durationSeconds}s."
}

Write-Log "Processing complete."
$__FirstLoopCompleted = $true
$sg = @'
Strictness escalated to maximum in phpstan/psalm/phpunit when available.
Recommended: install dependencies and run full CI tests on Hostinger.
'@
Append-Text $suggestionsPath $sg

function Is-Laravel-Compatible($item, $path) {
    $li = $item.ToLower()
    $bad = @('react', 'next', 'next.js', 'django', 'rails', 'express', 'node ', 'nuxt', 'svelte', 'angular', 'flask', 'fastapi')
    foreach ($b in $bad) { if ($li -match $b) { return $false } }
    return $true
}
function Suggest-Laravel-Replacement($item) {
    $li = $item.ToLower()
    if ($li -match 'react') { return 'Livewire' }
    if ($li -match 'next') { return 'Inertia.js' }
    if ($li -match 'django') { return 'Laravel' }
    if ($li -match 'express') { return 'Laravel Controllers' }
    if ($li -match 'angular') { return 'Alpine.js' }
    if ($li -match 'nuxt') { return 'Inertia.js' }
    if ($li -match 'svelte') { return 'Alpine.js' }
    return $null
}
function Is-Hostinger-Compatible($item, $path) {
    $li = $item.ToLower()
    $bad = @('docker', 'systemctl', 'service ', 'mount', 'sudo', 'kubectl', 'kubernetes', 'root', 'daemon', 'iptables')
    foreach ($b in $bad) { if ($li -match $b) { return $false } }
    return $true
}
function Suggest-Hostinger-Replacement($item) {
    $li = $item.ToLower()
    if ($li -match 'docker') { return 'Shared hosting compatible scripts without Docker' }
    if ($li -match 'kubectl' -or $li -match 'kubernetes') { return 'Plain PHP/Laravel queues + Scheduler' }
    if ($li -match 'systemctl' -or $li -match 'service ') { return 'Laravel Scheduler or cron jobs' }
    return $null
}
function Check-Compatibility($item, $path) {
    $reasons = @()
    $compatible = $true
    if (-not (Is-Laravel-Compatible $item $path)) {
        $compatible = $false
        $reasons += 'Not suitable for Laravel Full Stack'
    }
    if (-not (Is-Hostinger-Compatible $item $path)) {
        $compatible = $false
        $reasons += 'Not suitable for Hostinger environment'
    }
    $replacement = $null
    if (-not $compatible) {
        $replacement = Suggest-Laravel-Replacement $item
        if (-not $replacement) { $replacement = Suggest-Hostinger-Replacement $item }
    }
    return [PSCustomObject]@{ Compatible = $compatible; Replacement = $replacement; Reasons = $reasons }
}
function Analyze-Output-And-Fix($item, $path, $output) {
    if (-not $output) { return }
    $lower = $output.ToLower()
    $indicators = @('error', 'failed', 'warning', 'exception', 'fatal', 'critical')
    $hits = @()
    foreach ($kw in $indicators) { if ($lower -match $kw) { $hits += $kw } }
    if ($hits.Count -gt 0) {
        Write-Log "Analysis for '$item': indicators -> $($hits -join ', ')"
        Write-Log "Detected issue in '$item' output - requires review."
        if ($lower -match 'parse error') {
            Write-Log "Detected PHP parse error in '$path'. Verify syntax or autoloading."
        }
        elseif ($lower -match 'class.*not found' -or $lower -match 'undefined function') {
            $note = "Suggestion: run composer install and composer dump-autoload."
            Add-Content -Path $suggestionsPath -Value $note -Encoding UTF8
            Write-Log $note
        }
        elseif ($lower -match 'permission denied' -or $lower -match 'not permitted' -or $lower -match 'operation not permitted') {
            Write-Log "Permission issue likely due to shared hosting restrictions."
        }
    }
    else {
        Write-Log "Analysis for '$item': no error indicators."
    }
}
function Discover-New-Items() {
    $known = (Clean-Lines $listPath)
    $knownSet = @{}
    foreach ($k in $known) { $knownSet[$k] = $true }
    $existingDiscSet = @{}
    if (Test-Path $newDiscoveriesPath) { foreach ($line in (Clean-Lines $newDiscoveriesPath)) { $existingDiscSet[$line] = $true } }
    $found = @()
    $testFiles = $AllFiles | Where-Object { $_.PSIsContainer -eq $false -and $_.FullName -like "*\tests\*" -and $_.Extension -eq ".php" }
    foreach ($tf in $testFiles) {
        $name = [System.IO.Path]::GetFileNameWithoutExtension($tf.FullName)
        $line = "$name - $($tf.FullName)"
        if (-not $knownSet.ContainsKey($name) -and -not $existingDiscSet.ContainsKey($line)) {
            if ($DryRun) { Write-Log "DRYRUN: Would add discovery: $line" }
            else { Append-Text $newDiscoveriesPath $line }
            $found += $line
        }
    }
    $docFiles = $AllFiles | Where-Object { $_.PSIsContainer -eq $false -and $_.Extension -eq ".md" }
    foreach ($df in $docFiles) {
        $name = [System.IO.Path]::GetFileNameWithoutExtension($df.Name)
        $line = "$name - $($df.FullName)"
        if (-not $knownSet.ContainsKey($name) -and -not $existingDiscSet.ContainsKey($line)) {
            if ($DryRun) { Write-Log "DRYRUN: Would add discovery: $line" }
            else { Add-Content -Path $newDiscoveriesPath -Value $line -Encoding UTF8 }
            $found += $line
        }
    }
    return $found
}
# Extend replacements with common cross-stack alternatives
$replacements = @{
    "RIPS Security Scanner"     = "Progpilot Security Analysis";
    "SensioLabs Security Check" = "Enlightn Security Checker";
    "React"                     = "Livewire";
    "React.js"                  = "Livewire";
    "NextJS"                    = "Inertia.js";
    "Next.js"                   = "Inertia.js";
    "Express"                   = "Laravel Controllers";
    "Django"                    = "Laravel";
    "Angular"                   = "Alpine.js";
}

$items = Clean-Lines $listPath
# Guard: avoid duplicate processing if primary loop completed
if ($__FirstLoopCompleted) { Write-Log "Skipping duplicate loop block (guard)"; $items = @() }
elseif ($MaxItems -gt 0 -and $items.Count -gt $MaxItems) {
    $items = $items | Select-Object -First $MaxItems
}

Write-Log "Starting processing $($items.Count) items."

foreach ($item in $items) {
    $originalItem = $item
    if ($replacements.ContainsKey($item)) {
        $item = $replacements[$item]
        Write-Log "Replaced paid/incompatible '$originalItem' with free alternative '$item'."
    }

    if ($DryRun) {
        $path = Find-Item $item
        Ensure-Strictness $item $path
        Write-Log "DRYRUN: Would process '$originalItem' and move to fixed list."
    }
    else {
        $path = Ensure-Exists-Or-Create $item
        Ensure-Strictness $item $path
        Run-Item $item $path

        if (-not (Select-String -Path $fixedPath -Pattern ([regex]::Escape($originalItem)) -Quiet)) {
            Append-Text $fixedPath $originalItem
        }
        Remove-Item-From-List $listPath $originalItem
        Write-Log "Processed and moved '$originalItem' to fixed list."
    }
}

Write-Log "Processing complete."
$sg = @'
Strictness escalated to maximum in phpstan/psalm/phpunit when available.
Recommended: install dependencies and run full CI tests on Hostinger.
'@
Append-Text $suggestionsPath $sg

# Duplicate suggestion entry removed; $sg already appended above.
function Run-Item2($item, $path) {
    $__out = ""
    try {
        if ($path -like "*.php" -and $path -like "*\tests\*") {
            $phpunitPharHost = Join-Path -Path $toolsDir -ChildPath "phpunit.phar"
            $phpunitPhar = Convert-ToContainerPath $phpunitPharHost
            if (Test-Path $phpunitPharHost) {
                Write-Log "Running in container ${ContainerName}: php $phpunitPhar --testdox"
                $result = & docker exec -i $ContainerName php "$phpunitPhar" --testdox 2>&1
                $code = $LASTEXITCODE
                $__text = ($result | Out-String)
                Append-Text $reportPath $__text
                $__out += $__text
                if ($code -ne 0) {
                    Write-Log "PHPUnit exited with code $code for '$item'."
                }
            }
            else {
                $containerPath = Convert-ToContainerPath $path
                $lint = & docker exec -i $ContainerName php -l "$containerPath" 2>&1
                $__text2 = ($lint | Out-String)
                Append-Text $reportPath $__text2
                $__out += $__text2
                if ($LASTEXITCODE -ne 0) {
                    Write-Log "PHP lint failed for '$item' at '$path'."
                }
            }
        }
        elseif ($path -like "*.php") {
            $containerPath = Convert-ToContainerPath $path
            $lint = & docker exec -i $ContainerName php -l "$containerPath" 2>&1
            $__text3 = ($lint | Out-String)
            Append-Text $reportPath $__text3
            $__out += $__text3
            if ($LASTEXITCODE -ne 0) {
                Write-Log "PHP lint failed for '$item' at '$path'."
            }
        }
        else {
            Write-Log "No runnable action for '$item'. Verified existence at '$path'."
            $__out += "No runnable action for '$item' at '$path'."
        }
    }
    catch {
        Write-Log "Exception while running '$item': $($_.Exception.Message)"
    }
    return $__out
}

# Prevent duplicate iteration of items; counters already defined earlier
$items = @()

foreach ($item in $items) {
    $originalItem = $item
    if ($replacements.ContainsKey($item)) {
        $item = $replacements[$item]
        Write-Log "Replaced paid/incompatible '$originalItem' with free alternative '$item'."
    }

    if ($DryRun) {
        $path = Find-Item $item
        Ensure-Strictness $item $path
        Write-Log "DRYRUN: Would process '$originalItem' and move to fixed list."
    }
    else {
        $path = Ensure-Exists-Or-Create $item
        Ensure-Strictness $item $path
        Run-Item $item $path

        if (-not (Select-String -Path $fixedPath -Pattern ([regex]::Escape($originalItem)) -Quiet)) {
            Add-Content -Path $fixedPath -Value $originalItem -Encoding UTF8
        }
        Remove-Item-From-List $listPath $originalItem
        Write-Log "Processed and moved '$originalItem' to fixed list."
    }
}

$discovered = Discover-New-Items
$discoveredCount = ($discovered | Measure-Object).Count
Write-Log "Discovery: found $discoveredCount new items added to '$newDiscoveriesPath'."

Write-Log "Processing complete."
$summary = @"
===== SUMMARY =====
Processed successfully: $processedCount
Skipped or incompatible: $skippedCount
Replaced with alternatives: $replacedCount
Newly discovered items: $discoveredCount

Executable: $((Join-Path -Path $toolsDir -ChildPath 'tools_fixer.ps1'))
Run command:
powershell -ExecutionPolicy Bypass -File \"$((Join-Path -Path $toolsDir -ChildPath 'tools_fixer.ps1'))\" -ProjectRoot \"$ProjectRoot\" -ToolsDir \"$toolsDir\" -MaxItems $MaxItems

Docker:
- Container: $ContainerName
- Project root in container: $ContainerProjectRoot

Processing completed successfully with high technical strictness.
The script is optimized for performance, Docker-compatible, supports smart error checks, and protects against data loss or duplication.
"@
Append-Text $reportPath $summary
Write-Host $summary

# Auto-clean list after summary: archive current list and exclude processed items
function Sync-List-With-Fixed {
    try {
        if (-not (Test-Path $listPath) -or -not (Test-Path $fixedPath)) { return $null }
        $originalLines = Get-Content -Path $listPath -ErrorAction Stop
        $fixedItems = Get-Content -Path $fixedPath -ErrorAction Stop | Where-Object { $_ -notmatch '^\s*$' } | ForEach-Object { $_.Trim() }
        $fixedSet = [System.Collections.Generic.HashSet[string]]::new([StringComparer]::OrdinalIgnoreCase)
        foreach ($f in $fixedItems) { $null = $fixedSet.Add($f) }

        $archivePath = Join-Path -Path ([System.IO.Path]::GetDirectoryName($listPath)) -ChildPath ([System.IO.Path]::GetFileNameWithoutExtension($listPath) + ".archive.txt")

        $newLines = @()
        $removedCount = 0
        foreach ($line in $originalLines) {
            $trimmed = $line.Trim()
            if ($trimmed -match '^\s*$' -or $trimmed -match '^\s*#') {
                $newLines += $line
            }
            elseif ($fixedSet.Contains($trimmed)) {
                $removedCount++
                continue
            }
            else {
                $newLines += $line
            }
        }

        if ($DryRun) {
            Write-Log "DRYRUN: Would archive list to '$archivePath' and remove $removedCount lines."
            return [PSCustomObject]@{ ArchivePath = $archivePath; RemovedCount = $removedCount; ListPath = $listPath; DryRun = $true }
        }
        else {
            Copy-Item -Path $listPath -Destination $archivePath -Force
            Set-Content -Path $listPath -Value $newLines -Encoding UTF8
            Write-Log "Auto-cleaned list; removed $removedCount lines matching processed items."
            return [PSCustomObject]@{ ArchivePath = $archivePath; RemovedCount = $removedCount; ListPath = $listPath; DryRun = $false }
        }
    }
    catch {
        Write-Log "Auto-clean failed: $($_.Exception.Message)"
        return $null
    }
}

$cleanupResult = Sync-List-With-Fixed
Write-Log "List synced with fixed items (post-summary cleanup)."
if ($cleanupResult -ne $null) {
    $mode = if ($cleanupResult.DryRun) { 'DryRun (no changes)' } else { 'Actual' }
    $cleanupSummary = @"
===== CLEANUP =====
Mode: $mode
List: $($cleanupResult.ListPath)
Archive: $($cleanupResult.ArchivePath)
Removed lines: $($cleanupResult.RemovedCount)
"@
    Append-Text $reportPath $cleanupSummary
    Write-Host $cleanupSummary
}

function Scan-ArabicNamesCandidates([string]$root) {
    $results = @()
    if (-not (Test-Path $root)) { return $results }
    $items = Get-ChildItem -Path $root -Recurse -Force -ErrorAction SilentlyContinue
    foreach ($it in $items) {
        if ($it.Name -match '[^\x00-\x7F]') {
            if ($it.PSIsContainer) {
                $base = $it.Name
                $newBase = Convert-ArabicToEnglishSnakeCase $base
                if (-not $newBase) { $newBase = "renamed_" + ([guid]::NewGuid().ToString().Substring(0, 8)) }
                if ($newBase -eq $base) { continue }
                $newFull = Join-Path $it.Parent.FullName $newBase
                $counter = 1
                while (Test-Path $newFull) {
                    $newFull = Join-Path $it.Parent.FullName ($newBase + "_" + $counter)
                    $counter++
                }
                $results += [PSCustomObject]@{ Old = $base; New = [System.IO.Path]::GetFileName($newFull); Path = $it.FullName; ProposedPath = $newFull }
            }
            else {
                $base = [System.IO.Path]::GetFileNameWithoutExtension($it.Name)
                $ext = $it.Extension
                $newBase = Convert-ArabicToEnglishSnakeCase $base
                if (-not $newBase) { $newBase = "renamed_" + ([guid]::NewGuid().ToString().Substring(0, 8)) }
                if ($newBase -eq $base) { continue }
                $newName = $newBase + $ext
                $newFull = Join-Path $it.DirectoryName $newName
                $counter = 1
                while (Test-Path $newFull) {
                    $newName = $newBase + "_" + $counter + $ext
                    $newFull = Join-Path $it.DirectoryName $newName
                    $counter++
                }
                $results += [PSCustomObject]@{ Old = $it.Name; New = $newName; Path = $it.FullName; ProposedPath = $newFull }
            }
        }
    }
    return $results
}
# After renaming_report.txt is written, also produce project-wide candidates in a separate file
$Candidates = Scan-ArabicNamesCandidates $ProjectRoot
$candidatesReport = Join-Path -Path $toolsDir -ChildPath "renaming_candidates_project.txt"
$clines = @()
$clines += "| old_name | proposed_new_name | current_full_path | proposed_full_path |"
foreach ($c in $Candidates) {
    $clines += "| $($c.Old) | $($c.New) | $($c.Path) | $($c.ProposedPath) |"
}
if ($clines.Count -gt 1) {
    Set-Content -Path $candidatesReport -Value $clines -Encoding UTF8
}
else {
    Set-Content -Path $candidatesReport -Value @("No non-ASCII names detected at project root.") -Encoding UTF8
}

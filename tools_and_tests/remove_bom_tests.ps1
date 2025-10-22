$root = "C:\Users\Gaser\Desktop\COPRRA\tests"
$files = Get-ChildItem -Path $root -Filter '*.php' -File -Recurse

foreach ($f in $files) {
    $p = $f.FullName
    $c = Get-Content -Raw -Path $p
    [System.IO.File]::WriteAllText($p, $c, [System.Text.UTF8Encoding]::new($false))
    Write-Output "Rewritten (UTF8 no BOM): $p"
}

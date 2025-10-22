$path = 'C:\Users\Gaser\Desktop\COPRRA\tests\Unit\CommandInjectionPreventionTest.php'
$content = Get-Content -Path $path -Raw -Encoding UTF8
$utf8NoBom = New-Object System.Text.UTF8Encoding($false)
[System.IO.File]::WriteAllText($path, $content, $utf8NoBom)

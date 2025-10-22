# Test Convert-ArabicToEnglishSnakeCase with constructed Arabic via char codes
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
        $cs = [string]$ch
        $code = [int][char]$ch
        if ($map.ContainsKey($ch)) {
            $val = $map[$ch]
            Write-Host "char=$cs code=$code -> map='$val'"
            [void]$sb.Append($val)
        }
        else {
            $codeInt = [int][char]$ch
            if (($codeInt -ge 0x30 -and $codeInt -le 0x39) -or ($codeInt -ge 0x41 -and $codeInt -le 0x5A) -or ($codeInt -ge 0x61 -and $codeInt -le 0x7A)) {
                Write-Host "char=$cs code=$codeInt -> keep (ascii)"
                [void]$sb.Append($cs)
            }
            elseif ($cs -match '[\s\-]+') {
                Write-Host "char=$cs code=$codeInt -> underscore (space/hyphen)"
                [void]$sb.Append('_')
            }
            else {
                Write-Host "char=$cs code=$codeInt -> underscore (other)"
                [void]$sb.Append('_')
            }
        }
    }
    $out = $sb.ToString().ToLower()
    $out = ($out -replace '[<>:"/\\\|?*]', '_')
    $out = ($out -replace '_+', '_' -replace '^_', '' -replace '_$', '')
    if ($out -eq "") {
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

# Build Arabic phrase "الإصلاح_النهائي" via char codes
$arabic = ([char]0x0627).ToString() + ([char]0x0644).ToString() + ([char]0x0625).ToString() + ([char]0x0635).ToString() + ([char]0x0644).ToString() + ([char]0x0627).ToString() + ([char]0x062D).ToString() + '_' + ([char]0x0627).ToString() + ([char]0x0644).ToString() + ([char]0x0646).ToString() + ([char]0x0647).ToString() + ([char]0x0627).ToString() + ([char]0x0626).ToString() + ([char]0x064A).ToString()

Write-Host "Input: log_" $arabic
$test = "log_" + $arabic
Write-Host "Input length:" $test.Length
Write-Host "Output:" (Convert-ArabicToEnglishSnakeCase $test)

param([string]$Path)
$tok = $null
$err = $null
[System.Management.Automation.Language.Parser]::ParseFile($Path, [ref]$tok, [ref]$err) | Out-Null
if ($err) {
  $err | ForEach-Object { "{0} at {1}:{2}" -f $_.Message, $_.Extent.StartLineNumber, $_.Extent.StartColumnNumber }
} else {
  "PARSER_OK"
}

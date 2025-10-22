$ErrorActionPreference = 'Stop'

# Ensure PHP CLI uses our local php.ini with unlimited memory
$env:PHPRC = $PSScriptRoot

# Compute path to phpunit.bat reliably
$projectRoot = Split-Path $PSScriptRoot -Parent
$phpunitPath = Join-Path $projectRoot 'vendor\bin\phpunit.bat'

if (-not (Test-Path $phpunitPath)) {
  throw "phpunit.bat not found at $phpunitPath"
}

# Run PHPUnit Feature suite via Windows wrapper
& $phpunitPath --testsuite Feature
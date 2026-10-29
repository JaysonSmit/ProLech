# Wrapper: usa PHP 8.4 de Laragon para todos los comandos artisan
param(
    [Parameter(ValueFromRemainingArguments = $true)]
    [string[]]$Args
)

$phpDirs = Get-ChildItem "C:\laragon\bin\php" -Directory -ErrorAction SilentlyContinue |
    Where-Object { $_.Name -match '^php-8\.4' } |
    Sort-Object Name -Descending

$phpExe = $null
foreach ($dir in $phpDirs) {
    $candidate = Join-Path $dir.FullName "php.exe"
    if (Test-Path $candidate) {
        $phpExe = $candidate
        break
    }
}

if (-not $phpExe) {
    Write-Host "Instala PHP 8.4 en Laragon (Menu -> PHP -> Version)." -ForegroundColor Red
    exit 1
}

Set-Location $PSScriptRoot
& $phpExe artisan @Args

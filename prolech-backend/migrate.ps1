# Usa PHP 8.4 de Laragon (Laravel 13 / composer.lock requieren >= 8.4.1)
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
    Write-Host "No se encontro PHP 8.4 en C:\laragon\bin\php" -ForegroundColor Red
    Write-Host "En Laragon: Menu -> PHP -> Version -> 8.4.x, luego vuelve a ejecutar este script."
    exit 1
}

Write-Host "Usando: $phpExe" -ForegroundColor Green
& $phpExe -v
Set-Location $PSScriptRoot
& $phpExe artisan migrate @args
if ($LASTEXITCODE -eq 0) {
    Write-Host ""
    Write-Host "Tip: usa .\php84.cmd artisan serve  (siempre PHP 8.4)" -ForegroundColor Cyan
}

$ErrorActionPreference = "Stop"
Set-StrictMode -Version Latest

$projectDir = $PSScriptRoot
$portablePhp = Join-Path $projectDir "tools\php\php.exe"
$php = if (Test-Path $portablePhp) {
    $portablePhp
} else {
    (Get-Command php -ErrorAction Stop).Source
}

$phpDir = Split-Path $php
$extDir = Join-Path $phpDir "ext"
$required = @("php_pdo_sqlite.dll", "php_sqlite3.dll", "php_fileinfo.dll", "php_gd.dll")

foreach ($dll in $required) {
    if (-not (Test-Path (Join-Path $extDir $dll))) {
        throw "Falta la extensión $dll. Ejecute instalar.ps1 para preparar PHP portátil."
    }
}

Write-Host "Servidor FAMEX iniciado en http://localhost:8000" -ForegroundColor Cyan
Write-Host "Presione Ctrl+C para detenerlo." -ForegroundColor DarkGray
Push-Location $projectDir
try {
    & $php -d "extension_dir=$extDir" -d extension=pdo_sqlite -d extension=sqlite3 -d extension=fileinfo -d extension=gd -S localhost:8000
} finally {
    Pop-Location
}
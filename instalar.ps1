$ErrorActionPreference = "Stop"
Set-StrictMode -Version Latest

$projectDir = $PSScriptRoot
$toolsDir = Join-Path $projectDir "tools"
$phpDir = Join-Path $toolsDir "php"
$phpExe = Join-Path $phpDir "php.exe"
$downloadUrl = "https://windows.php.net/downloads/releases/latest/php-8.4-nts-Win32-vs17-x64-latest.zip"
$zipPath = Join-Path $toolsDir "php-portable.zip"

function Write-Step([string]$message) {
    Write-Host ""
    Write-Host "==> $message" -ForegroundColor Cyan
}

if (-not [Environment]::Is64BitOperatingSystem) {
    throw "Este instalador requiere Windows de 64 bits."
}

Write-Host "Instalador portátil del Sistema FAMEX" -ForegroundColor White
Write-Host "El proyecto se configurará en: $projectDir" -ForegroundColor DarkGray

New-Item -ItemType Directory -Force -Path $toolsDir | Out-Null

if (-not (Test-Path $phpExe)) {
    Write-Step "Descargando PHP 8.4 desde windows.php.net"
    try {
        Invoke-WebRequest -Uri $downloadUrl -OutFile $zipPath -UseBasicParsing
    } catch {
        throw "No se pudo descargar PHP. Verifique la conexión a Internet y vuelva a ejecutar instalar.ps1. Detalle: $($_.Exception.Message)"
    }

    Write-Step "Extrayendo PHP portátil"
    if (Test-Path $phpDir) {
        Remove-Item -LiteralPath $phpDir -Recurse -Force
    }
    Expand-Archive -LiteralPath $zipPath -DestinationPath $phpDir -Force
    Remove-Item -LiteralPath $zipPath -Force
} else {
    Write-Step "PHP portátil ya está instalado"
}

$iniDevelopment = Join-Path $phpDir "php.ini-development"
$iniPath = Join-Path $phpDir "php.ini"
if (-not (Test-Path $iniDevelopment)) {
    throw "La descarga de PHP no contiene php.ini-development."
}

Write-Step "Configurando extensiones"
$ini = Get-Content -LiteralPath $iniDevelopment -Raw
$ini = $ini -replace "(?m)^;extension_dir = `"ext`"\s*$", 'extension_dir = "ext"'
$ini = $ini -replace "(?m)^;extension=fileinfo\s*$", 'extension=fileinfo'
$ini = $ini -replace "(?m)^;extension=gd\s*$", 'extension=gd'
$ini = $ini -replace "(?m)^;extension=pdo_sqlite\s*$", 'extension=pdo_sqlite'
$ini = $ini -replace "(?m)^;extension=sqlite3\s*$", 'extension=sqlite3'
$ini = $ini -replace "(?m)^;date.timezone\s*=.*$", 'date.timezone = America/Mexico_City'
[IO.File]::WriteAllText($iniPath, $ini, [Text.UTF8Encoding]::new($false))

Write-Step "Comprobando PHP"
$versionOutput = & $phpExe -v 2>&1
if ($LASTEXITCODE -ne 0) {
    throw @"
PHP no pudo ejecutarse. Instale Microsoft Visual C++ Redistributable 2015-2022 (x64)
y vuelva a ejecutar este instalador:
https://aka.ms/vs/17/release/vc_redist.x64.exe

Detalle:
$($versionOutput -join [Environment]::NewLine)
"@
}
Write-Host ($versionOutput | Select-Object -First 1) -ForegroundColor Green

$modules = & $phpExe -m
$missing = @("PDO", "pdo_sqlite", "sqlite3", "fileinfo", "gd") |
    Where-Object { $_ -notin $modules }
if ($missing.Count -gt 0) {
    throw "No se pudieron habilitar estas extensiones: $($missing -join '', '')."
}

Write-Step "Inicializando y verificando la base de datos"
& $phpExe (Join-Path $projectDir "verificar_base.php")
if ($LASTEXITCODE -ne 0) {
    throw "La verificación de la base de datos no terminó correctamente."
}

Write-Host ""
Write-Host "Instalación terminada correctamente." -ForegroundColor Green
Write-Host "Para iniciar el sistema:" -ForegroundColor White
Write-Host "  1. Ejecute iniciar.ps1" -ForegroundColor White
Write-Host "  2. Abra http://localhost:8000" -ForegroundColor White
Write-Host ""
Read-Host "Presione Enter para cerrar"

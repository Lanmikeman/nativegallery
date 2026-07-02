# NativeGallery — helper for IIS on Windows (directories, permissions, checks)
# Does NOT configure FastCGI automatically — see docs/windows-iis.md
#
# Usage (Administrator PowerShell):
#   .\deploy\windows\setup-iis.ps1
#   .\deploy\windows\setup-iis.ps1 -WebRoot "D:\sites\nativegallery" -SiteName "NativeGallery"

param(
    [string]$WebRoot = "C:\inetpub\nativegallery",
    [string]$SiteName = "NativeGallery",
    [string]$PhpCgiPath = "C:\php\php-cgi.exe"
)

$ErrorActionPreference = "Stop"

Write-Host "==> NativeGallery IIS setup helper"
Write-Host "    WebRoot (default): $WebRoot"
Write-Host "    SiteName:          $SiteName"
Write-Host "    See docs/paths.md for production Linux path: /mnt/win/nativegallery"
Write-Host ""

if (-not (Test-Path $WebRoot)) {
    Write-Host "ERROR: WebRoot not found: $WebRoot"
    Write-Host "Clone first: git clone https://github.com/Lanmikeman/nativegallery.git $WebRoot"
    exit 1
}

$subdirs = @(
    "uploads",
    "cdn\temp", "cdn\previews", "cdn\image", "cdn\video",
    "logs", "storage\locks"
)
foreach ($rel in $subdirs) {
    $p = Join-Path $WebRoot $rel
    New-Item -ItemType Directory -Force -Path $p | Out-Null
    Write-Host "    dir: $rel"
}

foreach ($rel in @("uploads", "cdn", "logs", "storage")) {
    $p = Join-Path $WebRoot $rel
    icacls $p /grant "IIS_IUSRS:(OI)(CI)M" /T | Out-Null
    Write-Host "    ACL: $rel -> IIS_IUSRS"
}

if (-not (Test-Path (Join-Path $WebRoot "web.config"))) {
    Write-Host "WARN: web.config missing in $WebRoot (should be in repo root)"
}

if (-not (Test-Path (Join-Path $WebRoot "ngallery.yaml"))) {
    Write-Host "WARN: ngallery.yaml missing — copy from ngallery-example.yaml"
}

if (-not (Test-Path $PhpCgiPath)) {
    Write-Host "WARN: PHP CGI not found at $PhpCgiPath — install PHP 8.3 NTS and set FastCGI in IIS"
} else {
    Write-Host "OK: PHP CGI at $PhpCgiPath"
}

Import-Module WebAdministration -ErrorAction SilentlyContinue
if (Get-Module WebAdministration) {
    $site = Get-Website -Name $SiteName -ErrorAction SilentlyContinue
    if ($site) {
        Write-Host "OK: IIS site '$SiteName' exists, path: $($site.physicalPath)"
        if ($site.physicalPath -ne $WebRoot) {
            Write-Host "WARN: Site physical path differs from -WebRoot"
        }
    } else {
        Write-Host "INFO: IIS site '$SiteName' not found — create in IIS Manager:"
        Write-Host "       Physical path: $WebRoot"
        Write-Host "       Handler: *.php -> FastCgiModule -> $PhpCgiPath"
    }
} else {
    Write-Host "INFO: WebAdministration module unavailable — configure IIS manually"
}

Write-Host ""
Write-Host "Next: composer install, MySQL migrations, setup-task-scheduler.ps1"
Write-Host "Docs: docs/windows-iis.md"
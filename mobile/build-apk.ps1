<#
.SYNOPSIS
    Membangun APK E-Surat BPRS Sragen dengan alamat server yang bisa diatur.

.DESCRIPTION
    Skrip ini menulis alamat server ke capacitor.config.json, menyinkronkan proyek
    Android, lalu membangun APK. Hasilnya disalin ke public/downloads/esurat-bprs.apk
    supaya bisa diunduh dari halaman web.

    Alamat server TIDAK ditanam di kode Laravel; cukup jalankan ulang skrip ini bila
    IP kantor berubah atau saat pindah ke domain produksi.

.EXAMPLE
    .\build-apk.ps1 -ServerUrl http://192.168.10.199:8080

.EXAMPLE
    .\build-apk.ps1 -ServerUrl https://esurat.bprssragen.co.id -Mode release
#>
[CmdletBinding()]
param(
    [Parameter(Mandatory = $true)]
    [string]$ServerUrl,

    [ValidateSet('debug', 'release')]
    [string]$Mode = 'debug',

    # Lewati "cap sync" bila aset web belum berubah (membangun ulang lebih cepat).
    [switch]$SkipSync
)

$ErrorActionPreference = 'Stop'

$mobileDir = $PSScriptRoot
$projectRoot = Split-Path -Parent $mobileDir
$configPath = Join-Path $mobileDir 'capacitor.config.json'

if ($ServerUrl -notmatch '^https?://') {
    throw "ServerUrl harus dimulai dengan http:// atau https:// (contoh: http://192.168.10.199:8080)"
}

# Android hanya mengizinkan HTTP polos bila cleartext diaktifkan.
$cleartext = $ServerUrl.StartsWith('http://')

$config = Get-Content $configPath -Raw | ConvertFrom-Json
$config.server.url = $ServerUrl.TrimEnd('/')
$config.server.cleartext = $cleartext
$config | ConvertTo-Json -Depth 10 | Set-Content -Path $configPath -Encoding UTF8

Write-Host "Alamat server aplikasi : $($config.server.url)"
Write-Host "Cleartext (HTTP polos) : $cleartext"
if ($cleartext) {
    Write-Host "Peringatan: build dengan HTTP sebaiknya hanya untuk jaringan kantor." -ForegroundColor Yellow
}

if (-not $SkipSync) {
    Push-Location $mobileDir
    try {
        & .\node_modules\.bin\cap.cmd sync android
        if ($LASTEXITCODE -ne 0) { throw "cap sync gagal (kode $LASTEXITCODE)" }
    }
    finally {
        Pop-Location
    }
}

Push-Location (Join-Path $mobileDir 'android')
try {
    $task = if ($Mode -eq 'release') { 'assembleRelease' } else { 'assembleDebug' }
    & .\gradlew.bat $task
    if ($LASTEXITCODE -ne 0) { throw "Gradle gagal (kode $LASTEXITCODE)" }
}
finally {
    Pop-Location
}

$apk = Get-ChildItem -Path (Join-Path $mobileDir "android\app\build\outputs\apk\$Mode") -Filter *.apk -ErrorAction SilentlyContinue |
    Sort-Object LastWriteTime -Descending |
    Select-Object -First 1

if (-not $apk) {
    throw "APK tidak ditemukan di android\app\build\outputs\apk\$Mode"
}

$target = Join-Path $projectRoot 'public\downloads\esurat-bprs.apk'
Copy-Item -Path $apk.FullName -Destination $target -Force

Write-Host ""
Write-Host "APK selesai dibuat:" -ForegroundColor Green
Write-Host "  sumber : $($apk.FullName)"
Write-Host "  unduhan: $target"
if ($Mode -eq 'release') {
    Write-Host "Catatan: APK release harus ditandatangani keystore sebelum dibagikan." -ForegroundColor Yellow
}

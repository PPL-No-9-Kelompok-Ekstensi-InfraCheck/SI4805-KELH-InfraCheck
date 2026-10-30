# bootstrap.ps1 - Siapkan environment Docker InfraCheck sekali jalan.
# Jalankan dari root repository: powershell -ExecutionPolicy Bypass -File docker/bootstrap.ps1
# Script ini idempoten: aman dijalankan berulang kali.

$ErrorActionPreference = "Stop"

if (-not (Test-Path "artisan")) {
    Write-Error "Jalankan script ini dari root repository (folder yang berisi artisan)."
    exit 1
}

# 1. File environment
if (-not (Test-Path ".env")) {
    Copy-Item ".env.docker" ".env"
    Write-Host "[1/5] .env dibuat dari .env.docker"
} else {
    Write-Host "[1/5] .env sudah ada, dilewati"
}

# 2. Nyalakan container
Write-Host "[2/5] Menjalankan docker compose up -d ..."
docker compose up -d
if ($LASTEXITCODE -ne 0) { Write-Error "docker compose up gagal."; exit 1 }

# 3. Tunggu MySQL sehat
$container = docker compose ps -q mysql
if (-not $container) { Write-Error "Service mysql tidak ditemukan."; exit 1 }

Write-Host "[3/5] Menunggu MySQL sehat ..."
$deadline = (Get-Date).AddMinutes(3)
while ($true) {
    $status = docker inspect --format "{{.State.Health.Status}}" $container 2>$null
    if ($status -eq "healthy") { Write-Host "      MySQL sehat"; break }
    if ((Get-Date) -gt $deadline) {
        Write-Error "MySQL tidak sehat setelah 3 menit. Cek: docker compose logs mysql"
        exit 1
    }
    Start-Sleep -Seconds 2
}

# 4. Dependency PHP
Write-Host "[4/5] composer install ..."
docker compose exec -T app composer install --no-interaction --prefer-dist
if ($LASTEXITCODE -ne 0) { Write-Error "composer install gagal."; exit 1 }

# 5. Migrasi + seeder
Write-Host "[5/5] php artisan migrate --seed ..."
docker compose exec -T app php artisan migrate --seed --no-interaction
if ($LASTEXITCODE -ne 0) { Write-Error "migrate --seed gagal."; exit 1 }

Write-Host ""
Write-Host "Selesai. Aplikasi: http://localhost:8000"
Write-Host "phpMyAdmin: http://localhost:8080"
Write-Host "Login: admin@infracheck.ac.id / password"

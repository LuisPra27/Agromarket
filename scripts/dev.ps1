param([string]$Command = "start")

$ScriptDir = $PSScriptRoot
$RootPath = Split-Path -Parent $ScriptDir
$FrontendPath = Join-Path $RootPath "frontend\mobile"

switch ($Command) {
    "start" {
        Write-Host "=== Iniciando entorno de desarrollo Agromarket ===" -ForegroundColor Green

        # 1. Levantar Postgres
        Write-Host "`n[1/3] Levantando base de datos..." -ForegroundColor Cyan
        Set-Location $RootPath
        docker-compose up -d
        
        # Esperar a que Postgres esté healthy
        Write-Host "Esperando que Postgres esté listo..."
        $intentos = 0
        do {
            Start-Sleep -Seconds 2
            $estado = docker inspect --format='{{.State.Health.Status}}' agromarket_db 2>$null
            $intentos++
        } while ($estado -ne "healthy" -and $intentos -lt 15)
        
        if ($estado -eq "healthy") {
            Write-Host "Postgres listo." -ForegroundColor Green
        } else {
            Write-Host "Postgres tardó demasiado. Verifica con: docker ps" -ForegroundColor Yellow
        }

        # 2. Levantar backend
        Write-Host "`n[2/3] Iniciando backend Laravel..." -ForegroundColor Cyan
        & "$ScriptDir\backend.ps1" start
        Start-Sleep -Seconds 2
        Write-Host "Backend corriendo en http://127.0.0.1:8000" -ForegroundColor Green

        # 3. Levantar Expo
        Write-Host "`n[3/3] Iniciando Expo (React Native)..." -ForegroundColor Cyan
        Write-Host "Abre Expo Go en tu celular y escanea el QR." -ForegroundColor Yellow
        Set-Location $FrontendPath
        npx expo start
    }

    "stop" {
        Write-Host "=== Apagando entorno de desarrollo Agromarket ===" -ForegroundColor Red
        & "$ScriptDir\backend.ps1" stop
        Set-Location $RootPath
        docker-compose down
        Write-Host "Todo apagado." -ForegroundColor Green
    }

    default {
        Write-Host "Uso: ./scripts/dev.ps1 <comando>"
        Write-Host "  start  - Levantar Postgres + Backend + Expo"
        Write-Host "  stop   - Apagar Backend + Postgres"
    }
}
param([string]$Command = "start")

$ScriptDir = $PSScriptRoot
$RootPath = Split-Path -Parent $ScriptDir
$FrontendPath = Join-Path $RootPath "frontend\mobile"
$EnvPath = Join-Path $FrontendPath ".env"

function Get-LocalIP {
    $ip = (Get-NetIPAddress -AddressFamily IPv4 |
        Where-Object {
            $_.IPAddress -notmatch '^172\.' -and
            $_.IPAddress -notmatch '^127\.' -and
            $_.IPAddress -notmatch '^169\.' -and
            $_.InterfaceAlias -notmatch 'Loopback|vEthernet'
        } |
        Select-Object -First 1).IPAddress
    return $ip
}

function Update-EnvIP {
    $ip = Get-LocalIP
    if (-not $ip) {
        Write-Host "No se pudo detectar la IP. Verifica tu conexion de red." -ForegroundColor Red
        return $null
    }
    $contenido = @"
EXPO_PUBLIC_API_URL=http://${ip}:8000
EXPO_PUBLIC_WS_HOST=${ip}
"@
    Set-Content -Path $EnvPath -Value $contenido -Encoding UTF8
    Write-Host "IP detectada y actualizada: $ip" -ForegroundColor Green
    return $ip
}

switch ($Command) {
    "start" {
        Write-Host "=== Iniciando entorno de desarrollo Agromarket ===" -ForegroundColor Green

        # 0. Auto-detectar y actualizar IP
        Write-Host "`n[0/3] Detectando IP de red..." -ForegroundColor Cyan
        $ip = Update-EnvIP
        if ($ip) {
            Write-Host "Backend accesible en: http://${ip}:8000" -ForegroundColor Green
            Write-Host "Recuerda recargar Metro en el celular si ya esta corriendo" -ForegroundColor Yellow
        }

        # 1. Levantar Postgres
        Write-Host "`n[1/3] Levantando base de datos..." -ForegroundColor Cyan
        Set-Location $RootPath
        docker-compose up -d

        Write-Host "Esperando que Postgres este listo..."
        $intentos = 0
        do {
            Start-Sleep -Seconds 2
            $estado = docker inspect --format='{{.State.Health.Status}}' agromarket_db 2>$null
            $intentos++
        } while ($estado -ne "healthy" -and $intentos -lt 15)

        if ($estado -eq "healthy") {
            Write-Host "Postgres listo." -ForegroundColor Green
        } else {
            Write-Host "Postgres tardo demasiado. Verifica con: docker ps" -ForegroundColor Yellow
        }

        # 2. Levantar backend
        Write-Host "`n[2/3] Iniciando backend Laravel..." -ForegroundColor Cyan
        & "$ScriptDir\backend.ps1" start
        Start-Sleep -Seconds 2
        Write-Host "Backend corriendo en http://${ip}:8000" -ForegroundColor Green

        # 3. Levantar Expo
        Write-Host "`n[3/3] Iniciando Expo (React Native)..." -ForegroundColor Cyan
        Write-Host "Abre la app Agromarket en tu celular." -ForegroundColor Yellow
        Set-Location $FrontendPath
        npx expo start --dev-client --scheme agromarket --clear
    }

    "stop" {
        Write-Host "=== Apagando entorno de desarrollo Agromarket ===" -ForegroundColor Red
        & "$ScriptDir\backend.ps1" stop
        Set-Location $RootPath
        docker-compose down
        Write-Host "Todo apagado." -ForegroundColor Green
    }

    "set-ip" {
        Write-Host "Actualizando IP en .env..." -ForegroundColor Cyan
        $ip = Update-EnvIP
        if ($ip) {
            Write-Host "`nArchivo .env actualizado:" -ForegroundColor Green
            Get-Content $EnvPath
            Write-Host "`nRecarga Metro en tu celular:" -ForegroundColor Yellow
            Write-Host "  - Agita el dispositivo → Reload" -ForegroundColor Yellow
            Write-Host "  - O presiona 'r' en la terminal de Metro" -ForegroundColor Yellow
        }
    }

    "ip" {
        $ip = Get-LocalIP
        if ($ip) {
            Write-Host "Tu IP actual: $ip" -ForegroundColor Cyan
            Write-Host "Backend: http://${ip}:8000" -ForegroundColor Green
            Write-Host "WebSocket: ws://${ip}:8080" -ForegroundColor Green
        } else {
            Write-Host "No se pudo detectar la IP." -ForegroundColor Red
        }
    }

    default {
        Write-Host "Uso: ./scripts/dev.ps1 <comando>"
        Write-Host "  start   - Levantar todo (Postgres + Backend + Expo) con IP auto-detectada"
        Write-Host "  stop    - Apagar Backend + Postgres"
        Write-Host "  set-ip  - Detectar IP actual y actualizar .env (sin reiniciar nada)"
        Write-Host "  ip      - Solo mostrar la IP actual"
    }
}
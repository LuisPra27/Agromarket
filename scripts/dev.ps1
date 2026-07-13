param(
    [string]$Command = "start",
    [string]$NgrokUrl = ""
)

$ScriptDir = $PSScriptRoot
$RootPath = Split-Path -Parent $ScriptDir
$FrontendPath = Join-Path $RootPath "frontend\mobile"
$EnvPath = Join-Path $FrontendPath ".env"

function Get-LocalIP {
    # 1. Obtenemos solo las interfaces que están físicamente conectadas (Up)
    $activeInterfaces = Get-NetAdapter | Where-Object { $_.Status -eq "Up" } | Select-Object -ExpandProperty Name

    # 2. Forzamos el resultado a un Array con @(...) para evitar bugs de conteo
    $ips = @(Get-NetIPAddress -AddressFamily IPv4 |
        Where-Object {
            $activeInterfaces -contains $_.InterfaceAlias -and
            $_.IPAddress -notmatch '^127\.' -and
            $_.IPAddress -notmatch '^169\.' -and
            $_.InterfaceAlias -notmatch 'Loopback|vEthernet|VirtualBox|VMware|WSL'
        } |
        Select-Object IPAddress, InterfaceAlias)

    if ($ips.Count -eq 0) {
        Write-Host "No se encontraron IPs de red local disponibles." -ForegroundColor Red
        return $null
    }

    Write-Host ""
    Write-Host "IPs disponibles:" -ForegroundColor Cyan

    for ($i = 0; $i -lt $ips.Count; $i++) {
        Write-Host ("  [{0}] {1} - {2}" -f ($i + 1), $ips[$i].IPAddress, $ips[$i].InterfaceAlias)
    }

    # Opción ngrok al final
    $ngrokIndex = $ips.Count + 1
    Write-Host ("  [{0}] ngrok (ingresar URL manualmente)" -f $ngrokIndex)

    $seleccion = Read-Host "Elige el numero de la IP/opcion"

    try {
        $index = [int]$seleccion - 1
        if ($index -ge 0 -and $index -lt $ips.Count) {
            return $ips[$index].IPAddress
        }
        elseif ($index -eq $ngrokIndex - 1) {
            # Usuario eligió ngrok
            $url = Read-Host "Ingresa la URL ngrok (ej: https://xxxx.ngrok-free.app)"
            if ($url -match '^https?://') {
                return $url
            }
            Write-Host "URL invalida. Debe empezar con http:// o https://" -ForegroundColor Red
            return $null
        }
    }
    catch { }

    Write-Host "Seleccion invalida." -ForegroundColor Red
    return $null
}

function Update-EnvIP {
    param([string]$NgrokUrlOverride = "")

    $ip = $NgrokUrlOverride

    if (-not $ip) {
        $ip = Get-LocalIP
    }

    if (!$ip) {
        Write-Host "No se pudo detectar la IP." -ForegroundColor Red
        return $null
    }

    # Si es URL ngrok, extraer solo el host para WS_HOST
    $wsHost = $ip
    if ($ip -match '^https?://([^/]+)') {
        $wsHost = $matches[1]
    }

    # Leer .env existente y preservar otras variables (ej. MICROSOFT_CLIENT_ID)
    $existing = @{}
    if (Test-Path $EnvPath) {
        Get-Content $EnvPath | ForEach-Object {
            if ($_ -match '^([^=]+)=(.*)$') {
                $existing[$matches[1]] = $matches[2]
            }
        }
    }

    # Actualizar las URLs
    if ($ip -match '^https?://') {
        $existing['EXPO_PUBLIC_API_URL'] = $ip
    } else {
        $existing['EXPO_PUBLIC_API_URL'] = "http://$($ip):8000"
    }
    $existing['EXPO_PUBLIC_WS_HOST'] = $wsHost

    # Escribir de vuelta conservando todo
    $existing.GetEnumerator() | ForEach-Object { "$($_.Key)=$($_.Value)" } | Set-Content $EnvPath -Encoding UTF8

    Write-Host "IP/URL detectada y actualizada: $ip" -ForegroundColor Green

    return $ip
}

switch ($Command) {

    "start" {

        Write-Host "=== Iniciando entorno de desarrollo Agromarket ===" -ForegroundColor Green

        Write-Host ""
        Write-Host "[0/3] Detectando IP..." -ForegroundColor Cyan

        $ip = Update-EnvIP -NgrokUrlOverride $NgrokUrl

        if ($ip) {
            Write-Host "Backend: http://$($ip):8000" -ForegroundColor Green
        }

        Write-Host ""
        Write-Host "[1/3] Levantando Postgres..." -ForegroundColor Cyan

        Set-Location $RootPath

        docker-compose up -d

        $intentos = 0

        do {
            Start-Sleep 2
            $estado = docker inspect --format='{{.State.Health.Status}}' agromarket_db 2>$null
            $intentos++
        }
        while ($estado -ne "healthy" -and $intentos -lt 15)

        if ($estado -eq "healthy") {
            Write-Host "Postgres listo." -ForegroundColor Green
        }
        else {
            Write-Host "Postgres aun no esta listo." -ForegroundColor Yellow
        }

        Write-Host ""
        Write-Host "[2/3] Iniciando Backend..." -ForegroundColor Cyan

        & "$ScriptDir\backend.ps1" start

        Start-Sleep 2
        & "$ScriptDir\backend.ps1" reverb

        Write-Host "Backend y Reverb iniciado." -ForegroundColor Green

        Write-Host ""
        Write-Host "[3/3] Iniciando Expo..." -ForegroundColor Cyan

        Set-Location $FrontendPath

        npx expo start --dev-client --scheme agromarket --clear
    }

    "stop" {

        Write-Host "Apagando entorno..." -ForegroundColor Red

        & "$ScriptDir\backend.ps1" stop
        Set-Location $RootPath

        docker-compose down

        Write-Host "Todo apagado." -ForegroundColor Green
    }

    "set-ip" {

        $ip = Update-EnvIP -NgrokUrlOverride $NgrokUrl

        if ($ip) {

            Write-Host ""
            Write-Host ".env actualizado:" -ForegroundColor Green

            Get-Content $EnvPath

            Write-Host ""
            Write-Host "Recarga Metro:" -ForegroundColor Yellow
            Write-Host "  - Agita el dispositivo y pulsa Reload"
            Write-Host "  - O presiona r en la terminal de Expo"
        }
    }

    "ip" {

        $ip = Get-LocalIP

        if ($ip) {
            Write-Host "IP local: $ip" -ForegroundColor Cyan
            Write-Host "Backend : http://$($ip):8000"
            Write-Host "WebSocket: ws://$($ip):8080"
        }
        else {
            Write-Host "No se pudo detectar la IP." -ForegroundColor Red
        }
    }

    "ngrok" {
        param([string]$Url = "")

        if (-not $Url) {
            Write-Host "Uso: .\scripts\dev.ps1 ngrok https://xxxx.ngrok-free.app" -ForegroundColor Yellow
            return
        }

        $ip = Update-EnvIP -NgrokUrlOverride $Url

        if ($ip) {
            Write-Host ""
            Write-Host ".env actualizado con ngrok:" -ForegroundColor Green
            Get-Content $EnvPath
            Write-Host ""
            Write-Host "IMPORTANTE: El backend debe ser accesible en esa URL ngrok." -ForegroundColor Yellow
            Write-Host "  ngrok http 8000" -ForegroundColor Cyan
            Write-Host "  (corre en otra terminal mientras el backend esta arriba)" -ForegroundColor Gray
        }
    }

    default {

        Write-Host ""
        Write-Host "Uso: .\scripts\dev.ps1 <comando> [opciones]"
        Write-Host ""
        Write-Host "start          Levanta todo (Postgres + Backend + Reverb + Expo)"
        Write-Host "                 Opcional: .\scripts\dev.ps1 start -NgrokUrl https://xxxx.ngrok-free.app"
        Write-Host "stop           Apaga todo"
        Write-Host "set-ip         Actualiza .env con IP local detectada (menu interactivo)"
        Write-Host "                 Opcional: .\scripts\dev.ps1 set-ip -NgrokUrl https://xxxx.ngrok-free.app"
        Write-Host "ip             Muestra la IP local detectada"
        Write-Host "ngrok          Actualiza .env con URL ngrok (para PayPhone/webhooks)"
        Write-Host "                 .\scripts\dev.ps1 ngrok https://xxxx.ngrok-free.app"
    }
}
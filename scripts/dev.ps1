param(
    [string]$Command = "start"
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

    if ($ips.Count -eq 1) {
        return $ips[0].IPAddress
    }

    Write-Host ""
    Write-Host "IPs disponibles:" -ForegroundColor Cyan

    for ($i = 0; $i -lt $ips.Count; $i++) {
        Write-Host ("  [{0}] {1} - {2}" -f ($i + 1), $ips[$i].IPAddress, $ips[$i].InterfaceAlias)
    }

    $seleccion = Read-Host "Elige el numero de la IP correcta"

    try {
        $index = [int]$seleccion - 1
        if ($index -ge 0 -and $index -lt $ips.Count) {
            return $ips[$index].IPAddress
        }
    }
    catch { }

    Write-Host "Seleccion invalida." -ForegroundColor Red
    return $null
}
function Update-EnvIP {

    $ip = Get-LocalIP

    if (!$ip) {
        Write-Host "No se pudo detectar la IP." -ForegroundColor Red
        return $null
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

    # Actualizar solo las IPs
    $existing['EXPO_PUBLIC_API_URL'] = "http://$($ip):8000"
    $existing['EXPO_PUBLIC_WS_HOST'] = $ip

    # Escribir de vuelta conservando todo
    $existing.GetEnumerator() | ForEach-Object { "$($_.Key)=$($_.Value)" } | Set-Content $EnvPath -Encoding UTF8

    Write-Host "IP detectada y actualizada: $ip" -ForegroundColor Green

    return $ip
}

switch ($Command) {

    "start" {

        Write-Host "=== Iniciando entorno de desarrollo Agromarket ===" -ForegroundColor Green

        Write-Host ""
        Write-Host "[0/3] Detectando IP..." -ForegroundColor Cyan

        $ip = Update-EnvIP

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

        $ip = Update-EnvIP

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
            Write-Host "IP: $ip" -ForegroundColor Cyan
            Write-Host "Backend : http://$($ip):8000"
            Write-Host "WebSocket: ws://$($ip):8080"
        }
        else {
            Write-Host "No se pudo detectar la IP." -ForegroundColor Red
        }
    }

    default {

        Write-Host ""
        Write-Host "Uso: .\scripts\dev.ps1 <comando>"
        Write-Host ""
        Write-Host "start    Levanta todo"
        Write-Host "stop     Apaga todo"
        Write-Host "set-ip   Actualiza el .env"
        Write-Host "ip       Muestra la IP"
    }
}
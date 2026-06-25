param([string]$Command = "help")

$RootPath = Split-Path -Parent $PSScriptRoot
Set-Location $RootPath

switch ($Command) {
    "up" {
        Write-Host "Iniciando Postgres..."
        docker-compose up -d
    }
    "down" {
        Write-Host "Deteniendo Postgres..."
        docker-compose down
    }
    "logs" {
        Write-Host "Logs de Postgres..."
        docker-compose logs -f postgres
    }
    "status" {
        docker-compose ps
    }
    default {
        Write-Host "Uso: ./scripts/docker.ps1 <comando>"
        Write-Host "  up      - Iniciar Postgres"
        Write-Host "  down    - Detener Postgres"
        Write-Host "  logs    - Ver logs de Postgres"
        Write-Host "  status  - Ver estado del contenedor"
    }
}
param([string]$Command = "help", [string[]]$Args)

$ScriptDir = $PSScriptRoot
$BackendPath = Join-Path (Split-Path -Parent $ScriptDir) "backend"
$ImageName = "agromarket-backend"
$ContainerName = "agromarket-backend-dev"
$EnvFile = Join-Path $BackendPath ".env"
$PublicStoragePath = Join-Path $BackendPath "storage\app\public"

Set-Location $BackendPath

function Ensure-Image {
    $exists = docker image inspect $ImageName 2>$null
    if (-not $exists) {
        Write-Host "Imagen $ImageName no encontrada. Construyendo..."
        docker build -t $ImageName -f Dockerfile .
    }
}

function Ensure-StoragePath {
    if (-not (Test-Path $PublicStoragePath)) {
        New-Item -ItemType Directory -Path $PublicStoragePath -Force | Out-Null
    }
}

function Run-Artisan {
    param([string[]]$ArtisanArgs)
    Ensure-Image
    docker run --rm `
        --add-host=host.docker.internal:host-gateway `
        --env-file $EnvFile `
        -e DB_HOST=host.docker.internal `
        --entrypoint php `
        $ImageName artisan @ArtisanArgs
}

switch ($Command) {
    "start" {
        Ensure-Image
        Ensure-StoragePath
        Write-Host "Iniciando backend en http://127.0.0.1:8000 ..."
        docker rm -f $ContainerName 2>$null | Out-Null
        docker run -d `
            --name $ContainerName `
            --rm `
            -p 8000:8000 `
            -v "${PublicStoragePath}:/var/www/html/storage/app/public" `
            --add-host=host.docker.internal:host-gateway `
            --env-file $EnvFile `
            -e DB_HOST=host.docker.internal `
            --entrypoint php `
            $ImageName artisan serve --host=0.0.0.0 --port=8000 | Out-Null
        Write-Host "Backend iniciado. Usa './scripts/backend.ps1 logs' para ver salida."
    }
    "rebuild" {
    Write-Host "Reconstruyendo imagen con el código actual..."
    Set-Location $BackendPath
    docker build -t $ImageName .
    Write-Host "Imagen reconstruida. Reinicia el backend con 'start'."
    }
    "rebuild-restart" {
    Write-Host "Deteniendo backend..."
    docker rm -f $ContainerName 2>$null | Out-Null
    Ensure-StoragePath
    Write-Host "Reconstruyendo imagen..."
    Set-Location $BackendPath
    docker build -t $ImageName .
    Set-Location (Split-Path -Parent $ScriptDir)
    Write-Host "Iniciando backend..."
    docker run -d `
        --name $ContainerName `
        -p 8000:8000 `
        -v "${PublicStoragePath}:/var/www/html/storage/app/public" `
        --add-host=host.docker.internal:host-gateway `
        --env-file $EnvFile `
        -e DB_HOST=host.docker.internal `
        --entrypoint php `
        $ImageName artisan serve --host=0.0.0.0 --port=8000 | Out-Null
    Write-Host "Listo. Backend corriendo en http://127.0.0.1:8000"
    }
    "stop" {
        docker rm -f $ContainerName 2>$null | Out-Null
        Write-Host "Backend detenido."
    }
    "logs" {
        docker logs -f $ContainerName
    }
    "install" {
        docker build -t $ImageName -f Dockerfile .
    }
    "migrate" {
        Run-Artisan @("migrate", "--force")
    }
    "seed" {
        Run-Artisan @("db:seed", "--force")
    }
    "migrate-fresh" {
        Run-Artisan @("migrate:fresh", "--force")
    }
    "migrate-fresh-seed" {
        Run-Artisan @("migrate:fresh", "--seed", "--force")
    }
    "cache-clear" {
        Run-Artisan @("config:clear")
        Run-Artisan @("cache:clear")
        Run-Artisan @("route:clear")
        Run-Artisan @("view:clear")
    }
    "tinker" {
        docker run -it --rm `
            --add-host=host.docker.internal:host-gateway `
            --env-file $EnvFile `
            -e DB_HOST=host.docker.internal `
            --entrypoint php `
            $ImageName artisan tinker
    }
    "routes" {
        Run-Artisan @("route:list", "--path=api")
    }
    "reverb" {
        Write-Host "Iniciando servidor WebSocket (Reverb) en puerto 8080..."
        docker run -d `
            --name agromarket-reverb `
            -p 8080:8080 `
            --add-host=host.docker.internal:host-gateway `
            --env-file $EnvFile `
            -e DB_HOST=host.docker.internal `
            --entrypoint php `
            $ImageName artisan reverb:start --host=0.0.0.0 --port=8080
        Write-Host "Reverb corriendo en ws://127.0.0.1:8080"
    }
    "reverb-stop" {
        docker rm -f agromarket-reverb 2>$null | Out-Null
        Write-Host "Reverb detenido."
    }
    default {
        Write-Host "Comandos disponibles:"
        Write-Host "  start              - Iniciar backend (artisan serve)"
        Write-Host "  stop               - Detener backend"
        Write-Host "  logs               - Ver logs del backend"
        Write-Host "  install            - Construir imagen"
        Write-Host "  migrate            - Ejecutar migraciones"
        Write-Host "  seed               - Ejecutar seeders"
        Write-Host "  migrate-fresh      - Resetear BD y migrar"
        Write-Host "  migrate-fresh-seed - Resetear BD, migrar y seed"
        Write-Host "  cache-clear        - Limpiar cachés"
        Write-Host "  tinker             - Iniciar Tinker REPL"
        Write-Host "  routes             - Listar rutas API"
        Write-Host "  rebuild            - Reconstruir imagen con código actualizado"
        Write-Host "  rebuild-restart    - Detener, reconstruir e iniciar backend"
        Write-Host "  reverb             - Iniciar servidor WebSocket"
        Write-Host "  reverb-stop        - Detener servidor WebSocket"
    }
}
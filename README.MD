# AGROMARKET FCVT — MVP

E-commerce de "Vendedor Único" con sistema de logística colaborativa interna (estudiantes repartidores) operando dentro del campus de la ULEAM (Facultad de Ciencias Veterinarias y Tecnológicas). Funciona bajo una política estricta de **Cero Efectivo** y validación de entregas mediante **escaneo de códigos QR**.

## Stack Tecnológico

- **Backend & API:** Laravel 13 (PHP 8.4) + Laravel Sanctum (Auth) + PostgreSQL
- **Panel Administrativo:** Laravel Filament
- **Frontend Móvil:** React Native + TypeScript
- **Tiempo Real:** Laravel Reverb / Pusher (WebSockets)
- **Entorno de desarrollo:** Docker (PostgreSQL en contenedor, Laravel ejecutado vía imagen Docker propia)

## Reglas de Negocio Críticas

1. **Sin pasarela de pagos.** El pago es externo (transferencia bancaria). El cliente sube una captura del comprobante en el checkout.
2. **Aprobación manual.** El pedido entra con estado `pendiente_validacion`. Solo cuando el Admin aprueba la captura desde Filament, el stock se reduce y el pedido pasa a `preparando`.
3. **Apretón de manos QR.** Al aprobar el pedido se genera un hash único que la app del cliente renderiza como código QR.
4. **Validación de entrega.** El pedido solo pasa a `entregado` si la app del repartidor escanea con la cámara el QR del cliente.
5. **Billetera virtual del repartidor.** Al confirmar el escaneo exitoso, el backend suma automáticamente **$0.25** al balance del repartidor.

## Roadmap del Proyecto

### Fase 1. Instalación de Laravel y entorno ✅ COMPLETA
- [x] Crear estructura de carpetas (`backend/`, `scripts/`)
- [x] Configurar `docker-compose.yml` con PostgreSQL (`agromarket_db`, puerto host `5433`, volumen `agromarket_postgres_data`)
- [x] Crear `backend/Dockerfile` (PHP 8.4-cli-alpine + extensiones: `pdo`, `pdo_pgsql`, `pgsql`, `zip`, `bcmath`)
- [x] Construir imagen `agromarket-backend`
- [x] Instalar Laravel 13 dentro de `backend/` vía Composer local
- [x] Configurar `.env` (`DB_CONNECTION=pgsql`, `DB_HOST=host.docker.internal`, `DB_PORT=5433`)
- [x] Verificar conexión a PostgreSQL y correr migraciones base
- [x] Crear scripts `docker.ps1` / `backend.ps1` (PowerShell) para no repetir comandos `docker run` largos
- [x] Instalar Laravel Sanctum (`HasApiTokens` agregado al modelo `User`)
- [x] Instalar Laravel Filament v3.3.54 y generar panel admin (`/admin`)
- [x] Crear primer usuario administrador (`php artisan make:filament-user`)
- [x] Agregar extensión PHP `intl` al Dockerfile (requerida por Filament)

### Fase 2. Configuración básica ✅ COMPLETA
- [x] Configurar `FILESYSTEM_DISK=public` para fotos de productos y comprobantes
- [x] Configurar storage symlink (`php artisan storage:link`)
- [x] Ajustar límites de subida de archivos en PHP (`upload_max_filesize=20M`, `post_max_size=20M`, `memory_limit=256M` `max_execution_time=120`)
- [x] Excluir `public/storage` del contexto de build en `.dockerignore` (el symlink no se puede copiar al construir la imagen en Windows)
- [x] Acordar convención de carpetas en `storage/app/public/`: `productos/` (Fase 4) y `comprobantes/` (Fase 5)

### Fase 3. Modelado de base de datos
- [ ] Migraciones: `categorias`, `productos`, `usuarios` (con `rol`, `es_repartidor`, `balance`), `pedidos`, `detalle_pedido`
- [ ] Seeders de prueba (categorías y productos demo)
- [ ] Verificar relaciones (FK) en PostgreSQL

### Fase 4. Panel Admin — Módulo Inventario
- [ ] Filament Resource para `Producto` (nombre, precio, categoría, stock, foto)
- [ ] Filament Resource para `Categoria`
- [ ] Validaciones de formulario (stock ≥ 0, precio > 0)

### Fase 5. Panel Admin — Módulo Caja / Validación
- [ ] Vista de pedidos `pendiente_validacion` con imagen del comprobante
- [ ] Botones "Aprobar" (genera QR + reduce stock) y "Rechazar"
- [ ] Lógica del hash/QR en el backend

### Fase 6. Panel Admin — Módulo Liquidación
- [ ] Tabla de repartidores con balance acumulado
- [ ] Acción "Resetear / Pagar Balance"

### Fase 7. Instalación de React Native
- [ ] Setup del proyecto (Expo o CLI) + TypeScript
- [ ] Estructura de carpetas (`screens/`, `services/`, `navigation/`)

### Fase 8. Construcción de la API REST
- [ ] Endpoints de autenticación (Sanctum)
- [ ] Endpoint catálogo (`stock_fisico > 0`)
- [ ] Endpoint checkout (carrito + subida de comprobante)
- [ ] Endpoint `POST /api/orders/{id}/complete` (escaneo QR)

### Fase 9. Conexión Backend ↔ Frontend
- [ ] Cliente HTTP (Axios) en React Native
- [ ] Manejo de tokens Sanctum en la app
- [ ] Pruebas de consumo de los endpoints de la Fase 8

### Fase 10. App Cliente — pantallas
- [ ] Catálogo, Carrito, Checkout (subida de foto), Seguimiento con QR

### Fase 11. App Repartidor — pantallas
- [ ] Tablero de Viajes (WebSockets), Escáner QR, Billetera

### Fase 12. Tiempo real (Reverb / Pusher)
- [ ] Configuración del servidor WebSocket
- [ ] Broadcasting de eventos (pedido listo para delivery)

## Entorno de Desarrollo (Docker)

Este proyecto usa Docker **solo para PostgreSQL**; Laravel se ejecuta a través de una imagen propia (`agromarket-backend`) vía `docker run`, sin `docker-compose up` para el backend. El entorno está completamente aislado del proyecto Observatorio ULEAM (red, volúmenes, puertos y nombres de contenedor propios).

### Levantar PostgreSQL

```powershell
./scripts/docker.ps1 up
./scripts/docker.ps1 status
```

### Construir la imagen de Laravel

```powershell
./scripts/backend.ps1 install
```

> El `Dockerfile` incluye: PHP 8.4-cli-alpine, extensiones `pdo`, `pdo_pgsql`, `pgsql`, `zip`, `bcmath`, `intl` (requerida por Filament), y límites de subida de archivos ajustados (`upload_max_filesize=20M`, `post_max_size=20M`, `memory_limit=256M`). El `.dockerignore` excluye `public/storage` (symlink que no se puede copiar en builds de Windows).

### Ejecutar comandos artisan

Usar siempre el script `backend.ps1`, que ya incluye las variables de entorno y el volumen montado para reflejar cambios de código en tiempo real:

```powershell
./scripts/backend.ps1 migrate
./scripts/backend.ps1 seed
./scripts/backend.ps1 tinker
./scripts/backend.ps1 routes
```

Comando equivalente manual (referencia, normalmente no se usa directo):

```powershell
docker run --rm `
  --add-host=host.docker.internal:host-gateway `
  --env-file backend/.env `
  -e DB_HOST=host.docker.internal `
  -v "${PWD}/backend:/var/www/html" `
  --entrypoint php `
  agromarket-backend artisan <comando>
```

### Levantar el backend (servidor de desarrollo)

```powershell
./scripts/backend.ps1 start   # http://127.0.0.1:8000 — panel admin en /admin
./scripts/backend.ps1 logs
./scripts/backend.ps1 stop
```

### Rutina diaria — retomar el proyecto

```powershell
cd D:\Agromarket
./scripts/docker.ps1 up          # 1. Levantar Postgres
./scripts/docker.ps1 status      # 2. Esperar a que diga (healthy)
./scripts/backend.ps1 start      # 3. Levantar Laravel
# Abrir http://127.0.0.1:8000/admin
```

### Rutina diaria — apagar al terminar

```powershell
./scripts/backend.ps1 stop
./scripts/docker.ps1 down
```

> `docker.ps1 down` (sin `-v`) detiene y borra el contenedor de Postgres, pero **no borra el volumen** `agromarket_postgres_data` — los datos persisten. Solo usar `docker-compose down -v` manualmente si se quiere resetear todo desde cero.

### Variables de entorno clave (`backend/.env`)

```env
APP_NAME=Agromarket
APP_URL=http://localhost:8000

DB_CONNECTION=pgsql
DB_HOST=host.docker.internal
DB_PORT=5433
DB_DATABASE=agromarket_fcvt
DB_USERNAME=postgres
DB_PASSWORD=<tu password definido en docker-compose.yml>
```

> ⚠️ **Nota sobre el puerto:** `DB_PORT` debe ser `5433`, que es el puerto que el contenedor `agromarket_db` publica en el host (mapeo `5433:5432` en `docker-compose.yml`). Internamente, Postgres siempre escucha en el puerto estándar `5432` dentro del contenedor; `5433` es solo la puerta de entrada desde Windows, elegida para no chocar con el puerto `5432` por defecto que usa el proyecto Observatorio ULEAM si ambos están corriendo a la vez.

## Estructura del Proyecto

```
agromarket-fcvt/
├── backend/        # Laravel 13 + Filament (API + Panel Admin)
├── scripts/        # Scripts de automatización (docker.ps1, backend.ps1)
└── docker-compose.yml
```
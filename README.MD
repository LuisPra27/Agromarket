# AGROMARKET FCVT — MVP

E-commerce de "Vendedor Único" con sistema de logística colaborativa interna (estudiantes repartidores) operando dentro del campus de la ULEAM (Facultad de Ciencias Veterinarias y Tecnológicas). Funciona bajo una política estricta de **Cero Efectivo** y validación de entregas mediante **escaneo de códigos QR**.

## Stack Tecnológico

- **Backend & API:** Laravel (PHP 8.4) + Laravel Sanctum (Auth) + PostgreSQL
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

### Fase 1. Instalación de Laravel y entorno ✅
- [ ] Crear estructura de carpetas (`backend/`, `scripts/`)
- [ ] Configurar `docker-compose.yml` con PostgreSQL (`agromarket_db`, puerto host `5433`, volumen `agromarket_postgres_data`)
- [ ] Crear `backend/Dockerfile` (PHP 8.4-cli-alpine + extensiones: `pdo_pgsql`, `gd`, `zip`, `bcmath`, `intl`, `opcache`)
- [ ] Construir imagen `agromarket-backend`
- [ ] Instalar Laravel dentro de `backend/` vía contenedor
- [ ] Configurar `.env` (`DB_CONNECTION=pgsql`, `DB_HOST=host.docker.internal`, `DB_PORT=5433`)
- [ ] Verificar conexión a PostgreSQL y correr migraciones base
- [ ] Instalar Laravel Sanctum
- [ ] Instalar Laravel Filament
- [ ] Crear scripts `docker.sh` / `backend.sh` (o `.ps1` para Windows) para no repetir comandos `docker run` largos

### Fase 2. Configuración básica
- [ ] Configurar `FILESYSTEM_DISK` para fotos de productos y comprobantes
- [ ] Crear usuario admin para Filament (`php artisan make:filament-user`)
- [ ] Configurar storage symlink (`php artisan storage:link`)

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

Este proyecto usa Docker **solo para PostgreSQL**; Laravel se ejecuta a través de una imagen propia (`agromarket-backend`) vía `docker run`, sin `docker-compose up` para el backend. El entorno está completamente aislado del proyecto Observatorio ULEAM (red, volúmenes y nombres de contenedor propios).

### Levantar PostgreSQL

```powershell
docker-compose up -d
docker-compose ps
```

### Construir la imagen de Laravel

```powershell
docker build -t agromarket-backend -f backend/Dockerfile backend
```

### Ejecutar comandos artisan

```powershell
docker run --rm -v "${PWD}/backend:/app" --add-host=host.docker.internal:host-gateway agromarket-backend php artisan <comando>
```

### Variables de entorno clave (`backend/.env`)

```env
DB_CONNECTION=pgsql
DB_HOST=host.docker.internal
DB_PORT=5433
DB_DATABASE=agromarket_fcvt
DB_USERNAME=postgres
DB_PASSWORD=postgres123
```

> ⚠️ **Nota importante sobre el puerto:** `DB_PORT` debe ser `5433` (el puerto publicado en el host según `docker-compose.yml`), no `5432`. Usar `5432` puede conectar accidentalmente a otro PostgreSQL corriendo localmente en el host (por ejemplo, una instancia nativa de Windows o de otro proyecto), causando el error `FATAL: no existe la base de datos` aunque la base sí exista dentro del contenedor correcto.

## Estructura del Proyecto

```
agromarket-fcvt/
├── backend/        # Laravel + Filament (API + Panel Admin)
├── scripts/        # Scripts de automatización Docker
└── docker-compose.yml
```
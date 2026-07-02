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
- [x] Ajustar límites de subida de archivos en PHP (`upload_max_filesize=20M`, `post_max_size=20M`, `memory_limit=256M`, `max_execution_time=120`)
- [x] Excluir `public/storage` del contexto de build en `.dockerignore` (el symlink no se puede copiar al construir la imagen en Windows)
- [x] Acordar convención de carpetas en `storage/app/public/`: `productos/` (Fase 4) y `comprobantes/` (Fase 5)

### Fase 3. Modelado de base de datos ✅ COMPLETA
- [x] Migración `categorias` (id, nombre, descripcion)
- [x] Migración `productos` (FK a categorias, ON DELETE RESTRICT; nombre, precio, stock, imagen_url)
- [x] Migración `usuarios` (cedula, nombre_completo, correo, clave, rol enum [administrador, cliente], es_repartidor, balance)
- [x] Migración `pedidos` (FK cliente_id y repartidor_id a usuarios; total, metodo_entrega enum [retiro, delivery], estado enum [pendiente_validacion, rechazado, preparando, listo_para_delivery, en_camino, entregado], comprobante_pago_url, codigo_qr_hash único, punto_encuentro + pin_x + pin_y para ubicación tipo "pin sobre mapa del campus")
- [x] Migración `detalle_pedido` (FK a pedidos ON DELETE CASCADE, FK a productos ON DELETE RESTRICT; cantidad, precio_unitario histórico, subtotal)
- [x] Verificadas las 5 tablas y sus constraints (FK, CHECK, UNIQUE) directamente en PostgreSQL

> **Decisión de diseño — ubicación de entrega:** se descartó un catálogo `ubicaciones` (tabla aparte) y coordenadas GPS reales. En su lugar, `pedidos` guarda un campo de texto libre (`punto_encuentro`) más dos coordenadas porcentuales (`pin_x`, `pin_y`) que ubican un pin sobre una imagen estática del mapa del campus ULEAM. Esto resuelve que estudiantes no familiarizados con los nombres de las facultades puedan señalar visualmente dónde están, sin la complejidad de GPS en tiempo real ni un catálogo de puntos fijos.

> **Pendiente para fase de Auth (Fase 8):** el modelo de autenticación usa la tabla `usuarios` (no la `users` default de Laravel). Habrá que apuntar el modelo `User`/guard de Sanctum a `usuarios` y revisar el campo `clave` vs `password`.

### Fase 4. Panel Admin — Módulo Inventario ✅ COMPLETA
- [x] Modelo `Categoria` (relación hasMany con Producto)
- [x] Modelo `Producto` (relación belongsTo con Categoria, casts de precio/stock)
- [x] Filament Resource `CategoriaResource` (nombre, descripción, conteo de productos)
- [x] Filament Resource `ProductoResource` (select de categoría, precio con validación > 0, stock con validación ≥ 0, upload de imagen, badge de color según stock)
- [x] CRUD probado end-to-end: crear categoría, crear producto con foto, visualización de imagen en tabla y formulario

> **Hallazgo técnico — servir imágenes (Windows + Docker + volumen montado):** la ruta estándar de Laravel `/storage/{archivo}` (vía symlink `public/storage`) no funciona en este entorno. El symlink creado por `php artisan storage:link` queda roto al verlo desde Windows (`Get-Item` devuelve `Target {}`), y aunque se reemplazó por una ruta personalizada, **cualquier URL que empiece con `/storage/`** es interceptada y devuelve 403 Forbidden — comportamiento específico del servidor de desarrollo embebido de PHP (`artisan serve`) al intentar resolver esa ruta como archivo estático. Los permisos del archivo en sí están correctos (confirmado con `ls -la` dentro del contenedor, proceso corre como `root`).
>
> **Solución adoptada:**
> 1. Se eliminó el symlink `public/storage`.
> 2. Se creó una ruta propia en `routes/web.php`: `GET /archivos/{path}`, que lee el archivo vía `Storage::disk('public')->get($path)` y lo devuelve manualmente con el `Content-Type` correcto (mapeo de extensión a MIME, ya que `Storage::mimeType()` no está declarado en los stubs y genera warning de Intelephense — aunque funciona en runtime, se evitó por completo).
> 3. Se actualizó `config/filesystems.php` (disco `public`, clave `url`) para generar URLs con `/archivos` en vez de `/storage` — así Filament y los futuros endpoints de API generan automáticamente la URL correcta sin duplicar lógica.
> 4. Se corrigió `APP_URL` en `.env` (debía incluir el puerto, `http://localhost:8000`, no solo `http://localhost`) — sin el puerto, las URLs generadas apuntaban al puerto 80 en vez de 8000, causando que las imágenes no cargaran en Filament sin ningún error visible.
>
> Este problema es específico del entorno de desarrollo local (Windows + volumen montado). En producción (Nginx, sin volumen montado, symlink creado dentro de la imagen vía `COPY`) no debería ocurrir, pero no es necesario revertir nada — `/archivos` funciona igual de bien ahí.

### Fase 5. Panel Admin — Módulo Caja / Validación ✅ COMPLETA
- [x] Modelo `Pedido` (belongsTo cliente/repartidor vía `Usuario`, hasMany `DetallePedido`, casts de total/pin_x/pin_y)
- [x] Modelo `DetallePedido` (tabla `detalle_pedido` explícita por no seguir convención de pluralización, belongsTo a `Pedido` y `Producto`)
- [x] Modelo `Usuario` (distinto del `User` de Sanctum/Laravel — ver nota de Fase 3 sobre unificación pendiente en Fase 8)
- [x] `PedidoResource` filtrado a solo `estado = pendiente_validacion` (vía `getEloquentQuery()`)
- [x] Columna de imagen mostrando el comprobante de pago directamente en la tabla
- [x] Acción **"Aprobar"**: dentro de una transacción (`DB::transaction`), valida stock suficiente, decrementa stock de cada producto del detalle, genera `codigo_qr_hash` (UUID) y cambia estado a `preparando`
- [x] Acción **"Rechazar"**: cambia estado a `rechazado` sin tocar stock
- [x] Probado end-to-end con datos de prueba vía Tinker: aprobación generó QR hash y redujo stock correctamente (verificado directo en PostgreSQL)

> **Nota de diseño:** se removió la opción de "crear" pedido desde el panel admin (`getPages()` sin la ruta `create`) — los pedidos los originan los clientes desde la app móvil (Fase 8+), el admin solo aprueba/rechaza los que ya existen.

### Fase 6. Panel Admin — Módulo Liquidación ✅ COMPLETA
- [x] Migración `configuraciones` (clave única, valor, descripción) — tabla de configuración global del sistema
- [x] Migración `liquidaciones` (FK a usuarios ON DELETE RESTRICT, monto_pagado, observaciones) — historial de pagos a repartidores
- [x] Modelo `Configuracion` (tabla explícita `configuraciones`, helper estático `Configuracion::get('clave', $default)` para leer valores desde cualquier parte del código)
- [x] Modelo `Liquidacion` (tabla explícita `liquidaciones`, belongsTo Usuario, cast decimal en monto_pagado)
- [x] Relación `liquidaciones()` agregada al modelo `Usuario`
- [x] Seeder inicial: `incentivo_repartidor = 0.25` (monto en USD por entrega, configurable desde el panel)
- [x] Migración `add_repartidor_fields_to_usuarios_table`: elimina `es_repartidor` (boolean), agrega `estado_repartidor` (enum: no_postulado/pendiente/aprobado/rechazado) y `facultad` (nullable)
- [x] `UsuarioResource` (Filament): tabla de usuarios con filtro por estado_repartidor, badge de color por estado, botones "Aprobar"/"Rechazar" visibles solo cuando estado=pendiente, campo clave con hash pendiente para Fase 8
- [x] `LiquidacionResource` (Filament): vista filtrada solo a repartidores aprobados, balance con badge verde/gris, botón "Resetear/Pagar" visible solo si balance > 0 — al ejecutarse crea registro en `liquidaciones` (historial) y resetea balance a 0
- [x] `ConfiguracionResource` (Filament): vista de configuraciones del sistema, clave deshabilitada (no editable), solo se edita el valor — el admin puede cambiar el incentivo por entrega sin tocar código
- [x] Probado end-to-end: aprobación de repartidor, liquidación con historial registrado, edición de configuración global

> **Decisión de diseño — flujo de postulación a repartidor:** cualquier usuario puede postular (cambia su estado a `pendiente`), pero necesita `cedula` (ya obligatorio desde registro) y `facultad` (obligatorio al postular, validación a nivel de formulario, no de BD). El admin aprueba/rechaza desde el panel. La `facultad` sirve como dato de trazabilidad en caso de incidentes de entrega.

> **Decisión de diseño — correo institucional:** el formato es `e{cedula}@live.uleam.edu.ec`, asignado por la ULEAM vía Microsoft 365/Entra ID. Se evaluó OAuth con Microsoft pero se descartó para el MVP (requiere registro institucional en Azure). Se usa login propio con validación de formato del correo institucional (pendiente implementar en Fase 8). Microsoft login queda como mejora futura post-MVP.

> **Decisión de diseño — incentivo configurable:** el monto por entrega ($0.25 por defecto) no está hardcodeado en el código sino en la tabla `configuraciones`. Se lee con `Configuracion::get('incentivo_repartidor', 0.25)` desde cualquier parte del sistema — el admin puede cambiarlo desde el panel sin necesidad de redeploy.

> **Pendiente para Fase 8:** hashear la contraseña (`clave`) antes de guardar en `UsuarioResource` — actualmente se guarda en texto plano desde Filament (aceptable para desarrollo, no para producción).

> **Nota técnica:** los modelos `Configuracion`, `Liquidacion` y `DetallePedido` requieren `protected $table` explícito porque Laravel pluraliza en inglés (`configuracions`, `liquidacions`) y las tablas reales están en español (`configuraciones`, `liquidaciones`). Patrón a seguir en todos los modelos con nombres en español de este proyecto.

### Fase 7. Instalación de React Native ✅ COMPLETA
- [x] Proyecto Expo SDK 57 creado en `frontend/mobile` con template `blank-typescript`
- [x] Dependencias instaladas: axios, @react-native-async-storage/async-storage, expo-image-picker, expo-camera, expo-notifications, expo-constants, @react-navigation/native, @react-navigation/native-stack, @react-navigation/bottom-tabs, react-native-screens, react-native-safe-area-context
- [x] Estructura de carpetas: `src/screens/auth`, `src/screens/cliente`, `src/screens/repartidor`, `src/navigation`, `src/services`, `src/components`, `src/hooks`, `src/types`, `src/store`
- [x] Tipos TypeScript base (`src/types/index.ts`): interfaces Usuario, Categoria, Producto, DetallePedido, Pedido, ItemCarrito, ApiResponse, AuthResponse, PaginatedResponse
- [x] Servicio Axios (`src/services/api.ts`): interceptores para token Sanctum automático y manejo global de 401
- [x] AuthContext (`src/store/AuthContext.tsx`): estado global de sesión con login/logout/actualizarUsuario persistido en AsyncStorage
- [x] Navegación base (`src/navigation/AppNavigator.tsx` + `ClienteTabs.tsx`): patrón Uber — tabs cambian según estado_repartidor del usuario
- [x] Pantallas placeholder creadas para todas las rutas (LoginScreen, CatalogoScreen, MisPedidosScreen, PerfilScreen, RepartidorTabsScreen)
- [x] App.tsx conectado con AuthProvider + AppNavigator
- [x] Probado en celular físico Android vía Expo Go — muestra LoginScreen correctamente

> **Decisión de diseño — app única (patrón Uber):** una sola app para clientes y repartidores. El tab "Repartidor" aparece dinámicamente solo si `estado_repartidor === 'aprobado'`. Cualquier usuario puede postular desde su Perfil.

> **Decisión de producción:** en producción se usa EAS Build (`npx eas build --platform android`) para generar un APK/AAB independiente sin necesidad de Expo Go. Se compila en la nube sin requerir Android Studio localmente.

> **IP local para desarrollo:** el celular se conecta al backend usando `http://192.168.100.13:8000` (IP WiFi local de la PC de desarrollo). Si la red cambia, actualizar `API_URL` en `src/services/api.ts`.

### Fase 8. Construcción de la API REST 🚧 EN PROGRESO
- [x] Registrar `routes/api.php` en `bootstrap/app.php` (Laravel 11+ no lo carga automáticamente)
- [x] Migración `add_expo_push_token_to_usuarios_table`
- [x] Modelo `Usuario` extendido como `Authenticatable` + `HasApiTokens` + cast `hashed` en `clave` + `$authPasswordName = 'clave'`
- [x] `config/auth.php` actualizado para usar `Usuario::class` en vez de `User::class`
- [x] `AuthController` con endpoints: `POST /api/auth/login`, `POST /api/auth/logout`, `GET /api/auth/me`, `POST /api/auth/push-token`
- [x] Login probado desde PowerShell y desde app móvil — devuelve token Sanctum + datos de usuario
- [x] Pantalla `LoginScreen` real conectada al backend — redirige automáticamente a tabs tras login exitoso
- [ ] Endpoint catálogo (`GET /api/productos`)
- [ ] Endpoint checkout (carrito + subida de comprobante)
- [ ] Endpoint `POST /api/orders/{id}/complete` (escaneo QR)
- [ ] Endpoint `POST /api/orders/{id}/accept` (repartidor acepta viaje)
- [ ] Endpoint `GET /api/pedidos` (mis pedidos del cliente)

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

> ⚠️ **Esto NO se corre a diario.** Solo es necesario la primera vez, o cuando se modifique el `Dockerfile` (ej. nueva extensión PHP) o se instale una dependencia nueva vía Composer (ej. un paquete nuevo). Para el día a día, ver la sección "Rutina diaria" más abajo — ahí solo se usa `docker.ps1 up` y `backend.ps1 start`.

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

### Cuando se modifica código PHP (Fase 8+)

A partir de la Fase 8 el backend ya no usa volumen montado (se adoptó el patrón del Observatorio ULEAM: código copiado dentro de la imagen para mejor performance). Cualquier cambio en archivos PHP requiere reconstruir la imagen antes de que se refleje:

```powershell
./scripts/backend.ps1 stop
./scripts/backend.ps1 rebuild
./scripts/backend.ps1 start
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
├── frontend/
│   └── mobile/     # Expo + React Native + TypeScript (app única para cliente y repartidor)
├── scripts/        # Scripts de automatización (docker.ps1, backend.ps1)
└── docker-compose.yml
```

> **Decisión de diseño — app única (patrón Uber):** en vez de dos apps separadas (cliente y repartidor), se usa una sola app que cambia de "modo" según el rol y estado del usuario:
> - `rol = 'cliente'` + `estado_repartidor != 'aprobado'` → tabs: Catálogo, Mis Pedidos, Perfil
> - `rol = 'cliente'` + `estado_repartidor = 'aprobado'` → tabs: Catálogo, Mis Pedidos, **Modo Repartidor**, Perfil
> - Cualquier usuario puede postular a repartidor desde su Perfil
> - El tab "Modo Repartidor" incluye: Tablero de Viajes, Escáner QR, Billetera
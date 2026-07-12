<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Usuario;
use App\Models\Producto;
use App\Models\Pedido;
use App\Models\DetallePedido;
use App\Models\Liquidacion;
use App\Models\Categoria;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class MassiveUleamSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🌱 Iniciando seeder masivo ULEAM (500+ usuarios)...');

        // ============ FACULTADES ULEAM REALES ============
        $facultades = [
            'Ciencias Administrativas',
            'Ciencias Económicas',
            'Ciencias de la Salud',
            'Ciencias Agrarias',
            'Ingeniería',
            'Ciencias Jurídicas y Sociales',
            'Ciencias de la Educación',
            'Ciencias Naturales y Matemáticas',
            'Ciencias Marinas',
            'Ciencias Políticas y Sociales',
        ];

        // ============ NOMBRES/APELLIDOS ECUATORIANOS ============
        $nombresM = ['Luis', 'Juan', 'Carlos', 'José', 'Pedro', 'Miguel', 'Andrés', 'Diego', 'Fernando', 'Roberto', 'Jorge', 'Antonio', 'Daniel', 'David', 'Cristian', 'Eduardo', 'Ricardo', 'Gabriel', 'Alejandro', 'Sebastián', 'Nicolás', 'Mateo', 'Santiago', 'Emilio', 'Adrián', 'Víctor', 'Hugo', 'Pablo', 'Raúl', 'Sergio'];
        $nombresF = ['María', 'Ana', 'Luisa', 'Carmen', 'Rosa', 'Isabel', 'Patricia', 'Mónica', 'Claudia', 'Andrea', 'Paola', 'Verónica', 'Daniela', 'Valeria', 'Camila', 'Sofía', 'Gabriela', 'Natalia', 'Mariana', 'Alejandra', 'Stefany', 'Karen', 'Jéssica', 'Yuliana', 'Estefanía', 'Melissa', 'Bárbara', 'Lorena', 'Silvia', 'Ximena'];
        $apellidos = ['González', 'Rodríguez', 'Gómez', 'Fernández', 'López', 'Martínez', 'Sánchez', 'Pérez', 'Torres', 'Flores', 'Rivera', 'Ramírez', 'Moreno', 'Ortiz', 'Herrera', 'Vargas', 'Castro', 'Rojas', 'Mendoza', 'Jiménez', 'Romero', 'Alvarado', 'Castillo', 'Molina', 'Delgado', 'Aguilar', 'Ramos', 'Gutiérrez', 'Suárez', 'Cortés', 'Ponce', 'Reyes', 'León', 'Campos', 'Guerrero', 'Vera', 'Medina', 'Cruz', 'Velasco', 'Peña', 'Cordero', 'Santos', 'Álvarez', 'Díaz', 'Ruiz', 'Acosta', 'Navarro', 'Mora', 'Salazar', 'Carrillo', 'Cueva', 'Montalvo', 'Paredes', 'Solano', 'Zambrano', 'Loor', 'Plúa', 'Cedeño', 'Vera', 'Mera', 'Cevallos'];

        // ============ GENERAR USUARIOS ============
        $this->command->info('👥 Creando 500+ usuarios ULEAM...');

        $totalUsuarios = 550; // ~500 + buffer
        $repartidoresAprobados = 80;  // ~15%
        $repartidoresPendientes = 30; // ~5%
        $repartidoresRechazados = 20; // ~4%
        $clientes = $totalUsuarios - $repartidoresAprobados - $repartidoresPendientes - $repartidoresRechazados;

        // Admin ya existe, no duplicar
        $cedulasUsadas = ['1234567891']; // Admin

        // Helper para cédula ecuatoriana válida (10 dígitos)
        $generarCedula = function () use (&$cedulasUsadas) {
            for ($i = 0; $i < 100; $i++) {
                $provincia = rand(1, 24);
                $provincia = str_pad($provincia, 2, '0', STR_PAD_LEFT);
                $resto = str_pad(rand(1000000, 9999999), 7, '0', STR_PAD_LEFT);
                $cedula = $provincia . $resto . rand(0, 9);
                if (!in_array($cedula, $cedulasUsadas)) {
                    $cedulasUsadas[] = $cedula;
                    return $cedula;
                }
            }
            // Fallback
            $cedula = '09' . str_pad(rand(10000000, 99999999), 8, '0', STR_PAD_LEFT);
            $cedulasUsadas[] = $cedula;
            return $cedula;
        };

        // ---- 1. REPARTIDORES APROBADOS ----
        $this->command->info("   🛵 Creando {$repartidoresAprobados} repartidores aprobados...");
        for ($i = 0; $i < $repartidoresAprobados; $i++) {
            $genero = rand(0, 1);
            $nombre = ($genero ? $nombresM : $nombresF)[array_rand($genero ? $nombresM : $nombresF)];
            $apellido = $apellidos[array_rand($apellidos)] . ' ' . $apellidos[array_rand($apellidos)];
            $cedula = $generarCedula();
            $facultad = $facultades[array_rand($facultades)];

            Usuario::create([
                'cedula' => $cedula,
                'nombre_completo' => $nombre . ' ' . $apellido,
                'correo' => "e{$cedula}@live.uleam.edu.ec",
                'clave' => Hash::make('password123'),
                'rol' => 'cliente',
                'estado_repartidor' => 'aprobado',
                'facultad' => $facultad,
                'balance' => rand(500, 5000) / 100, // 5.00 - 50.00
                'expo_push_token' => null,
            ]);
        }

        // ---- 2. REPARTIDORES PENDIENTES ----
        $this->command->info("   ⏳ Creando {$repartidoresPendientes} repartidores pendientes...");
        for ($i = 0; $i < $repartidoresPendientes; $i++) {
            $genero = rand(0, 1);
            $nombre = ($genero ? $nombresM : $nombresF)[array_rand($genero ? $nombresM : $nombresF)];
            $apellido = $apellidos[array_rand($apellidos)] . ' ' . $apellidos[array_rand($apellidos)];
            $cedula = $generarCedula();
            $facultad = $facultades[array_rand($facultades)];

            Usuario::create([
                'cedula' => $cedula,
                'nombre_completo' => $nombre . ' ' . $apellido,
                'correo' => "e{$cedula}@live.uleam.edu.ec",
                'clave' => Hash::make('password123'),
                'rol' => 'cliente',
                'estado_repartidor' => 'pendiente',
                'facultad' => $facultad,
                'balance' => 0,
            ]);
        }

        // ---- 3. REPARTIDORES RECHAZADOS ----
        $this->command->info("   ❌ Creando {$repartidoresRechazados} repartidores rechazados...");
        for ($i = 0; $i < $repartidoresRechazados; $i++) {
            $genero = rand(0, 1);
            $nombre = ($genero ? $nombresM : $nombresF)[array_rand($genero ? $nombresM : $nombresF)];
            $apellido = $apellidos[array_rand($apellidos)] . ' ' . $apellidos[array_rand($apellidos)];
            $cedula = $generarCedula();
            $facultad = $facultades[array_rand($facultades)];

            Usuario::create([
                'cedula' => $cedula,
                'nombre_completo' => $nombre . ' ' . $apellido,
                'correo' => "e{$cedula}@live.uleam.edu.ec",
                'clave' => Hash::make('password123'),
                'rol' => 'cliente',
                'estado_repartidor' => 'rechazado',
                'facultad' => $facultad,
                'balance' => 0,
            ]);
        }

        // ---- 4. CLIENTES NORMALES (estudiantes ULEAM) ----
        $this->command->info("   👨‍🎓 Creando {$clientes} estudiantes/clientes ULEAM...");
        $chunkSize = 100;
        $creados = 0;

        while ($creados < $clientes) {
            $lote = min($chunkSize, $clientes - $creados);
            $data = [];

            for ($i = 0; $i < $lote; $i++) {
                $genero = rand(0, 1);
                $nombre = ($genero ? $nombresM : $nombresF)[array_rand($genero ? $nombresM : $nombresF)];
                $apellido = $apellidos[array_rand($apellidos)] . ' ' . $apellidos[array_rand($apellidos)];
                $cedula = $generarCedula();
                $facultad = $facultades[array_rand($facultades)];

                $data[] = [
                    'cedula' => $cedula,
                    'nombre_completo' => $nombre . ' ' . $apellido,
                    'correo' => "e{$cedula}@live.uleam.edu.ec",
                    'clave' => Hash::make('password123'),
                    'rol' => 'cliente',
                    'estado_repartidor' => 'no_postulado',
                    'facultad' => $facultad,
                    'balance' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            DB::table('usuarios')->insert($data);
            $creados += $lote;
            $this->command->info("      Creados {$creados}/{$clientes} clientes...");
        }

        $this->command->info('✅ Usuarios creados: ' . Usuario::count());

        // ============ PRODUCTOS ADICIONALES (si faltan) ============
        $this->command->info('📦 Verificando/creando productos...');
        $productosCount = Producto::count();
        if ($productosCount < 50) {
            $categorias = Categoria::pluck('id')->toArray();
            if (empty($categorias)) {
                $cat = Categoria::create(['nombre' => 'General', 'icono' => '📦']);
                $categorias = [$cat->id];
            }

            $nombresProductos = [
                'Café Americano', 'Café con Leche', 'Capuchino', 'Latte', 'Moca', 'Té Verde', 'Té Negro', 'Chocolate Caliente',
                'Jugo de Naranja', 'Jugo de Maracuyá', 'Jugo de Mora', 'Jugo de Piña', 'Batido de Fresa', 'Batido de Mango',
                'Agua Mineral', 'Agua con Gas', 'Gatorade', 'Coca Cola', 'Sprite', 'Fanta',
                'Sándwich de Pollo', 'Sándwich de Jamón', 'Sándwich Vegetariano', 'Hamburguesa', 'Cheeseburger', 'Hamburguesa Vegana',
                'Papas Fritas', 'Papas con Queso', 'Aros de Cebolla', 'Nuggets de Pollo',
                'Ensalada César', 'Ensalada Mixta', 'Ensalada de Frutas', 'Bowl de Quinoa', 'Wrap de Pollo', 'Wrap Vegano',
                'Pizza Personal', 'Lasagna', 'Espagueti Boloñesa', 'Arroz con Pollo', 'Seco de Pollo', 'Ceviche', 'Encebollado',
                'Empanada de Verde', 'Empanada de Queso', 'Empanada de Carne', 'Bolón de Verde', 'Tostones', 'Chifles',
                'Helado de Vainilla', 'Helado de Chocolate', 'Helado de Fresa', 'Gelatina', 'Flan', 'Torta de Chocolate', 'Cheesecake',
                'Galleta Chispas', 'Brownie', 'Muffin', 'Donut', 'Croissant',
            ];

            for ($i = $productosCount; $i < 60; $i++) {
                $nombre = $nombresProductos[array_rand($nombresProductos)] . ' ' . ($i + 1);
                Producto::create([
                    'nombre' => $nombre,
                    'precio' => rand(50, 1500) / 100, // $0.50 - $15.00
                    'stock' => rand(10, 200),
                    'categoria_id' => $categorias[array_rand($categorias)],
                    'imagen_url' => null,
                ]);
            }
        }

        // ============ PEDIDOS MASIVOS (simular 3 meses de actividad) ============
        $this->command->info('📋 Creando pedidos masivos (historial 3 meses)...');

        $clientesList = Usuario::where('rol', 'cliente')
            ->where('estado_repartidor', 'no_postulado')
            ->get();

        $repartidoresList = Usuario::where('estado_repartidor', 'aprobado')->get();

        $productosList = Producto::where('stock', '>', 0)->where('activo', true)->get();

        if ($clientesList->isEmpty() || $productosList->isEmpty()) {
            $this->command->warn('No hay clientes o productos para crear pedidos de prueba');
            return;
        }

        $estados = ['pendiente_validacion', 'preparando', 'listo_para_delivery', 'en_camino', 'entregado', 'rechazado', 'cancelado'];
        $metodos = ['retiro', 'delivery'];

        // Pesos para estados (más entregados que pendientes)
        $pesosEstados = [
            'entregado' => 45,
            'en_camino' => 10,
            'listo_para_delivery' => 8,
            'preparando' => 7,
            'pendiente_validacion' => 10,
            'cancelado' => 10,
            'rechazado' => 10,
        ];

        $puntosEntrega = [
            'Facultad de Ciencias Administrativas - Entrada principal',
            'Facultad de Ingeniería - Bloque A',
            'Facultad de Ciencias Económicas - Entrada',
            'Facultad de Ciencias de la Salud - Entrada principal',
            'Facultad de Ciencias Agrarias - Entrada',
            'Biblioteca Central - Entrada',
            'Comedor Universitario',
            'Parqueadero Principal',
            'Facultad de Ciencias Jurídicas - Entrada',
            'Facultad de Ciencias de la Educación - Entrada',
        ];

        // ~2000 pedidos en 3 meses
        $totalPedidos = 2000;
        $chunkSizePedidos = 200;
        $creadosPedidos = 0;

        $this->command->info("   Creando {$totalPedidos} pedidos en lotes de {$chunkSizePedidos}...");

        while ($creadosPedidos < $totalPedidos) {
            $lote = min($chunkSizePedidos, $totalPedidos - $creadosPedidos);
            $pedidosData = [];
            $detallesData = [];

            for ($i = 0; $i < $lote; $i++) {
                $cliente = $clientesList->random();
                $estado = $this->randomWeighted($pesosEstados);
                $metodo = $metodos[array_rand($metodos)];
                $numItems = rand(1, 5);
                $items = $productosList->random($numItems);
                $total = 0;
                $detalles = [];

                foreach ($items as $producto) {
                    $cantidad = rand(1, 3);
                    if ($producto->stock < $cantidad) {
                        $cantidad = max(1, $producto->stock);
                    }
                    $subtotal = $producto->precio * $cantidad;
                    $total += $subtotal;

                    $detalles[] = [
                        'producto_id' => $producto->id,
                        'cantidad' => $cantidad,
                        'precio_unitario' => $producto->precio,
                        'subtotal' => $subtotal,
                    ];
                }

                $repartidor_id = null;
                if (in_array($estado, ['en_camino', 'entregado']) && $repartidoresList->isNotEmpty()) {
                    $repartidor_id = $repartidoresList->random()->id;
                }

                $codigo_qr_hash = null;
                if (in_array($estado, ['preparando', 'listo_para_delivery', 'en_camino', 'entregado'])) {
                    // Generar UUID único para código QR
                    do {
                        $uuid = (string) Str::uuid();
                        $existe = Pedido::where('codigo_qr_hash', $uuid)->exists();
                    } while ($existe);
                    $codigo_qr_hash = $uuid;
                }

                // Calcular numero_orden_cliente
                $ultimo = Pedido::where('cliente_id', $cliente->id)->max('numero_orden_cliente') ?? 0;
                $numero_orden = $ultimo + 1;

                // Fecha aleatoria en los últimos 90 días
                $fechaCreacion = now()->subDays(rand(0, 90))->subHours(rand(0, 23))->subMinutes(rand(0, 59));

                $pedidoId = $creadosPedidos + $i + 1; // temporal

                $pedidosData[] = [
                    'cliente_id' => $cliente->id,
                    'repartidor_id' => $repartidor_id,
                    'total' => $total,
                    'metodo_entrega' => $metodo,
                    'estado' => $estado,
                    'comprobante_pago_url' => 'comprobantes/fake_' . $pedidoId . '.jpg',
                    'punto_encuentro' => $metodo === 'delivery' ? $puntosEntrega[array_rand($puntosEntrega)] : null,
                    'pin_x' => $metodo === 'delivery' ? rand(-2000000, 2000000) / 1000000 : null,
                    'pin_y' => $metodo === 'delivery' ? rand(-2000000, 2000000) / 1000000 : null,
                    'codigo_qr_hash' => $codigo_qr_hash,
                    'numero_orden_cliente' => $numero_orden,
                    'created_at' => $fechaCreacion,
                    'updated_at' => $fechaCreacion,
                ];
            }

            // Insertar pedidos en lote
            $insertedPedidos = DB::table('pedidos')->insertGetId($pedidosData[0] ?? []);

            // Para obtener IDs reales, usamos insert y luego recuperamos
            // En MySQL/PostgreSQL podemos usar insertGetId en bucle o batch con retorno
            // Vamos a insertar uno por uno para tener IDs reales
            foreach ($pedidosData as $idx => $pData) {
                $pedido = Pedido::create($pData);

                foreach ($detallesData[$idx] ?? [] as $detalle) {
                    $detalle['pedido_id'] = $pedido->id;
                    DetallePedido::create($detalle);
                    Producto::where('id', $detalle['producto_id'])->decrement('stock', $detalle['cantidad']);
                }
            }

            $creadosPedidos += $lote;
            $this->command->info("      Creados {$creadosPedidos}/{$totalPedidos} pedidos...");
        }

        $this->command->info('✅ Pedidos creados: ' . Pedido::count());

        // ============ LIQUIDACIONES PARA REPARTIDORES ============
        $this->command->info('💰 Generando liquidaciones para repartidores...');

        $entregados = Pedido::where('estado', 'entregado')
            ->whereNotNull('repartidor_id')
            ->get()
            ->groupBy('repartidor_id');

        foreach ($entregados as $repartidorId => $pedidos) {
            $totalEntregas = $pedidos->count();
            $gananciaBruta = $pedidos->sum(function ($p) {
                return $p->total * 0.15; // 15% comisión
            });
            $comisionPlataforma = $gananciaBruta * 0.10; // 10% a plataforma
            $gananciaNeta = $gananciaBruta - $comisionPlataforma;

            if ($totalEntregas > 0) {
                Liquidacion::create([
                    'repartidor_id' => $repartidorId,
                    'periodo_inicio' => now()->subDays(30)->startOfMonth(),
                    'periodo_fin' => now()->subDays(30)->endOfMonth(),
                    'total_entregas' => $totalEntregas,
                    'ganancia_bruta' => round($gananciaBruta, 2),
                    'comision_plataforma' => round($comisionPlataforma, 2),
                    'ganancia_neta' => round($gananciaNeta, 2),
                    'estado' => 'pendiente',
                ]);
            }
        }

        $this->command->info('✅ Liquidaciones creadas: ' . Liquidacion::count());

        // ============ RESUMEN FINAL ============
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->info('📊 RESUMEN SEEDER MASIVO ULEAM');
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->info('👥 Usuarios totales: ' . Usuario::count());
        $this->command->info('   - Admin: 1');
        $this->command->info('   - Repartidores aprobados: ' . Usuario::where('estado_repartidor', 'aprobado')->count());
        $this->command->info('   - Repartidores pendientes: ' . Usuario::where('estado_repartidor', 'pendiente')->count());
        $this->command->info('   - Repartidores rechazados: ' . Usuario::where('estado_repartidor', 'rechazado')->count());
        $this->command->info('   - Clientes (estudiantes): ' . Usuario::where('estado_repartidor', 'no_postulado')->count());
        $this->command->info('📦 Productos: ' . Producto::count());
        $this->command->info('📋 Pedidos totales: ' . Pedido::count());
        $this->command->info('   - Entregados: ' . Pedido::where('estado', 'entregado')->count());
        $this->command->info('   - En camino: ' . Pedido::where('estado', 'en_camino')->count());
        $this->command->info('   - Listos para delivery: ' . Pedido::where('estado', 'listo_para_delivery')->count());
        $this->command->info('   - Preparando: ' . Pedido::where('estado', 'preparando')->count());
        $this->command->info('   - Pendientes validación: ' . Pedido::where('estado', 'pendiente_validacion')->count());
        $this->command->info('   - Cancelados: ' . Pedido::where('estado', 'cancelado')->count());
        $this->command->info('   - Rechazados: ' . Pedido::where('estado', 'rechazado')->count());
        $this->command->info('💰 Liquidaciones: ' . Liquidacion::count());
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->info('✅ Seeder masivo completado. Usa "php artisan migrate:fresh --seed" para resetear.');
    }

    private function randomWeighted(array $weights): string
    {
        $total = array_sum($weights);
        $rand = rand(1, $total);
        $cumulative = 0;

        foreach ($weights as $key => $weight) {
            $cumulative += $weight;
            if ($rand <= $cumulative) {
                return $key;
            }
        }
        return array_key_first($weights);
    }
}
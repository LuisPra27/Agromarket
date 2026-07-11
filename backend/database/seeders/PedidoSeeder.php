<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Usuario;
use App\Models\Pedido;
use App\Models\DetallePedido;
use App\Models\Producto;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class PedidoSeeder extends Seeder
{
    public function run(): void
    {
        $clientes = Usuario::where('rol', 'cliente')
            ->where('estado_repartidor', 'no_postulado')
            ->get();

        $repartidores = Usuario::where('estado_repartidor', 'aprobado')->get();

        $productos = Producto::where('stock', '>', 0)->get();

        if ($clientes->isEmpty() || $productos->isEmpty()) {
            $this->command->warn('No hay clientes o productos para crear pedidos de prueba');
            return;
        }

        $estados = ['pendiente_validacion', 'preparando', 'listo_para_delivery', 'en_camino', 'entregado', 'rechazado', 'cancelado'];
        $metodos = ['retiro', 'delivery'];

        // Crear 30 pedidos variados
        for ($i = 0; $i < 30; $i++) {
            $cliente = $clientes->random();
            $estado = $estados[array_rand($estados)];
            $metodo = $metodos[array_rand($metodos)];
            $numItems = rand(1, 4);

            $items = $productos->random($numItems);
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
            if (in_array($estado, ['en_camino', 'entregado']) && $repartidores->isNotEmpty()) {
                $repartidor_id = $repartidores->random()->id;
            }

            $codigo_qr_hash = in_array($estado, ['preparando', 'listo_para_delivery', 'en_camino', 'entregado'])
                ? (string) Str::uuid()
                : null;

            // Calcular numero_orden_cliente ANTES de crear el pedido
            $ultimo = Pedido::where('cliente_id', $cliente->id)->max('numero_orden_cliente') ?? 0;
            $numero_orden = $ultimo + 1;

            $pedido = Pedido::create([
                'cliente_id' => $cliente->id,
                'repartidor_id' => $repartidor_id,
                'total' => $total,
                'metodo_entrega' => $metodo,
                'estado' => $estado,
                'comprobante_pago_url' => 'comprobantes/fake_' . $i . '.jpg',
                'punto_encuentro' => $metodo === 'delivery' ? $this->randomPuntoEncuentro() : null,
                'pin_x' => $metodo === 'delivery' ? rand(-2000000, 2000000) / 1000000 : null,
                'pin_y' => $metodo === 'delivery' ? rand(-2000000, 2000000) / 1000000 : null,
                'codigo_qr_hash' => $codigo_qr_hash,
                'numero_orden_cliente' => $numero_orden,
            ]);

            foreach ($detalles as $detalle) {
                $detalle['pedido_id'] = $pedido->id;
                DetallePedido::create($detalle);

                // Decrementar stock real
                Producto::where('id', $detalle['producto_id'])->decrement('stock', $detalle['cantidad']);
            }
        }
    }

    private function randomPuntoEncuentro(): string
    {
        $puntos = [
            'Facultad de Ciencias Administrativas - Entrada principal',
            'Facultad de Ingeniería - Bloque A',
            'Facultad de Ciencias Económicas - Entrada',
            'Facultad de Ciencias de la Salud - Entrada principal',
            'Facultad de Ciencias Agrarias - Entrada',
            'Biblioteca Central - Entrada',
            'Comedor Universitario',
            'Parqueadero Principal',
        ];
        return $puntos[array_rand($puntos)];
    }
}
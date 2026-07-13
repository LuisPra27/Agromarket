<?php

namespace App\Http\Controllers\Api;

use App\Events\PedidoCreado;
use App\Http\Controllers\Controller;
use App\Models\Configuracion;
use App\Models\Pedido;
use App\Models\Producto;
use App\Services\PayphoneConfirmacionService;
use App\Services\PayphoneService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PayphoneController extends Controller
{
    // Crea el pedido en estado "pendiente_pago" (sin tocar stock todavía)
    // y prepara la transacción en Payphone, devolviendo la URL de pago.
    public function prepare(Request $request): JsonResponse
    {
        $request->validate([
            'metodo_entrega'       => 'required|in:retiro,delivery',
            'items'                => 'required|array|min:1',
            'items.*.producto_id'  => 'required|integer|exists:productos,id',
            'items.*.cantidad'     => 'required|integer|min:1',
            'punto_encuentro'      => 'nullable|string|max:500',
            'pin_x'                => 'nullable|numeric',
            'pin_y'                => 'nullable|numeric',
        ]);

        $pedido = DB::transaction(function () use ($request) {
            $total = 0;

            foreach ($request->items as $item) {
                $producto = Producto::findOrFail($item['producto_id']);

                if ($producto->stock < $item['cantidad']) {
                    throw new \Exception("Stock insuficiente para {$producto->nombre}.");
                }

                $total += $producto->precio * $item['cantidad'];
            }

            if ($request->metodo_entrega === 'delivery') {
                $total += (float) Configuracion::get('costo_delivery', 0.30);
            }

            $pedido = Pedido::create([
                'cliente_id'           => $request->user()->id,
                'total'                => $total,
                'metodo_entrega'       => $request->metodo_entrega,
                'metodo_pago'          => 'payphone',
                'estado'               => 'pendiente_pago',
                'punto_encuentro'      => $request->punto_encuentro,
                'pin_x'                => $request->pin_x,
                'pin_y'                => $request->pin_y,
                'payphone_client_transaction_id' => (string) Str::uuid(),
                'numero_orden_cliente' => Pedido::where('cliente_id', $request->user()->id)->max('numero_orden_cliente') + 1,
            ]);

            foreach ($request->items as $item) {
                $producto = Producto::findOrFail($item['producto_id']);
                $pedido->detalles()->create([
                    'producto_id'     => $producto->id,
                    'cantidad'        => $item['cantidad'],
                    'precio_unitario' => $producto->precio,
                    'subtotal'        => $producto->precio * $item['cantidad'],
                ]);
            }

            return $pedido;
        });

        $respuestaPayphone = PayphoneService::prepare(
            $pedido->payphone_client_transaction_id,
            (float) $pedido->total,
            "Pedido Agromarket #{$pedido->id}"
        );

        $urlPago = $respuestaPayphone['payWithCard'] ?? $respuestaPayphone['payWithPayPhone'] ?? null;

        if (!$urlPago) {
            $pedido->update(['estado' => 'rechazado']);
            return response()->json(['message' => 'Payphone no devolvió una URL de pago.'], 502);
        }

        $pedido->update(['payphone_pay_url' => $urlPago]);

        event(new PedidoCreado($pedido));

        return response()->json([
            'pedido' => $pedido->load('detalles.producto'),
            // La app debe abrir ESTAS urls (nuestras páginas puente), no las
            // de Payphone directamente: Payphone exige que la navegación
            // hacia su formulario venga desde el dominio registrado.
            'bridge_url' => url("/payphone/bridge/{$pedido->payphone_client_transaction_id}"),
            'bridge_url_cajita' => url("/payphone/cajita/{$pedido->payphone_client_transaction_id}"),
        ], 201);
    }

    // El móvil puede llamar esto como respaldo/verificación (la confirmación
    // "real" ya ocurre en la página puente web cuando Payphone redirige ahí).
    public function confirm(Request $request): JsonResponse
    {
        $request->validate([
            'id' => 'required|integer',
            'clientTransactionId' => 'required|string',
        ]);

        $pedido = Pedido::where('payphone_client_transaction_id', $request->clientTransactionId)
            ->where('cliente_id', $request->user()->id)
            ->first();

        if (!$pedido) {
            return response()->json(['message' => 'Pedido no encontrado.'], 404);
        }

        $resultado = PayphoneConfirmacionService::confirmar(
            $request->clientTransactionId,
            (int) $request->id
        );

        return match ($resultado['resultado']) {
            PayphoneConfirmacionService::OK => response()->json([
                'message' => 'Pago confirmado. Tu pedido ya está en preparación.',
                'pedido' => $resultado['pedido'],
            ]),
            PayphoneConfirmacionService::YA_PROCESADO => response()->json([
                'message' => 'Este pedido ya fue procesado.',
                'pedido' => $resultado['pedido'],
            ]),
            PayphoneConfirmacionService::SIN_STOCK => response()->json([
                'message' => 'El pago se procesó pero ya no hay stock suficiente. Un administrador revisará tu pedido.',
                'pedido' => $resultado['pedido'],
            ], 409),
            default => response()->json([
                'message' => 'El pago no fue aprobado por Payphone.',
                'pedido' => $resultado['pedido'],
            ], 422),
        };
    }
}

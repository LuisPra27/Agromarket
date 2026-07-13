<?php

namespace App\Http\Controllers\Api;

use App\Events\PedidoCreado;
use App\Http\Controllers\Controller;
use App\Models\Configuracion;
use App\Models\Pedido;
use App\Models\Producto;
use App\Services\PayphoneService;
use App\Services\PedidoAprobacionService;
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

        event(new PedidoCreado($pedido));

        return response()->json([
            'pedido' => $pedido->load('detalles.producto'),
            'payment_url' => $respuestaPayphone['payWithCard'] ?? $respuestaPayphone['payWithPayPhone'] ?? null,
        ], 201);
    }

    // El móvil llama esto al volver del navegador de pago, con los parámetros
    // "id" y "clientTransactionId" que Payphone agrega al redirect. Confirmamos
    // contra la API de Payphone (nunca confiamos solo en el redirect) y, si el
    // pago fue aprobado, reutilizamos el mismo servicio que usa la aprobación
    // manual en Filament para reducir stock y generar el QR.
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

        if ($pedido->estado !== 'pendiente_pago') {
            return response()->json([
                'message' => 'Este pedido ya fue procesado.',
                'pedido' => $pedido,
            ]);
        }

        $resultado = PayphoneService::confirm((int) $request->id, $request->clientTransactionId);

        if (($resultado['transactionStatus'] ?? null) !== 'Approved') {
            $pedido->update(['estado' => 'rechazado']);

            return response()->json([
                'message' => 'El pago no fue aprobado por Payphone.',
                'pedido' => $pedido,
            ], 422);
        }

        $pedido->update(['payphone_transaction_id' => (string) $request->id]);

        try {
            PedidoAprobacionService::aprobar($pedido);
        } catch (\Throwable $e) {
            // El pago sí se cobró pero ya no hay stock (caso raro: se agotó
            // entre el prepare y el confirm). Dejamos rastro claro para que
            // el admin resuelva manualmente el reembolso.
            $pedido->update(['estado' => 'pendiente_validacion']);

            return response()->json([
                'message' => 'El pago se procesó pero ya no hay stock suficiente. Un administrador revisará tu pedido.',
                'pedido' => $pedido->fresh(),
            ], 409);
        }

        return response()->json([
            'message' => 'Pago confirmado. Tu pedido ya está en preparación.',
            'pedido' => $pedido->fresh()->load('detalles.producto'),
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pedido;
use App\Models\Producto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PedidoController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $pedidos = Pedido::with(['detalles.producto'])
            ->where('cliente_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($pedidos);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'metodo_entrega'       => 'required|in:retiro,delivery',
            'items'                => 'required|array|min:1',
            'items.*.producto_id'  => 'required|integer|exists:productos,id',
            'items.*.cantidad'     => 'required|integer|min:1',
            'comprobante'          => 'required|file|mimes:jpg,jpeg,png,pdf|max:20480',
            'punto_encuentro'      => 'nullable|string|max:500',
            'pin_x'                => 'nullable|numeric',
            'pin_y'                => 'nullable|numeric',
        ]);

        // Subir comprobante
        $path = $request->file('comprobante')->store('comprobantes', 'public');

        $pedido = DB::transaction(function () use ($request, $path) {
            // Calcular total
            $total = 0;
            $items = [];

            foreach ($request->items as $item) {
                $producto = Producto::findOrFail($item['producto_id']);

                if ($producto->stock < $item['cantidad']) {
                    throw new \Exception("Stock insuficiente para {$producto->nombre}.");
                }

                $subtotal = $producto->precio * $item['cantidad'];
                $total += $subtotal;

                $items[] = [
                    'producto'         => $producto,
                    'cantidad'         => $item['cantidad'],
                    'precio_unitario'  => $producto->precio,
                    'subtotal'         => $subtotal,
                ];
            }

            // Crear pedido
            $pedido = Pedido::create([
                'cliente_id'           => $request->user()->id,
                'total'                => $total,
                'metodo_entrega'       => $request->metodo_entrega,
                'estado'               => 'pendiente_validacion',
                'comprobante_pago_url' => $path,
                'punto_encuentro'      => $request->punto_encuentro,
                'pin_x'                => $request->pin_x,
                'pin_y'                => $request->pin_y,
            ]);

            // Crear detalles
            foreach ($items as $item) {
                $pedido->detalles()->create([
                    'producto_id'     => $item['producto']->id,
                    'cantidad'        => $item['cantidad'],
                    'precio_unitario' => $item['precio_unitario'],
                    'subtotal'        => $item['subtotal'],
                ]);
            }

            return $pedido;
        });

        return response()->json([
            'message' => 'Pedido creado correctamente.',
            'pedido'  => $pedido->load('detalles.producto'),
        ], 201);
    }

    public function show(Request $request, Pedido $pedido): JsonResponse
    {
        if ($pedido->cliente_id !== $request->user()->id) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        return response()->json($pedido->load(['detalles.producto', 'repartidor']));
    }

    public function complete(Request $request, Pedido $pedido): JsonResponse
    {
        $request->validate([
            'codigo_qr' => 'required|string',
        ]);

        if ($pedido->repartidor_id !== $request->user()->id) {
            return response()->json(['message' => 'No eres el repartidor asignado a este pedido.'], 403);
        }

        if (!in_array($pedido->estado, ['en_camino', 'listo_para_delivery'])) {
            return response()->json(['message' => 'Este pedido no puede completarse en su estado actual.'], 422);
        }

        if ($request->codigo_qr !== $pedido->codigo_qr_hash) {
            return response()->json(['message' => 'Código QR incorrecto.'], 422);
        }

        DB::transaction(function () use ($pedido, $request) {
            $pedido->update(['estado' => 'entregado']);

            // Acreditar incentivo al repartidor
            $incentivo = (float) \App\Models\Configuracion::get('incentivo_repartidor', 0.25);

            $request->user()->increment('balance', $incentivo);
        });

        return response()->json(['message' => 'Entrega confirmada. ¡Gracias!']);
    }

    public function disponibles(Request $request): JsonResponse
    {
        $pedidos = Pedido::with(['cliente', 'detalles.producto'])
            ->where('estado', 'listo_para_delivery')
            ->where('metodo_entrega', 'delivery')
            ->whereNull('repartidor_id')
            ->where('cliente_id', '!=', $request->user()->id)
            ->orderBy('created_at')
            ->get();

        return response()->json($pedidos);
    }

    public function misViajes(Request $request): JsonResponse
    {
        $pedidos = Pedido::with(['cliente', 'detalles.producto'])
            ->where('repartidor_id', $request->user()->id)
            ->whereIn('estado', ['en_camino', 'entregado'])
            ->orderBy('updated_at', 'desc')
            ->get();

        return response()->json($pedidos);
    }
    public function accept(Request $request, Pedido $pedido): JsonResponse
{
    if ($pedido->metodo_entrega !== 'delivery') {
        return response()->json(['message' => 'Este pedido es de retiro, no necesita repartidor.'], 422);
    }

    if ($pedido->estado !== 'listo_para_delivery') {
        return response()->json(['message' => 'Este pedido no está disponible para aceptar.'], 422);
    }

    if ($pedido->cliente_id === $request->user()->id) {
        return response()->json(['message' => 'No puedes aceptar tu propio pedido.'], 422);
    }

    $actualizado = DB::transaction(function () use ($pedido, $request) {
        $pedidoFresh = Pedido::where('id', $pedido->id)
            ->where('estado', 'listo_para_delivery')
            ->whereNull('repartidor_id')
            ->lockForUpdate()
            ->first();

        if (!$pedidoFresh) {
            return null;
        }

        $pedidoFresh->update([
            'repartidor_id' => $request->user()->id,
            'estado'        => 'en_camino',
        ]);

        return $pedidoFresh;
    });

    if (!$actualizado) {
        return response()->json(['message' => 'Este viaje ya fue aceptado por otro repartidor.'], 409);
    }

    return response()->json([
        'message' => 'Viaje aceptado correctamente.',
        'pedido'  => $actualizado->load(['cliente', 'detalles.producto']),
    ]);
}
}

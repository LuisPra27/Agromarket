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
}

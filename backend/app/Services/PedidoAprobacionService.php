<?php

namespace App\Services;

use App\Events\PedidoAprobado;
use App\Models\Pedido;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PedidoAprobacionService
{
    // Reduce el stock de cada producto del pedido y genera el código QR.
    // Lanza una excepción (sin tocar nada) si falta stock de algún producto.
    // Se usa tanto desde la aprobación manual en Filament (Caja/Validación)
    // como desde la confirmación automática de un pago con Payphone.
    public static function aprobar(Pedido $pedido): Pedido
    {
        DB::transaction(function () use ($pedido) {
            foreach ($pedido->detalles as $detalle) {
                $producto = $detalle->producto;
                if ($producto->stock < $detalle->cantidad) {
                    throw new \Exception("Stock insuficiente para {$producto->nombre}.");
                }
                $producto->decrement('stock', $detalle->cantidad);
            }

            $pedido->update([
                'estado' => 'preparando',
                'codigo_qr_hash' => (string) Str::uuid(),
            ]);
        });

        $pedido->refresh();

        event(new PedidoAprobado($pedido));

        ExpoPushService::enviar(
            [$pedido->cliente->expo_push_token],
            'Tu pedido está listo 🎉',
            'Pedido aprobado. Ya puedes ver tu código QR en la app.',
            ['tipo' => 'pedido_aprobado', 'pedido_id' => $pedido->id]
        );

        return $pedido;
    }
}

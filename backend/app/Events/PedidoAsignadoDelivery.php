<?php

namespace App\Events;

use App\Models\Pedido;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PedidoAsignadoDelivery implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Pedido $pedido)
    {
        $this->pedido = $pedido->load(['cliente', 'repartidor', 'detalles.producto']);
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('admin.pedidos'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'pedido.asignado_delivery';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->pedido->id,
            'repartidor_id' => $this->pedido->repartidor_id,
            'repartidor_nombre' => $this->pedido->repartidor?->nombre_completo,
            'estado' => $this->pedido->estado,
        ];
    }
}
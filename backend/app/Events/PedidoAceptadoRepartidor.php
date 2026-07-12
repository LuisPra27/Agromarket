<?php

namespace App\Events;

use App\Models\Pedido;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PedidoAceptadoRepartidor implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Pedido $pedido)
    {
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('admin.pedidos'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'pedido.aceptado_repartidor';
    }

    public function broadcastWith(): array
    {
        return [
            'pedido_id' => $this->pedido->id,
            'estado_nuevo' => 'en_camino',
            'repartidor_id' => $this->pedido->repartidor_id,
            'repartidor_nombre' => $this->pedido->repartidor?->nombre_completo,
        ];
    }
}
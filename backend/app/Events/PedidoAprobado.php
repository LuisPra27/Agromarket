<?php

namespace App\Events;

use App\Models\Pedido;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PedidoAprobado implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Pedido $pedido)
    {
        $this->pedido = $pedido->load(['cliente', 'detalles.producto']);
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('admin.pedidos'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'pedido.aprobado';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->pedido->id,
            'estado_anterior' => 'pendiente_validacion',
            'estado_nuevo' => 'preparando',
            'metodo_entrega' => $this->pedido->metodo_entrega,
            'total' => $this->pedido->total,
        ];
    }
}
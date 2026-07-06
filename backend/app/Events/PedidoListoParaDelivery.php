<?php

namespace App\Events;

use App\Models\Pedido;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PedidoListoParaDelivery implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Pedido $pedido)
    {
        $this->pedido = $pedido->load(['cliente', 'detalles.producto']);
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('repartidores'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'pedido.listo';
    }

    public function broadcastWith(): array
    {
        return [
            'id'              => $this->pedido->id,
            'total'           => $this->pedido->total,
            'punto_encuentro' => $this->pedido->punto_encuentro,
            'cliente'         => [
                'nombre_completo' => $this->pedido->cliente?->nombre_completo,
            ],
            'detalles' => $this->pedido->detalles->map(fn ($d) => [
                'cantidad' => $d->cantidad,
                'producto' => ['nombre' => $d->producto?->nombre],
            ]),
        ];
    }
}

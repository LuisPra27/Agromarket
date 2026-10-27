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
            // admin.pedidos alimenta los badges de navegacion de Filament.
            new Channel('admin.pedidos'),
            // repartidores es el canal que escuchan los moviles para refrescar
            // el Tablero en vivo. Estaba declarado en routes/channels.php con
            // su autorizacion, pero ningun evento emitia ahi: el Expo se
            // suscribia a un canal al que nunca llegaba nada.
            new Channel('repartidores'),
        ];
    }

    // OJO: el Expo escuchaba '.pedido.listo' y CLAUDE.md decia lo mismo, pero
    // el nombre real es este. NO se renombra: admin.pedidos ya lo consume en
    // Filament y cambiarlo rompe los badges del admin. El cliente Android
    // escucha el nombre real.
    public function broadcastAs(): string
    {
        return 'pedido.listo_para_delivery';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->pedido->id,
            'estado_anterior' => 'preparando',
            'estado_nuevo' => 'listo_para_delivery',
            'metodo_entrega' => $this->pedido->metodo_entrega,
            'total' => $this->pedido->total,
        ];
    }
}
<?php

namespace App\Events;

use App\Models\Usuario;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RepartidorAprobado implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Usuario $usuario)
    {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("usuario.{$this->usuario->id}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'repartidor.aprobado';
    }

    public function broadcastWith(): array
    {
        return [
            'usuario_id' => $this->usuario->id,
            'estado_repartidor' => $this->usuario->estado_repartidor,
            'message' => '¡Tu solicitud fue aprobada! Ya puedes trabajar como repartidor.',
        ];
    }
}
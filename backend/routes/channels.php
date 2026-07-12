<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\Usuario;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

// Canal para notificaciones de nuevos pedidos a repartidores
Broadcast::channel('repartidores', function (Usuario $usuario) {
    return $usuario->estado_repartidor === 'aprobado';
});

// Canal privado por usuario (aprobación/rechazo repartidor, etc.)
Broadcast::channel('usuario.{usuarioId}', function (Usuario $usuario, int $usuarioId) {
    return $usuario->id === $usuarioId;
});
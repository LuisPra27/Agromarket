<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'cedula', 'nombre_completo', 'correo', 'microsoft_id', 'clave', 'rol',
    'estado_repartidor', 'facultad', 'balance', 'expo_push_token',
])]
#[Hidden(['clave'])]
class Usuario extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'usuarios';
    protected $authPasswordName = 'clave';

    protected function casts(): array
    {
        return [
            'balance' => 'decimal:2',
            'clave' => 'hashed',
        ];
    }

    public function pedidosComoCliente(): HasMany
    {
        return $this->hasMany(Pedido::class, 'cliente_id');
    }

    public function pedidosComoRepartidor(): HasMany
    {
        return $this->hasMany(Pedido::class, 'repartidor_id');
    }

    public function liquidaciones(): HasMany
    {
        return $this->hasMany(Liquidacion::class);
    }
}

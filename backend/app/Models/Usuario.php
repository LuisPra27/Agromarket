<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['cedula', 'nombre_completo', 'correo', 'clave', 'rol', 'estado_repartidor', 'facultad', 'balance'])]
#[Hidden(['clave'])]
class Usuario extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'balance' => 'decimal:2',
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

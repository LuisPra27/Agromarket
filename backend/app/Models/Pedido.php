<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'cliente_id',
    'repartidor_id',
    'total',
    'metodo_entrega',
    'metodo_pago',
    'estado',
    'comprobante_pago_url',
    'payphone_client_transaction_id',
    'payphone_transaction_id',
    'payphone_pay_url',
    'codigo_qr_hash',
    'punto_encuentro',
    'pin_x',
    'pin_y',
    'motivo_cancelacion',
    'numero_orden_cliente',
])]
class Pedido extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'total' => 'decimal:2',
            'pin_x' => 'float',
            'pin_y' => 'float',
        ];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'cliente_id');
    }

    public function repartidor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'repartidor_id');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(DetallePedido::class);
    }
}

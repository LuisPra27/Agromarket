<?php

namespace Database\Factories;

use App\Models\DetallePedido;
use App\Models\Pedido;
use App\Models\Producto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DetallePedido>
 */
class DetallePedidoFactory extends Factory
{
    protected $model = DetallePedido::class;

    public function definition(): array
    {
        $cantidad = fake()->numberBetween(1, 3);
        $precio = fake()->randomFloat(2, 0.5, 10);

        return [
            'pedido_id' => Pedido::factory(),
            'producto_id' => Producto::factory(),
            'cantidad' => $cantidad,
            'precio_unitario' => $precio,
            'subtotal' => $precio * $cantidad,
        ];
    }
}

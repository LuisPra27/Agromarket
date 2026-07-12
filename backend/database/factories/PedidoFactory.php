<?php

namespace Database\Factories;

use App\Models\Pedido;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pedido>
 */
class PedidoFactory extends Factory
{
    protected $model = Pedido::class;

    public function definition(): array
    {
        return [
            'cliente_id' => Usuario::factory(),
            'repartidor_id' => null,
            'total' => 0,
            'metodo_entrega' => 'retiro',
            'estado' => 'pendiente_validacion',
            'comprobante_pago_url' => 'comprobantes/fake.jpg',
            'codigo_qr_hash' => null,
            'punto_encuentro' => null,
            'numero_orden_cliente' => 1,
        ];
    }

    public function delivery(): static
    {
        return $this->state(fn (array $attributes) => [
            'metodo_entrega' => 'delivery',
            'punto_encuentro' => 'Facultad de Ingeniería - Entrada principal',
        ]);
    }

    public function listoParaDelivery(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => 'listo_para_delivery',
            'codigo_qr_hash' => (string) \Illuminate\Support\Str::uuid(),
        ]);
    }

    public function enCamino(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => 'en_camino',
            'repartidor_id' => Usuario::factory()->repartidorAprobado(),
            'codigo_qr_hash' => (string) \Illuminate\Support\Str::uuid(),
        ]);
    }
}

<?php

namespace Database\Factories;

use App\Models\Categoria;
use App\Models\Producto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Producto>
 */
class ProductoFactory extends Factory
{
    protected $model = Producto::class;

    public function definition(): array
    {
        return [
            'categoria_id' => Categoria::factory(),
            'nombre' => fake()->unique()->words(2, true),
            'precio' => fake()->randomFloat(2, 0.5, 10),
            'stock' => 20,
            'imagen_url' => null,
        ];
    }

    public function sinStock(): static
    {
        return $this->state(fn (array $attributes) => ['stock' => 0]);
    }
}

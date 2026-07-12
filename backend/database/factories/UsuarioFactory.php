<?php

namespace Database\Factories;

use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<Usuario>
 */
class UsuarioFactory extends Factory
{
    protected $model = Usuario::class;

    public function definition(): array
    {
        return [
            'cedula' => fake()->unique()->numerify('##########'),
            'nombre_completo' => fake()->name(),
            'correo' => fake()->unique()->safeEmail(),
            'microsoft_id' => null,
            'clave' => Hash::make('password'),
            'rol' => 'cliente',
            'estado_repartidor' => 'no_postulado',
            'facultad' => null,
            'telefono' => null,
            'balance' => 0,
            'expo_push_token' => null,
        ];
    }

    // Usuario aprobado como repartidor, listo para aceptar viajes
    public function repartidorAprobado(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado_repartidor' => 'aprobado',
            'facultad' => 'Facultad Ingeniería, Industria y Construcción',
        ]);
    }

    // Usuario que entró por primera vez con Microsoft (sin cédula ni clave todavía)
    public function pendienteDeCompletarPerfil(): static
    {
        return $this->state(fn (array $attributes) => [
            'cedula' => null,
            'clave' => null,
            'microsoft_id' => fake()->uuid(),
        ]);
    }

    public function administrador(): static
    {
        return $this->state(fn (array $attributes) => [
            'rol' => 'administrador',
        ]);
    }
}

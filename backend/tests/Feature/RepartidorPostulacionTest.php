<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RepartidorPostulacionTest extends TestCase
{
    use RefreshDatabase;

    public function test_postular_con_facultad_valida_funciona(): void
    {
        $usuario = Usuario::factory()->create(['estado_repartidor' => 'no_postulado']);
        Sanctum::actingAs($usuario, ['*'], 'usuario');

        $response = $this->postJson('/api/auth/postular-repartidor', [
            'facultad' => 'Facultad Ciencias de la Salud',
        ]);

        $response->assertOk();
        $this->assertSame('pendiente', $usuario->fresh()->estado_repartidor);
        $this->assertSame('Facultad Ciencias de la Salud', $usuario->fresh()->facultad);
    }

    public function test_postular_con_texto_libre_no_permitido_falla(): void
    {
        $usuario = Usuario::factory()->create(['estado_repartidor' => 'no_postulado']);
        Sanctum::actingAs($usuario, ['*'], 'usuario');

        $response = $this->postJson('/api/auth/postular-repartidor', [
            'facultad' => 'Una facultad inventada que no existe',
        ]);

        $response->assertStatus(422);
        $this->assertSame('no_postulado', $usuario->fresh()->estado_repartidor);
    }

    public function test_no_puede_postular_si_ya_esta_aprobado(): void
    {
        $usuario = Usuario::factory()->repartidorAprobado()->create();
        Sanctum::actingAs($usuario, ['*'], 'usuario');

        $response = $this->postJson('/api/auth/postular-repartidor', [
            'facultad' => 'Facultad Ciencias de la Salud',
        ]);

        $response->assertStatus(422);
    }

    public function test_puede_volver_a_postular_si_fue_rechazado(): void
    {
        $usuario = Usuario::factory()->create(['estado_repartidor' => 'rechazado']);
        Sanctum::actingAs($usuario, ['*'], 'usuario');

        $response = $this->postJson('/api/auth/postular-repartidor', [
            'facultad' => 'Facultad Ciencias de la Vida y Tecnologías',
        ]);

        $response->assertOk();
        $this->assertSame('pendiente', $usuario->fresh()->estado_repartidor);
    }
}

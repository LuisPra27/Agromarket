<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthMicrosoftTest extends TestCase
{
    use RefreshDatabase;

    public function test_primer_login_con_microsoft_crea_usuario_sin_cedula(): void
    {
        Http::fake([
            'graph.microsoft.com/*' => Http::response([
                'id' => 'ms-id-123',
                'mail' => 'estudiante@live.uleam.edu.ec',
                'displayName' => 'Estudiante Prueba',
            ], 200),
        ]);

        $response = $this->postJson('/api/auth/microsoft', [
            'access_token' => 'token-falso-valido',
        ]);

        $response->assertOk();
        $response->assertJsonPath('usuario.correo', 'estudiante@live.uleam.edu.ec');
        $response->assertJsonPath('usuario.cedula', null);

        $this->assertDatabaseHas('usuarios', [
            'correo' => 'estudiante@live.uleam.edu.ec',
            'microsoft_id' => 'ms-id-123',
            'cedula' => null,
        ]);
    }

    public function test_segundo_login_con_mismo_microsoft_id_no_duplica_usuario(): void
    {
        Http::fake([
            'graph.microsoft.com/*' => Http::response([
                'id' => 'ms-id-123',
                'mail' => 'estudiante@live.uleam.edu.ec',
                'displayName' => 'Estudiante Prueba',
            ], 200),
        ]);

        $this->postJson('/api/auth/microsoft', ['access_token' => 'token-1'])->assertOk();
        $this->postJson('/api/auth/microsoft', ['access_token' => 'token-2'])->assertOk();

        $this->assertSame(1, Usuario::where('microsoft_id', 'ms-id-123')->count());
    }

    public function test_token_invalido_de_microsoft_es_rechazado(): void
    {
        Http::fake([
            'graph.microsoft.com/*' => Http::response(['error' => 'invalid token'], 401),
        ]);

        $response = $this->postJson('/api/auth/microsoft', [
            'access_token' => 'token-invalido',
        ]);

        $response->assertStatus(401);
        $this->assertDatabaseCount('usuarios', 0);
    }

    public function test_completar_perfil_guarda_cedula_una_sola_vez(): void
    {
        $usuario = Usuario::factory()->pendienteDeCompletarPerfil()->create();
        Sanctum::actingAs($usuario, ['*'], 'usuario');

        $response = $this->postJson('/api/auth/completar-perfil', [
            'cedula' => '1234567890',
        ]);

        $response->assertOk();
        $this->assertSame('1234567890', $usuario->fresh()->cedula);
    }

    public function test_completar_perfil_rechaza_cedula_duplicada(): void
    {
        Usuario::factory()->create(['cedula' => '1234567890']);
        $usuario = Usuario::factory()->pendienteDeCompletarPerfil()->create();
        Sanctum::actingAs($usuario, ['*'], 'usuario');

        $response = $this->postJson('/api/auth/completar-perfil', [
            'cedula' => '1234567890',
        ]);

        $response->assertStatus(422);
    }
}

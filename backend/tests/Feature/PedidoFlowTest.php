<?php

namespace Tests\Feature;

use App\Filament\Resources\PedidoResource\Pages\ListPedidos;
use App\Models\Configuracion;
use App\Models\DetallePedido;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\User;
use App\Models\Usuario;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PedidoFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        // Los tests que usan Livewire::test() sobre un Resource de Filament
        // (ej. callTableAction en ListPedidos) necesitan que el panel "admin"
        // esté activo, si no Filament no resuelve el contexto correctamente.
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    /** El cliente crea un pedido: no debe tocar el stock todavía. */
    public function test_crear_pedido_no_reduce_stock_hasta_aprobar(): void
    {
        $cliente = Usuario::factory()->create();
        $producto = Producto::factory()->create(['stock' => 10, 'precio' => 2.00]);

        Sanctum::actingAs($cliente, ['*'], 'usuario');

        $response = $this->postJson('/api/pedidos', [
            'metodo_entrega' => 'retiro',
            'items' => [
                ['producto_id' => $producto->id, 'cantidad' => 3],
            ],
            'comprobante' => UploadedFile::fake()->image('comprobante.jpg'),
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('pedido.estado', 'pendiente_validacion');

        $this->assertSame(10, $producto->fresh()->stock);
        $this->assertSame(6.0, (float) Pedido::first()->total);
    }

    /** No se puede crear un pedido con más cantidad de la que hay en stock. */
    public function test_crear_pedido_falla_si_no_hay_stock_suficiente(): void
    {
        $cliente = Usuario::factory()->create();
        $producto = Producto::factory()->create(['stock' => 2]);

        Sanctum::actingAs($cliente, ['*'], 'usuario');

        $response = $this->postJson('/api/pedidos', [
            'metodo_entrega' => 'retiro',
            'items' => [
                ['producto_id' => $producto->id, 'cantidad' => 5],
            ],
            'comprobante' => UploadedFile::fake()->image('comprobante.jpg'),
        ]);

        $response->assertStatus(500);
        $this->assertDatabaseCount('pedidos', 0);
    }

    /** El costo de delivery se suma al total solo cuando aplica. */
    public function test_costo_delivery_se_suma_solo_si_metodo_es_delivery(): void
    {
        Configuracion::create(['clave' => 'costo_delivery', 'valor' => '1.50']);
        $cliente = Usuario::factory()->create();
        $producto = Producto::factory()->create(['stock' => 10, 'precio' => 2.00]);

        Sanctum::actingAs($cliente, ['*'], 'usuario');

        $response = $this->postJson('/api/pedidos', [
            'metodo_entrega' => 'delivery',
            'items' => [['producto_id' => $producto->id, 'cantidad' => 1]],
            'comprobante' => UploadedFile::fake()->image('comprobante.jpg'),
            'punto_encuentro' => 'Facultad de Ingeniería',
        ]);

        $response->assertStatus(201);
        $this->assertSame(3.5, (float) Pedido::first()->total); // 2.00 + 1.50
    }

    /** Al aprobar en Caja/Validación: reduce stock, genera QR y cambia estado. */
    public function test_aprobar_pedido_reduce_stock_y_genera_qr(): void
    {
        $admin = User::factory()->create();
        $producto = Producto::factory()->create(['stock' => 10]);
        $pedido = Pedido::factory()->create(['estado' => 'pendiente_validacion']);
        DetallePedido::factory()->create([
            'pedido_id' => $pedido->id,
            'producto_id' => $producto->id,
            'cantidad' => 4,
        ]);

        $this->actingAs($admin);

        Livewire::test(ListPedidos::class)
            ->callTableAction('aprobar', $pedido);

        $pedido->refresh();
        $this->assertSame('preparando', $pedido->estado);
        $this->assertNotNull($pedido->codigo_qr_hash);
        $this->assertSame(6, $producto->fresh()->stock);
    }

    /** No se puede aprobar un pedido si ya no hay stock suficiente al momento de validar. */
    public function test_aprobar_pedido_falla_si_stock_bajo_entre_creacion_y_aprobacion(): void
    {
        $admin = User::factory()->create();
        $producto = Producto::factory()->create(['stock' => 2]);
        $pedido = Pedido::factory()->create(['estado' => 'pendiente_validacion']);
        DetallePedido::factory()->create([
            'pedido_id' => $pedido->id,
            'producto_id' => $producto->id,
            'cantidad' => 5,
        ]);

        $this->actingAs($admin);

        try {
            Livewire::test(ListPedidos::class)->callTableAction('aprobar', $pedido);
        } catch (\Throwable $e) {
            // La acción lanza excepción a propósito; nos interesa el estado final.
        }

        $pedido->refresh();
        $this->assertSame('pendiente_validacion', $pedido->estado, 'El pedido no debió aprobarse sin stock.');
        $this->assertSame(2, $producto->fresh()->stock, 'El stock no debió tocarse si falló la aprobación.');
    }

    /** Un repartidor acepta un viaje listo para delivery. */
    public function test_repartidor_acepta_viaje_disponible(): void
    {
        $repartidor = Usuario::factory()->repartidorAprobado()->create();
        $pedido = Pedido::factory()->delivery()->listoParaDelivery()->create();

        Sanctum::actingAs($repartidor, ['*'], 'usuario');

        $response = $this->postJson("/api/repartidor/{$pedido->id}/accept");

        $response->assertOk();
        $pedido->refresh();
        $this->assertSame('en_camino', $pedido->estado);
        $this->assertSame($repartidor->id, $pedido->repartidor_id);
    }

    /** Dos repartidores no pueden aceptar el mismo viaje (condición de carrera). */
    public function test_dos_repartidores_no_pueden_aceptar_el_mismo_viaje(): void
    {
        $repartidor1 = Usuario::factory()->repartidorAprobado()->create();
        $repartidor2 = Usuario::factory()->repartidorAprobado()->create();
        $pedido = Pedido::factory()->delivery()->listoParaDelivery()->create();

        Sanctum::actingAs($repartidor1, ['*'], 'usuario');
        $this->postJson("/api/repartidor/{$pedido->id}/accept")->assertOk();

        Sanctum::actingAs($repartidor2, ['*'], 'usuario');
        $response = $this->postJson("/api/repartidor/{$pedido->id}/accept");

        $response->assertStatus(422);
        $this->assertSame($repartidor1->id, $pedido->fresh()->repartidor_id);
    }

    /** Un repartidor no puede aceptar un segundo viaje mientras tiene uno activo. */
    public function test_repartidor_no_puede_aceptar_dos_viajes_a_la_vez(): void
    {
        $repartidor = Usuario::factory()->repartidorAprobado()->create();
        Pedido::factory()->delivery()->enCamino()->create(['repartidor_id' => $repartidor->id]);
        $otroPedido = Pedido::factory()->delivery()->listoParaDelivery()->create();

        Sanctum::actingAs($repartidor, ['*'], 'usuario');

        $response = $this->postJson("/api/repartidor/{$otroPedido->id}/accept");

        $response->assertStatus(422);
        $this->assertNull($otroPedido->fresh()->repartidor_id);
    }

    /** Escanear el QR correcto entrega el pedido y acredita el incentivo al repartidor. */
    public function test_completar_entrega_con_qr_correcto_acredita_balance(): void
    {
        Configuracion::create(['clave' => 'incentivo_repartidor', 'valor' => '0.25']);

        $repartidor = Usuario::factory()->repartidorAprobado()->create(['balance' => 0]);
        $pedido = Pedido::factory()->delivery()->create([
            'estado' => 'en_camino',
            'repartidor_id' => $repartidor->id,
            'codigo_qr_hash' => 'abc-123',
        ]);

        Sanctum::actingAs($repartidor, ['*'], 'usuario');

        $response = $this->postJson("/api/repartidor/{$pedido->id}/complete", [
            'codigo_qr' => 'abc-123',
        ]);

        $response->assertOk();
        $this->assertSame('entregado', $pedido->fresh()->estado);
        $this->assertSame(0.25, (float) $repartidor->fresh()->balance);
    }

    /** Un código QR incorrecto no debe completar la entrega ni acreditar nada. */
    public function test_completar_entrega_con_qr_incorrecto_falla(): void
    {
        $repartidor = Usuario::factory()->repartidorAprobado()->create(['balance' => 0]);
        $pedido = Pedido::factory()->delivery()->create([
            'estado' => 'en_camino',
            'repartidor_id' => $repartidor->id,
            'codigo_qr_hash' => 'codigo-correcto',
        ]);

        Sanctum::actingAs($repartidor, ['*'], 'usuario');

        $response = $this->postJson("/api/repartidor/{$pedido->id}/complete", [
            'codigo_qr' => 'codigo-incorrecto',
        ]);

        $response->assertStatus(422);
        $this->assertSame('en_camino', $pedido->fresh()->estado);
        $this->assertSame(0.0, (float) $repartidor->fresh()->balance);
    }

    /** Un repartidor no puede completar la entrega de un pedido que no es suyo. */
    public function test_repartidor_no_puede_completar_pedido_ajeno(): void
    {
        $repartidorAsignado = Usuario::factory()->repartidorAprobado()->create();
        $otroRepartidor = Usuario::factory()->repartidorAprobado()->create();
        $pedido = Pedido::factory()->delivery()->create([
            'estado' => 'en_camino',
            'repartidor_id' => $repartidorAsignado->id,
            'codigo_qr_hash' => 'abc-123',
        ]);

        Sanctum::actingAs($otroRepartidor, ['*'], 'usuario');

        $response = $this->postJson("/api/repartidor/{$pedido->id}/complete", [
            'codigo_qr' => 'abc-123',
        ]);

        $response->assertStatus(403);
    }
}

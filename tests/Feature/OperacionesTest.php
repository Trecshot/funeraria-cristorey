<?php

namespace Tests\Feature;

use App\Models\ProductoBodega;
use App\Models\Servicio;
use App\Models\Traslado;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class OperacionesTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_requires_login_and_is_available_to_authenticated_roles(): void
    {
        $this->get('/dashboard')->assertRedirect(route('login'));

        $this->actingAs(User::factory()->create(['role' => 'asistente']))
            ->get('/dashboard')
            ->assertOk()
            ->assertViewIs('dashboard.index');
    }

    public function test_chofer_can_see_only_assigned_route_and_cannot_access_finances(): void
    {
        $chofer = User::factory()->create(['role' => 'chofer']);
        $servicio = Servicio::create([
            'created_by' => User::factory()->create(['role' => 'asistente'])->id,
            'folio' => 'FC-TEST-1',
            'cliente_nombre' => 'Cliente',
            'difunto_nombre' => 'Servicio asignado',
            'convenio' => 'particular',
            'monto_servicio' => 100000,
            'aporte_convenio' => 0,
            'copago' => 100000,
        ]);
        Traslado::create([
            'servicio_id' => $servicio->id,
            'chofer_id' => $chofer->id,
            'cementerio' => 'Cementerio',
            'salida_at' => now()->addHour(),
            'estado' => 'programado',
            'implementos' => ['Cruz', 'Tarjetas'],
        ]);

        $this->actingAs($chofer)
            ->get('/movil/chofer')
            ->assertOk()
            ->assertSee('Servicio asignado')
            ->assertDontSee('Finanzas');

        $this->get('/')->assertRedirect(route('movil.chofer'));
        $this->get('/dashboard')->assertForbidden();
        $this->get('/login')->assertRedirect(route('movil.chofer'));
        $this->get('/servicios')->assertForbidden();
        $this->get('/finanzas')->assertForbidden();
    }

    public function test_service_is_saved_with_an_automatic_calendar_transfer(): void
    {
        $assistant = User::factory()->create(['role' => 'asistente']);

        $this->actingAs($assistant)
            ->post('/servicios', [
                'cliente_nombre' => 'Cliente de prueba',
                'difunto_nombre' => 'Difunto de prueba',
                'convenio' => 'ips',
                'monto_servicio' => 1500000,
                'cortejo_at' => now()->addDay()->format('Y-m-d\TH:i'),
                'destino' => 'Cementerio de prueba',
                'estado' => 'abierto',
            ])
            ->assertRedirect();

        $servicio = Servicio::firstOrFail();
        $this->assertSame('Cliente de prueba', $servicio->cliente_contratante);
        $this->assertSame(430000.0, $servicio->copagoEstimado());
        $this->assertDatabaseHas('traslados', [
            'servicio_id' => $servicio->id,
            'cementerio' => 'Cementerio de prueba',
        ]);
    }

    public function test_bodega_transfer_updates_stock_and_records_audit_movement(): void
    {
        $user = User::factory()->create(['role' => 'bodega']);
        $producto = ProductoBodega::create([
            'modelo' => 'Marquetina',
            'color' => 'Natural',
            'tallado' => 'Liso',
            'stock_salon' => 5,
            'stock_taller' => 0,
            'stock_minimo' => 1,
        ]);

        $this->actingAs($user)
            ->post('/bodega/traspaso', [
                'producto_id' => $producto->id,
                'origen' => 'salon',
                'destino' => 'taller',
                'cantidad' => 2,
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('productos_bodega', [
            'id' => $producto->id,
            'stock_salon' => 3,
            'stock_taller' => 2,
        ]);
        $this->assertDatabaseHas('movimientos_bodega', [
            'producto_bodega_id' => $producto->id,
            'cantidad' => 2,
        ]);
    }

    public function test_bodega_rejects_a_transfer_that_exceeds_available_stock(): void
    {
        $user = User::factory()->create(['role' => 'bodega']);
        $producto = ProductoBodega::create([
            'modelo' => 'Lisa',
            'stock_salon' => 1,
            'stock_taller' => 0,
            'stock_minimo' => 0,
        ]);

        $this->actingAs($user)
            ->from('/bodega')
            ->post('/bodega/traspaso', [
                'producto_id' => $producto->id,
                'origen' => 'salon',
                'destino' => 'taller',
                'cantidad' => 2,
            ])
            ->assertRedirect('/bodega')
            ->assertSessionHasErrors('cantidad');

        $this->assertDatabaseHas('productos_bodega', ['id' => $producto->id, 'stock_salon' => 1]);
        $this->assertDatabaseCount('movimientos_bodega', 0);
    }

    public function test_document_upload_requires_private_s3_configuration_before_creating_service(): void
    {
        config([
            'filesystems.disks.s3.bucket' => null,
            'filesystems.disks.s3.key' => null,
            'filesystems.disks.s3.secret' => null,
        ]);

        $this->actingAs(User::factory()->create(['role' => 'asistente']))
            ->post('/servicios', [
                'cliente_nombre' => 'Cliente',
                'difunto_nombre' => 'Respaldo de prueba',
                'convenio' => 'particular',
                'monto_servicio' => 0,
                'estado' => 'abierto',
                'tipos_documentos' => ['certificado_defuncion'],
                'documentos' => [UploadedFile::fake()->image('certificado.png')],
            ])
            ->assertStatus(503);

        $this->assertDatabaseCount('servicios', 0);
    }

    public function test_public_catalog_never_shows_internal_prices(): void
    {
        ProductoBodega::create([
            'modelo' => 'Paloma-biblia',
            'color' => 'Caoba',
            'stock_salon' => 1,
            'stock_taller' => 0,
            'stock_minimo' => 0,
            'publicado' => true,
        ]);

        $this->get('/catalogo')
            ->assertOk()
            ->assertSee('Paloma-biblia')
            ->assertDontSee('total_servicio');
    }
}

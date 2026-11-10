<?php

namespace Tests\Feature;

use App\Models\Chofer;
use App\Models\ProductoBodega;
use App\Models\Servicio;
use App\Models\Traslado;
use App\Models\User;
use App\Models\Vehiculo;
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

        $this->get('/dashboard')->assertForbidden();
        $this->get('/login')->assertRedirect(route('movil.chofer'));
        $this->get('/servicios')->assertForbidden();
        $this->get('/finanzas')->assertForbidden();
        $this->get('/')->assertRedirect(route('login'));
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
            'codigo' => 'URN-TEST-01',
            'modelo' => 'Marquetina',
            'color' => 'Natural',
            'tallado' => 'Liso',
            'categoria_etaria' => 'Adulto',
            'medidas_cm' => '1.90m x 65cm',
            'precio' => 450000,
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

        $this->assertDatabaseHas('urnas', [
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
            'codigo' => 'URN-TEST-02',
            'modelo' => 'Lisa',
            'color' => 'Caoba',
            'categoria_etaria' => 'Adulto',
            'medidas_cm' => '1.85m x 60cm',
            'precio' => 380000,
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

        $this->assertDatabaseHas('urnas', ['id' => $producto->id, 'stock_salon' => 1]);
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

    public function test_urna_can_be_registered_with_dimensions_category_and_price(): void
    {
        $user = User::factory()->create(['role' => 'bodega']);

        $this->actingAs($user)
            ->post('/bodega/productos', [
                'codigo' => 'URN-AD-99',
                'modelo' => 'Marquetina Imperial Especial',
                'color' => 'Caoba',
                'tallado' => 'Cruz',
                'categoria_etaria' => 'Adulto',
                'medidas_cm' => '1.90m x 65cm',
                'personalizada' => 1,
                'precio' => 450000,
                'stock_salon' => 2,
                'stock_taller' => 4,
                'stock_minimo' => 2,
            ])
            ->assertRedirect(route('bodega.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('urnas', [
            'codigo' => 'URN-AD-99',
            'modelo' => 'Marquetina Imperial Especial',
            'categoria_etaria' => 'Adulto',
            'medidas_cm' => '1.90m x 65cm',
            'personalizada' => 1,
            'precio' => 450000,
            'stock_salon' => 2,
            'stock_taller' => 4,
        ]);
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

    public function test_admin_can_view_and_register_rbac_users(): void
    {
        $admin = User::factory()->create(['role' => 'administrador']);

        $this->actingAs($admin)
            ->get('/usuarios')
            ->assertOk()
            ->assertViewIs('usuarios.index')
            ->assertSee('Usuarios Registrados y Control de Roles (RBAC)');

        $this->actingAs($admin)
            ->post('/usuarios', [
                'name' => 'Carlos Muñoz',
                'email' => 'carlos.munoz@example.test',
                'role' => 'chofer',
                'password' => 'clave-segura-1234',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', [
            'email' => 'carlos.munoz@example.test',
            'role' => 'chofer',
        ]);
    }

    public function test_flota_updates_vehicle_state_and_prevents_schedule_overlap(): void
    {
        $admin = User::factory()->create(['role' => 'administrador']);
        $choferUser = User::factory()->create(['role' => 'chofer', 'name' => 'Carlos Soto']);

        $vehiculo = Vehiculo::create([
            'patente' => 'KH-TY-18',
            'modelo_uso' => 'Mercedes Benz Carroza Ceremonial',
            'apto_rural' => false,
            'licencia_requerida' => 'A2',
            'estado' => 'Mantenimiento',
            'kilometraje' => 42000,
        ]);

        Chofer::create([
            'user_id' => $choferUser->id,
            'nombre_completo' => 'Carlos Soto',
            'rut' => '14.582.391-4',
            'licencia_clase' => 'A2',
            'estado' => 'Disponible',
            'vehiculo_asignado_id' => $vehiculo->id,
        ]);

        // Actualizar estado del vehículo mediante petición JSON (Finalizar Mantención)
        $this->actingAs($admin)
            ->patchJson(route('flota.vehiculos.estado', $vehiculo->id), [
                'estado' => 'Disponible',
            ])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'nuevo_estado' => 'Disponible',
            ]);

        $this->assertDatabaseHas('vehiculos', [
            'id' => $vehiculo->id,
            'estado' => 'Disponible',
        ]);

        // Crear dos traslados en el mismo bloque horario (±1 hora) y verificar prevención de solapamiento
        $horaSalida = now()->addDay()->setHour(10)->setMinute(0);
        $servicio1 = Servicio::create([
            'created_by' => $admin->id,
            'folio' => 'FC-SOL-1',
            'cliente_nombre' => 'Cliente 1',
            'difunto_nombre' => 'Difunto 1',
            'convenio' => 'particular',
            'monto_servicio' => 1000000,
            'aporte_convenio' => 0,
            'copago' => 1000000,
        ]);
        $servicio2 = Servicio::create([
            'created_by' => $admin->id,
            'folio' => 'FC-SOL-2',
            'cliente_nombre' => 'Cliente 2',
            'difunto_nombre' => 'Difunto 2',
            'convenio' => 'particular',
            'monto_servicio' => 1000000,
            'aporte_convenio' => 0,
            'copago' => 1000000,
        ]);

        Traslado::create([
            'servicio_id' => $servicio1->id,
            'vehiculo_id' => $vehiculo->id,
            'chofer_id' => $choferUser->id,
            'cementerio' => 'Cementerio General',
            'salida_at' => $horaSalida,
            'estado' => 'programado',
        ]);

        $trasladoSolapado = Traslado::create([
            'servicio_id' => $servicio2->id,
            'cementerio' => 'Parque del Sendero',
            'salida_at' => $horaSalida->copy()->addHour(),
            'estado' => 'programado',
        ]);

        $this->actingAs($admin)
            ->from(route('flota.calendario'))
            ->patch(route('flota.traslados.asignar', $trasladoSolapado), [
                'vehiculo_id' => $vehiculo->id,
                'chofer_id' => $choferUser->id,
            ])
            ->assertRedirect(route('flota.calendario'))
            ->assertSessionHasErrors('vehiculo_id');
    }
}

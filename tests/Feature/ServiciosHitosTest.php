<?php

namespace Tests\Feature;

use App\Models\HistorialServicio;
use App\Models\Servicio;
use App\Models\Urna;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiciosHitosTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'administrador',
        ]);
    }

    public function test_servicio_initializes_apertura_hito_automatically(): void
    {
        $servicio = Servicio::create([
            'folio' => 'FC-TEST-STEPPER',
            'created_by' => $this->admin->id,
            'cliente_nombre' => 'María Gonzalez',
            'difunto_nombre' => 'José Gonzalez',
            'convenio' => 'particular',
            'monto_servicio' => 1200000,
            'estado' => 'abierto',
        ]);

        $pasos = $servicio->sincronizarHitosOperativos();

        $this->assertCount(5, $pasos);
        $this->assertEquals('apertura', $pasos[0]['clave']);
        $this->assertTrue($pasos[0]['completado']);
        $this->assertDatabaseHas('historial_servicios', [
            'servicio_id' => $servicio->id,
            'hito' => HistorialServicio::HITO_APERTURA,
        ]);
    }

    public function test_assigning_urna_completes_logistica_urna_hito(): void
    {
        $urna = Urna::create([
            'codigo' => 'UR-TEST-01',
            'modelo' => 'Marquetina Raulí',
            'color' => 'Nogal',
            'categoria_etaria' => 'Adulto',
            'stock_salon' => 3,
            'stock_taller' => 2,
        ]);

        $servicio = Servicio::create([
            'folio' => 'FC-TEST-URNA',
            'created_by' => $this->admin->id,
            'cliente_nombre' => 'Carlos Perez',
            'difunto_nombre' => 'Marta Perez',
            'convenio' => 'ips',
            'monto_servicio' => 1500000,
            'estado' => 'abierto',
        ]);

        // Antes de asignar urna, paso 2 debe estar activo / no completado
        $pasosAntes = $servicio->sincronizarHitosOperativos();
        $this->assertFalse($pasosAntes[1]['completado']);

        // Asignamos urna mediante el endpoint
        $response = $this->actingAs($this->admin)
            ->patch(route('servicios.urna.asignar', $servicio), [
                'urna_id' => $urna->id,
            ]);

        $response->assertRedirect(route('servicios.show', $servicio));
        $this->assertDatabaseHas('servicios', [
            'id' => $servicio->id,
            'urna_id' => $urna->id,
        ]);

        $servicio->refresh();
        $pasosDespues = $servicio->sincronizarHitosOperativos();
        $this->assertTrue($pasosDespues[1]['completado']);
        $this->assertStringContainsString('Marquetina Raulí', $pasosDespues[1]['detalle']);
    }

    public function test_servicios_show_renders_horizontal_stepper_with_all_milestones(): void
    {
        $servicio = Servicio::create([
            'folio' => 'FC-TEST-SHOW',
            'created_by' => $this->admin->id,
            'cliente_nombre' => 'Familia Tapia',
            'difunto_nombre' => 'Esteban Tapia',
            'convenio' => 'afp',
            'monto_servicio' => 1600000,
            'estado' => 'abierto',
        ]);

        $response = $this->actingAs($this->admin)->get(route('servicios.show', $servicio));

        $response->assertOk();
        $response->assertViewIs('servicios.show');
        $response->assertSee('Flujo Operativo y Trazabilidad en Tiempo Real');
        $response->assertSee('Apertura');
        $response->assertSee('Logística / Urna');
        $response->assertSee('Legal y Documental');
        $response->assertSee('Cortejo y Flota');
        $response->assertSee('Finalizado');
        $response->assertSee('Historial Cronológico de Auditoría');
    }

    public function test_calculo_porcentaje_avance_reflects_milestones(): void
    {
        $servicio = Servicio::create([
            'folio' => 'FC-TEST-PROGRESS',
            'created_by' => $this->admin->id,
            'cliente_nombre' => 'Familia Rojas',
            'difunto_nombre' => 'Rosa Rojas',
            'convenio' => 'particular',
            'monto_servicio' => 1000000,
            'estado' => 'abierto',
        ]);

        // Solo apertura completada -> 1 de 5 = 20%
        $this->assertEquals(20, $servicio->calcularPorcentajeAvance());

        // Con urna asignada -> 2 de 5 = 40%
        $urna = Urna::create([
            'codigo' => 'UR-TEST-02',
            'modelo' => 'Tradicional Pino',
            'color' => 'Caoba',
            'categoria_etaria' => 'Adulto',
            'stock_salon' => 1,
            'stock_taller' => 0,
        ]);
        $servicio->urna_id = $urna->id;
        $servicio->save();

        $this->assertEquals(40, $servicio->calcularPorcentajeAvance());
    }
}

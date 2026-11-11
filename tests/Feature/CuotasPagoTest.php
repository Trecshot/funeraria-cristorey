<?php

namespace Tests\Feature;

use App\Models\CuotaPago;
use App\Models\HistorialServicio;
use App\Models\Liquidacion;
use App\Models\Servicio;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class CuotasPagoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['funeraria.uf_value_clp' => 38000]);
    }

    public function test_liquidacion_generates_plan_de_cuotas_and_detects_morosidad(): void
    {
        $admin = User::factory()->create(['role' => 'administrador']);

        $servicio = Servicio::create([
            'created_by' => $admin->id,
            'folio' => 'FC-PEDRO-MORALES',
            'cliente_nombre' => 'Familia Morales',
            'difunto_nombre' => 'Pedro Morales',
            'convenio' => 'ips',
            'monto_servicio' => 1450000,
            'estado' => 'abierto',
        ]);

        $servicio->calcularAportes();
        $servicio->save();

        $liquidacion = $servicio->obtenerOCrearLiquidacion();
        $this->assertSame(1450000, $liquidacion->monto_total);
        $this->assertSame(1070000, $liquidacion->monto_subvencion);
        $this->assertSame(380000, $liquidacion->monto_copago_familia);

        // Generar 2 cuotas de $190.000: Cuota 1 vencida hace 5 días, Cuota 2 en 25 días
        $liquidacion->cuotas()->delete();
        $cuota1 = $liquidacion->cuotas()->create([
            'numero_cuota' => 1,
            'monto_cuota' => 190000,
            'fecha_vencimiento' => Carbon::today()->subDays(5),
            'estado' => CuotaPago::ESTADO_PENDIENTE,
        ]);

        $cuota2 = $liquidacion->cuotas()->create([
            'numero_cuota' => 2,
            'monto_cuota' => 190000,
            'fecha_vencimiento' => Carbon::today()->addDays(25),
            'estado' => CuotaPago::ESTADO_PENDIENTE,
        ]);

        $this->assertTrue($cuota1->estaAtrasada());
        $this->assertFalse($cuota2->estaAtrasada());

        $liquidacion->recalcularEstados();
        $liquidacion->refresh();

        $this->assertSame(Liquidacion::ESTADO_GENERAL_MOROSO, $liquidacion->estado_general);
        $this->assertStringContainsString('Moroso', $liquidacion->estadoGeneralLabel());
        $this->assertStringContainsString('1 cuota atrasada', $liquidacion->resumenMorosidad());
    }

    public function test_artisan_command_verificar_cuotas_atrasadas_processes_and_logs_audit(): void
    {
        $admin = User::factory()->create(['role' => 'administrador']);

        $servicio = Servicio::create([
            'created_by' => $admin->id,
            'folio' => 'FC-AUDIT-DEUDA',
            'cliente_nombre' => 'Contratante',
            'difunto_nombre' => 'Difunto',
            'convenio' => 'particular',
            'monto_servicio' => 500000,
            'estado' => 'abierto',
        ]);

        $liquidacion = $servicio->obtenerOCrearLiquidacion();
        $liquidacion->cuotas()->delete();

        $cuota = $liquidacion->cuotas()->create([
            'numero_cuota' => 1,
            'monto_cuota' => 500000,
            'fecha_vencimiento' => Carbon::today()->subDay(),
            'estado' => CuotaPago::ESTADO_PENDIENTE,
        ]);

        $exitCode = Artisan::call('cuotas:verificar-atrasos');
        $this->assertSame(0, $exitCode);

        $cuota->refresh();
        $this->assertSame(CuotaPago::ESTADO_ATRASADA, $cuota->estado);

        $liquidacion->refresh();
        $this->assertSame(Liquidacion::ESTADO_GENERAL_MOROSO, $liquidacion->estado_general);

        $this->assertDatabaseHas('historial_servicios', [
            'servicio_id' => $servicio->id,
            'hito' => HistorialServicio::HITO_FINALIZADO,
            'titulo' => 'Alerta Financiera de Morosidad',
        ]);
    }

    public function test_paying_cuotas_updates_balance_and_achieves_pagado_total(): void
    {
        $admin = User::factory()->create(['role' => 'administrador']);

        $servicio = Servicio::create([
            'created_by' => $admin->id,
            'folio' => 'FC-PAY-FLOW',
            'cliente_nombre' => 'María Elena Sepúlveda',
            'difunto_nombre' => 'Jorge Alberto Sepúlveda',
            'convenio' => 'afp',
            'monto_servicio' => 1680000,
            'estado' => 'abierto',
        ]);

        $servicio->calcularAportes(38000);
        $servicio->save();

        $liquidacion = $servicio->obtenerOCrearLiquidacion(38000);
        $liquidacion->cuotas()->delete();

        $cuota1 = $liquidacion->cuotas()->create([
            'numero_cuota' => 1,
            'monto_cuota' => 555000,
            'fecha_vencimiento' => Carbon::today()->subDays(2),
            'estado' => CuotaPago::ESTADO_ATRASADA,
        ]);

        $cuota2 = $liquidacion->cuotas()->create([
            'numero_cuota' => 2,
            'monto_cuota' => 555000,
            'fecha_vencimiento' => Carbon::today()->addDays(28),
            'estado' => CuotaPago::ESTADO_PENDIENTE,
        ]);

        $liquidacion->recalcularEstados();
        $this->assertSame(Liquidacion::ESTADO_GENERAL_MOROSO, $liquidacion->fresh()->estado_general);

        // Pagar Cuota 1
        $response1 = $this->actingAs($admin)->post(route('servicios.cuotas.pagar', [$servicio, $cuota1]), [
            'metodo_pago' => 'Transferencia',
            'comprobante_referencia' => 'OP-123456',
        ]);

        $response1->assertRedirect();
        $cuota1->refresh();
        $this->assertSame(CuotaPago::ESTADO_PAGADA, $cuota1->estado);
        $this->assertSame(555000, $liquidacion->fresh()->monto_copago_pagado);
        $this->assertSame(Liquidacion::ESTADO_GENERAL_AL_DIA, $liquidacion->fresh()->estado_general);

        // Pagar Cuota 2
        $response2 = $this->actingAs($admin)->post(route('servicios.cuotas.pagar', [$servicio, $cuota2]), [
            'metodo_pago' => 'Transbank',
        ]);

        $response2->assertRedirect();
        $cuota2->refresh();
        $this->assertSame(CuotaPago::ESTADO_PAGADA, $cuota2->estado);
        $this->assertSame(Liquidacion::ESTADO_GENERAL_PAGADO_TOTAL, $liquidacion->fresh()->estado_general);
        $this->assertSame(0, $liquidacion->fresh()->saldoCopagoPendiente());
    }

    public function test_servicios_show_view_renders_estado_de_cuenta_y_copago_card(): void
    {
        $admin = User::factory()->create(['role' => 'administrador']);

        $servicio = Servicio::create([
            'created_by' => $admin->id,
            'folio' => 'FC-CARD-VIEW',
            'cliente_nombre' => 'Familia Morales Huenchulaf',
            'difunto_nombre' => 'Pedro Morales Huenchulaf',
            'convenio' => 'ips',
            'monto_servicio' => 1450000,
            'estado' => 'abierto',
        ]);

        $servicio->calcularAportes();
        $servicio->save();

        $liquidacion = $servicio->obtenerOCrearLiquidacion();
        $liquidacion->cuotas()->delete();
        $liquidacion->cuotas()->create([
            'numero_cuota' => 1,
            'monto_cuota' => 190000,
            'fecha_vencimiento' => Carbon::today()->subDays(5),
            'estado' => CuotaPago::ESTADO_ATRASADA,
        ]);
        $liquidacion->cuotas()->create([
            'numero_cuota' => 2,
            'monto_cuota' => 190000,
            'fecha_vencimiento' => Carbon::today()->addDays(25),
            'estado' => CuotaPago::ESTADO_PENDIENTE,
        ]);
        $liquidacion->recalcularEstados();

        $response = $this->actingAs($admin)->get(route('servicios.show', $servicio));
        $response->assertOk();
        $response->assertSee('Estado de Cuenta y Copago');
        $response->assertSee('Costo Total');
        $response->assertSee('$1.450.000 CLP');
        $response->assertSee('-$1.070.000 CLP');
        $response->assertSee('$380.000 CLP');
        $response->assertSee('Plan de Pago:');
        $response->assertSee('2 Cuotas');
        $response->assertSee('Moroso');
        $response->assertSee('1 cuota atrasada');
        $response->assertSee('Cuota 1');
        $response->assertSee('Cuota 2');
        $response->assertSee('Registrar Pago');
        $response->assertSee('Ver en Módulo de Finanzas');
    }
}

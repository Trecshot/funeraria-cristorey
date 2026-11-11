<?php

namespace Tests\Feature;

use App\Models\Liquidacion;
use App\Models\Servicio;
use App\Models\User;
use App\Services\IndicadoresService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanzasModuleTest extends TestCase
{
    use RefreshDatabase;

    protected User $userFinanzas;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userFinanzas = User::factory()->create([
            'email' => 'finanzas@cristorey.cl',
            'role' => 'finanzas',
        ]);
    }

    public function test_indicadores_service_returns_valid_uf_value(): void
    {
        $service = app(IndicadoresService::class);
        $uf = $service->getValorUF();

        $this->assertIsFloat($uf);
        $this->assertGreaterThan(30000, $uf);
        $this->assertStringStartsWith('$', $service->getValorUFFormateado());
    }

    public function test_servicio_calculates_dynamic_copago_for_ips_and_afp(): void
    {
        $servicioIps = Servicio::create([
            'folio' => 'FC-TEST-IPS',
            'created_by' => $this->userFinanzas->id,
            'cliente_nombre' => 'Familia Morales',
            'difunto_nombre' => 'Pedro Morales',
            'convenio' => 'ips',
            'monto_servicio' => 1450000,
            'estado' => 'abierto',
        ]);

        $servicioIps->calcularAportes();
        $this->assertEquals(Servicio::APORTE_IPS_CLP, $servicioIps->aporte_convenio);
        $this->assertEquals(380000, $servicioIps->copago);

        $servicioAfp = Servicio::create([
            'folio' => 'FC-TEST-AFP',
            'created_by' => $this->userFinanzas->id,
            'cliente_nombre' => 'Familia Sepulveda',
            'difunto_nombre' => 'Jorge Sepulveda',
            'convenio' => 'afp',
            'monto_servicio' => 1680000,
            'estado' => 'abierto',
        ]);

        $servicioAfp->calcularAportes(40000.0);
        $this->assertEquals(600000, $servicioAfp->aporte_convenio);
        $this->assertEquals(1080000, $servicioAfp->copago);
    }

    public function test_obtener_o_crear_liquidacion_generates_foliated_record(): void
    {
        $servicio = Servicio::create([
            'folio' => 'FC-TEST-LIQ',
            'created_by' => $this->userFinanzas->id,
            'cliente_nombre' => 'Familia Gonzalez',
            'difunto_nombre' => 'Juan Gonzalez',
            'convenio' => 'ips',
            'monto_servicio' => 1200000,
            'estado' => 'abierto',
        ]);

        $liq = $servicio->obtenerOCrearLiquidacion();

        $this->assertNotNull($liq);
        $this->assertDatabaseHas('liquidaciones', [
            'servicio_id' => $servicio->id,
            'monto_total' => 1200000,
            'monto_subvencion' => 1070000,
            'monto_copago_familia' => 130000,
            'estado_subvencion' => 'tramitacion',
            'estado_copago' => 'pendiente',
        ]);
        $this->assertStringStartsWith('FE-', $liq->folio_factura);
        $this->assertStringStartsWith('BE-', $liq->folio_boleta);
    }

    public function test_finanzas_index_displays_kpis_and_services(): void
    {
        $servicio = Servicio::create([
            'folio' => 'FC-TEST-VIEW',
            'created_by' => $this->userFinanzas->id,
            'cliente_nombre' => 'Familia Perez',
            'difunto_nombre' => 'Ana Perez',
            'convenio' => 'ips',
            'monto_servicio' => 1500000,
            'estado' => 'abierto',
        ]);
        $servicio->obtenerOCrearLiquidacion();

        $response = $this->actingAs($this->userFinanzas)->get(route('finanzas.index'));

        $response->assertOk();
        $response->assertViewIs('finanzas.index');
        $response->assertViewHas('kpis');
        $response->assertViewHas('ufValue');
        $response->assertSee('Ana Perez');
        $response->assertSee('Total por Cobrar (Instituciones)');
        $response->assertSee('Total por Cobrar (Copagos Familias)');
    }

    public function test_user_can_update_liquidacion_folios_and_status(): void
    {
        $servicio = Servicio::create([
            'folio' => 'FC-TEST-UPDATE',
            'created_by' => $this->userFinanzas->id,
            'cliente_nombre' => 'Familia Soto',
            'difunto_nombre' => 'Mario Soto',
            'convenio' => 'afp',
            'monto_servicio' => 1800000,
            'estado' => 'abierto',
        ]);
        $servicio->obtenerOCrearLiquidacion();

        $response = $this->actingAs($this->userFinanzas)
            ->patch(route('finanzas.liquidaciones.update', $servicio), [
                'folio_factura' => 'FE-99881',
                'folio_boleta' => 'BE-33442',
                'estado_subvencion' => 'aprobado',
                'estado_copago' => 'pagado_total',
                'metodo_pago_familia' => 'Transferencia',
                'monto_copago_pagado' => 1200000,
                'observaciones' => 'Liquidación confirmada en sucursal.',
            ]);

        $response->assertRedirect(route('finanzas.index'));
        $this->assertDatabaseHas('liquidaciones', [
            'servicio_id' => $servicio->id,
            'folio_factura' => 'FE-99881',
            'folio_boleta' => 'BE-33442',
            'estado_subvencion' => 'aprobado',
            'estado_copago' => 'pagado_total',
            'metodo_pago_familia' => 'Transferencia',
        ]);
    }

    public function test_pdf_generation_routes_return_pdf_response(): void
    {
        $servicio = Servicio::create([
            'folio' => 'FC-TEST-PDF',
            'created_by' => $this->userFinanzas->id,
            'cliente_nombre' => 'Familia Castro',
            'difunto_nombre' => 'Elena Castro',
            'convenio' => 'ips',
            'monto_servicio' => 1350000,
            'estado' => 'abierto',
        ]);
        $servicio->obtenerOCrearLiquidacion();

        $rFactura = $this->actingAs($this->userFinanzas)->get(route('finanzas.factura_exenta', $servicio));
        $rFactura->assertOk();
        $this->assertEquals('application/pdf', $rFactura->headers->get('content-type'));

        $rBoleta = $this->actingAs($this->userFinanzas)->get(route('finanzas.boleta_copago', $servicio));
        $rBoleta->assertOk();
        $this->assertEquals('application/pdf', $rBoleta->headers->get('content-type'));

        $rGeneral = $this->actingAs($this->userFinanzas)->get(route('finanzas.pdf', $servicio));
        $rGeneral->assertOk();
        $this->assertEquals('application/pdf', $rGeneral->headers->get('content-type'));
    }
}

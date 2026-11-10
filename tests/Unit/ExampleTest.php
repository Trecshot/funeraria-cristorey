<?php

namespace Tests\Unit;

use App\Models\DocumentoServicio;
use App\Models\ProductoBodega;
use App\Models\Servicio;
use App\Models\User;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_servicio_calculates_ips_and_afp_contributions_and_missing_documents(): void
    {
        config(['funeraria.uf_value_clp' => 38000]);

        $ips = new Servicio(['convenio' => 'ips', 'monto_servicio' => 1500000]);
        $ips->calcularAportes();
        $this->assertSame(Servicio::APORTE_IPS_CLP, $ips->aporte_convenio);
        $this->assertSame(430000.0, $ips->copagoEstimado());

        $afp = new Servicio(['convenio' => 'afp', 'monto_servicio' => 1000000, 'causa_sml' => 'Peritaje']);
        $afp->calcularAportes();
        $this->assertSame(15 * 38000, $afp->aporte_convenio);
        $this->assertSame(430000.0, $afp->copagoEstimado());
        $this->assertSame(['resolucion_seremi', 'oficio_fiscalia_sml'], $afp->documentosRequeridos());
    }

    public function test_producto_bodega_computes_code_and_critical_stock(): void
    {
        $producto = new ProductoBodega([
            'stock_salon' => 1,
            'stock_taller' => 1,
            'stock_minimo' => 2,
        ]);
        $producto->id = 7;

        $this->assertSame('URN-007', $producto->codigo);
        $this->assertSame(2, $producto->stock_total);
        $this->assertTrue($producto->stock_critico);
    }

    public function test_user_roles_and_document_labels_are_centralized(): void
    {
        $admin = new User(['role' => 'administrador']);
        $chofer = new User(['role' => 'chofer']);

        $this->assertTrue($admin->hasRole('administrador', 'gerencia'));
        $this->assertTrue($admin->canAccessFinances());
        $this->assertFalse($chofer->canAccessFinances());
        $this->assertSame('Resolución Seremi', (new DocumentoServicio(['tipo' => 'resolucion_seremi']))->tipoLabel());
    }
}

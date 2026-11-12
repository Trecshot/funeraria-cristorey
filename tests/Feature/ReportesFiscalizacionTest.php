<?php

namespace Tests\Feature;

use App\Models\DocumentoLegal;
use App\Models\Servicio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportesFiscalizacionTest extends TestCase
{
    use RefreshDatabase;

    private function crearExpedienteDePrueba(User $admin, string $folio, string $nombre, string $rut, string $contexto, ?string $folioMedico = null, bool $interurbano = false): Servicio
    {
        $servicio = Servicio::create([
            'folio' => $folio,
            'created_by' => $admin->id,
            'cliente_nombre' => 'Contratante Prueba',
            'cliente_rut' => '12.345.678-9',
            'cliente_telefono' => '+56 9 1111 2222',
            'difunto_nombre' => $nombre,
            'difunto_rut' => $rut,
            'convenio' => 'ips',
            'monto_servicio' => 1500000,
            'aporte_convenio' => 1070000,
            'copago' => 430000,
            'estado' => 'abierto',
        ]);

        DocumentoLegal::create([
            'expediente_id' => $servicio->id,
            'contexto_deceso' => $contexto,
            'traslado_interurbano' => $interurbano,
            'folio_certificado_medico' => $folioMedico,
            'folio_pase_sepultacion' => 'PS-TEST-001',
        ]);

        return $servicio;
    }

    public function test_admin_can_view_reportes_module_and_sidebar_link(): void
    {
        $admin = User::factory()->create(['role' => 'administrador']);

        $response = $this->actingAs($admin)->get(route('reportes.index'));

        $response->assertOk();
        $response->assertSee('Reportes y Fiscalización');
        $response->assertSee('Generador de Informes Normativos (Seremi / Registro Civil)');
    }

    public function test_unauthorized_roles_cannot_access_reportes(): void
    {
        $chofer = User::factory()->create(['role' => 'chofer']);
        $asistente = User::factory()->create(['role' => 'asistente']);

        $this->actingAs($chofer)->get(route('reportes.index'))->assertForbidden();
        $this->actingAs($asistente)->get(route('reportes.index'))->assertForbidden();
        $this->actingAs($chofer)->post(route('reportes.exportar'), [
            'fecha_inicio' => now()->subDays(10)->format('Y-m-d'),
            'fecha_fin' => now()->format('Y-m-d'),
        ])->assertForbidden();
    }

    public function test_export_validates_date_range_strictly(): void
    {
        $admin = User::factory()->create(['role' => 'administrador']);

        $response = $this->actingAs($admin)->post(route('reportes.exportar'), [
            'fecha_inicio' => '2026-06-15',
            'fecha_fin' => '2026-06-01', // Anterior a fecha_inicio
        ]);

        $response->assertSessionHasErrors(['fecha_fin']);
    }

    public function test_csv_nominal_export_streams_records_and_filters_by_context(): void
    {
        $admin = User::factory()->create(['role' => 'administrador']);

        $this->crearExpedienteDePrueba($admin, 'FC-NAT-01', 'Juan Perez Natural', '8.111.222-3', 'NATURAL', 'CMD-9991');
        $this->crearExpedienteDePrueba($admin, 'FC-SML-02', 'Pedro Soto Judicial', '9.333.444-5', 'SML_VIOLENTO', null);

        $response = $this->actingAs($admin)->post(route('reportes.exportar'), [
            'fecha_inicio' => now()->subDay()->format('Y-m-d'),
            'fecha_fin' => now()->addDay()->format('Y-m-d'),
            'contexto' => 'NATURAL',
            'anonimizado' => '0',
            'formato' => 'csv',
        ]);

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));

        $csvContent = $response->streamedContent();

        // Verifica BOM UTF-8 y cabeceras nominales
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csvContent);
        $this->assertStringContainsString('RUT Difunto', $csvContent);
        $this->assertStringContainsString('Nombre Difunto', $csvContent);

        // Debe incluir el caso NATURAL y excluir el caso SML_VIOLENTO
        $this->assertStringContainsString('FC-NAT-01', $csvContent);
        $this->assertStringContainsString('Juan Perez Natural', $csvContent);
        $this->assertStringContainsString('8.111.222-3', $csvContent);
        $this->assertStringContainsString('CMD-9991', $csvContent);
        $this->assertStringNotContainsString('FC-SML-02', $csvContent);
        $this->assertStringNotContainsString('Pedro Soto Judicial', $csvContent);
    }

    public function test_csv_anonymized_export_omits_personal_identifiers_ley_21719(): void
    {
        $admin = User::factory()->create(['role' => 'administrador']);

        $this->crearExpedienteDePrueba($admin, 'FC-ANON-01', 'Maria Confidencial Reservada', '7.654.321-K', 'NATURAL', 'CMD-SECRET-1');

        $response = $this->actingAs($admin)->post(route('reportes.exportar'), [
            'fecha_inicio' => now()->subDay()->format('Y-m-d'),
            'fecha_fin' => now()->addDay()->format('Y-m-d'),
            'contexto' => 'TODOS',
            'anonimizado' => '1',
            'formato' => 'csv',
        ]);

        $response->assertOk();
        $csvContent = $response->streamedContent();

        // Debe contener Folio y Contexto
        $this->assertStringContainsString('FC-ANON-01', $csvContent);
        $this->assertStringContainsString('NATURAL', $csvContent);

        // Ley 21.719: NO debe contener RUT ni Nombre del Difunto ni cabeceras nominales
        $this->assertStringNotContainsString('RUT Difunto', $csvContent);
        $this->assertStringNotContainsString('Nombre Difunto', $csvContent);
        $this->assertStringNotContainsString('Maria Confidencial Reservada', $csvContent);
        $this->assertStringNotContainsString('7.654.321-K', $csvContent);
        $this->assertStringNotContainsString('CMD-SECRET-1', $csvContent);
    }

    public function test_pdf_resumen_export_returns_pdf_response(): void
    {
        $admin = User::factory()->create(['role' => 'administrador']);
        $this->crearExpedienteDePrueba($admin, 'FC-PDF-01', 'Carlos Informe Pdf', '10.222.333-4', 'RURAL_TESTIGOS');

        $response = $this->actingAs($admin)->post(route('reportes.exportar'), [
            'fecha_inicio' => now()->subDay()->format('Y-m-d'),
            'fecha_fin' => now()->addDay()->format('Y-m-d'),
            'contexto' => 'TODOS',
            'anonimizado' => '0',
            'formato' => 'pdf',
        ]);

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
    }
}

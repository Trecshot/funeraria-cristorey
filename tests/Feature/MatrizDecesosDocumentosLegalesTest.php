<?php

namespace Tests\Feature;

use App\Models\DocumentoLegal;
use App\Models\HistorialAuditoria;
use App\Models\Servicio;
use App\Models\Traslado;
use App\Models\User;
use App\Models\Vehiculo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MatrizDecesosDocumentosLegalesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'filesystems.disks.s3.bucket' => 'bucket-cristorey-test',
            'filesystems.disks.s3.key' => 'test-key',
            'filesystems.disks.s3.secret' => 'test-secret',
            'funeraria.uf_value_clp' => 38000,
        ]);

        Storage::fake('s3');
    }

    public function test_form_renders_matriz_decesos_and_security_copy(): void
    {
        $admin = User::factory()->create(['role' => 'administrador']);

        $response = $this->actingAs($admin)->get(route('servicios.create'));

        $response->assertOk();
        $response->assertSee('Matriz de Contextos de Deceso y Requisitos Sanitarios');
        $response->assertSee('Contexto del Deceso');
        $response->assertSee('Plazo Legal Estricto de 48 Horas (Código Sanitario)');
        $response->assertSee('Folio Certificado Médico de Defunción');
        $response->assertSee('¿Requiere Traslado Interurbano (Entre Comunas)?');
        $response->assertSee('Cifrado AES-256 Activo');
    }

    public function test_muerte_natural_requires_folio_medico_and_encrypts_at_rest(): void
    {
        $admin = User::factory()->create(['role' => 'administrador']);

        // 1. Falla si falta el folio médico en muerte natural
        $failResponse = $this->actingAs($admin)->post(route('servicios.store'), [
            'cliente_nombre' => 'María Contratante',
            'difunto_nombre' => 'Juan Difunto Natural',
            'convenio' => 'particular',
            'monto_servicio' => 1200000,
            'estado' => 'abierto',
            'contexto_deceso' => 'NATURAL',
            'folio_certificado_medico' => '', // Inválido
        ]);

        $failResponse->assertSessionHasErrors(['folio_certificado_medico']);

        // 2. Éxito cuando se proporciona el folio médico
        $response = $this->actingAs($admin)->post(route('servicios.store'), [
            'cliente_nombre' => 'María Contratante',
            'difunto_nombre' => 'Juan Difunto Natural',
            'convenio' => 'particular',
            'monto_servicio' => 1200000,
            'estado' => 'abierto',
            'contexto_deceso' => 'NATURAL',
            'folio_certificado_medico' => 'CMD-2026-999888',
            'folio_pase_sepultacion' => 'PS-2026-111222',
        ]);

        $response->assertRedirect();
        $servicio = Servicio::where('difunto_nombre', 'Juan Difunto Natural')->firstOrFail();
        $docLegal = $servicio->documentoLegal;

        $this->assertNotNull($docLegal);
        $this->assertEquals('NATURAL', $docLegal->contexto_deceso);
        $this->assertEquals('CMD-2026-999888', $docLegal->folio_certificado_medico);
        $this->assertEquals('PS-2026-111222', $docLegal->folio_pase_sepultacion);

        // Verificación de Cifrado en Reposo (Data-at-Rest Encryption Ley 21.719)
        // El valor sin procesar en la tabla SQL NO debe ser texto plano
        $rawMedico = DB::table('documentos_legales')->where('id', $docLegal->id)->value('folio_certificado_medico');
        $rawPase = DB::table('documentos_legales')->where('id', $docLegal->id)->value('folio_pase_sepultacion');

        $this->assertNotEquals('CMD-2026-999888', $rawMedico);
        $this->assertNotEquals('PS-2026-111222', $rawPase);
        $this->assertStringContainsString('eyJ', $rawMedico); // Payload base64 cifrado por Laravel

        // Verificación de Auditoría Inmutable (Ley 21.663)
        $auditoria = HistorialAuditoria::where('expediente_id', $servicio->id)->first();
        $this->assertNotNull($auditoria);
        $this->assertEquals('documentos_legales', $auditoria->target_entity);
        $this->assertEquals(64, strlen($auditoria->transaction_hash)); // Hash SHA-256
    }

    public function test_muerte_violenta_sml_requires_fiscalia_order_file(): void
    {
        $admin = User::factory()->create(['role' => 'administrador']);

        // 1. Falla si no se adjunta orden de autopsia / fiscalía
        $failResponse = $this->actingAs($admin)->post(route('servicios.store'), [
            'cliente_nombre' => 'Pedro Pariente',
            'difunto_nombre' => 'Víctor SML Test',
            'convenio' => 'particular',
            'monto_servicio' => 1500000,
            'estado' => 'abierto',
            'contexto_deceso' => 'SML_VIOLENTO',
        ]);

        $failResponse->assertSessionHasErrors(['orden_autopsia_sml']);

        // 2. Éxito con archivo adjunto
        $file = UploadedFile::fake()->create('oficio_fiscalia_sml.pdf', 500, 'application/pdf');

        $response = $this->actingAs($admin)->post(route('servicios.store'), [
            'cliente_nombre' => 'Pedro Pariente',
            'difunto_nombre' => 'Víctor SML Test',
            'convenio' => 'particular',
            'monto_servicio' => 1500000,
            'estado' => 'abierto',
            'contexto_deceso' => 'SML_VIOLENTO',
            'orden_autopsia_sml' => $file,
        ]);

        $response->assertRedirect();
        $servicio = Servicio::where('difunto_nombre', 'Víctor SML Test')->firstOrFail();
        $docLegal = $servicio->documentoLegal;

        $this->assertNotNull($docLegal);
        $this->assertEquals('SML_VIOLENTO', $docLegal->contexto_deceso);
        $this->assertNotNull($docLegal->ruta_orden_autopsia_sml);
        Storage::disk('s3')->assertExists($docLegal->ruta_orden_autopsia_sml);

        // Registro de auditoría específico de carga de documento
        $this->assertDatabaseHas('historial_auditorias', [
            'expediente_id' => $servicio->id,
            'descripcion' => 'Carga de Orden de Autopsia / Parte de Fiscalía SML',
        ]);
    }

    public function test_muerte_rural_requires_two_witnesses(): void
    {
        $admin = User::factory()->create(['role' => 'administrador']);

        // 1. Falla si faltan testigos o son menos de 2
        $failResponse = $this->actingAs($admin)->post(route('servicios.store'), [
            'cliente_nombre' => 'Familia Curamil',
            'difunto_nombre' => 'Anselmo Curamil Rural',
            'convenio' => 'particular',
            'monto_servicio' => 1100000,
            'estado' => 'abierto',
            'contexto_deceso' => 'RURAL_TESTIGOS',
            'testigos' => [
                ['nombre' => 'Solo Un Testigo', 'rut' => '12.345.678-9'],
            ],
        ]);

        $failResponse->assertSessionHasErrors(['testigos']);

        // 2. Éxito con 2 testigos válidos
        $response = $this->actingAs($admin)->post(route('servicios.store'), [
            'cliente_nombre' => 'Familia Curamil',
            'difunto_nombre' => 'Anselmo Curamil Rural',
            'convenio' => 'particular',
            'monto_servicio' => 1100000,
            'estado' => 'abierto',
            'contexto_deceso' => 'RURAL_TESTIGOS',
            'testigos' => [
                ['nombre' => 'Juan Huenchumil', 'rut' => '11.222.333-4'],
                ['nombre' => 'Rosa Millape', 'rut' => '14.555.666-7'],
            ],
        ]);

        $response->assertRedirect();
        $servicio = Servicio::where('difunto_nombre', 'Anselmo Curamil Rural')->firstOrFail();
        $docLegal = $servicio->documentoLegal;

        $this->assertNotNull($docLegal);
        $this->assertEquals('RURAL_TESTIGOS', $docLegal->contexto_deceso);
        $this->assertCount(2, $docLegal->testigos_rurales);
        $this->assertEquals('Juan Huenchumil', $docLegal->testigos_rurales[0]['nombre']);
        $this->assertEquals('14.555.666-7', $docLegal->testigos_rurales[1]['rut']);
    }

    public function test_traslado_interurbano_requires_resolucion_seremi(): void
    {
        $admin = User::factory()->create(['role' => 'administrador']);

        // 1. Falla si marca traslado interurbano pero no sube resolución
        $failResponse = $this->actingAs($admin)->post(route('servicios.store'), [
            'cliente_nombre' => 'Cliente Interurbano',
            'difunto_nombre' => 'Difunto Cunco a Temuco',
            'convenio' => 'particular',
            'monto_servicio' => 1400000,
            'estado' => 'abierto',
            'contexto_deceso' => 'NATURAL',
            'folio_certificado_medico' => 'CMD-554433',
            'traslado_interurbano' => 1,
        ]);

        $failResponse->assertSessionHasErrors(['resolucion_seremi']);

        // 2. Éxito con archivo de Resolución Seremi adjunto
        $seremiFile = UploadedFile::fake()->create('resolucion_seremi_traslado.pdf', 600, 'application/pdf');

        $response = $this->actingAs($admin)->post(route('servicios.store'), [
            'cliente_nombre' => 'Cliente Interurbano',
            'difunto_nombre' => 'Difunto Cunco a Temuco',
            'convenio' => 'particular',
            'monto_servicio' => 1400000,
            'estado' => 'abierto',
            'contexto_deceso' => 'NATURAL',
            'folio_certificado_medico' => 'CMD-554433',
            'traslado_interurbano' => 1,
            'resolucion_seremi' => $seremiFile,
        ]);

        $response->assertRedirect();
        $servicio = Servicio::where('difunto_nombre', 'Difunto Cunco a Temuco')->firstOrFail();
        $docLegal = $servicio->documentoLegal;

        $this->assertTrue($docLegal->traslado_interurbano);
        $this->assertNotNull($docLegal->ruta_resolucion_seremi);
        Storage::disk('s3')->assertExists($docLegal->ruta_resolucion_seremi);

        // Registro de auditoría
        $this->assertDatabaseHas('historial_auditorias', [
            'expediente_id' => $servicio->id,
            'descripcion' => 'Carga de Resolución Sanitaria de Traslado (Art. 75 DTO 357)',
        ]);
    }

    public function test_chofer_mobile_view_applies_data_minimization(): void
    {
        $choferUser = User::factory()->create(['role' => 'chofer', 'name' => 'Chofer Carlos']);
        $admin = User::factory()->create(['role' => 'administrador']);

        $vehiculo = Vehiculo::create([
            'patente' => 'ABCD12',
            'nombre' => 'Carroza Mercedes-Benz',
            'modelo_uso' => 'Carroza de Traslado',
            'estado' => 'Disponible',
            'licencia_requerida' => 'A2',
        ]);

        $servicio = Servicio::create([
            'created_by' => $admin->id,
            'folio' => 'FC-MIN-001',
            'cliente_nombre' => 'Familia Confidencial',
            'difunto_nombre' => 'Persona Fallecida Confidencial',
            'convenio' => 'particular',
            'monto_servicio' => 5000000, // Dato financiero que el chofer NO debe ver
            'causa_sml' => 'Causa de Muerte Reservada Judicial', // Dato que el chofer NO debe ver
            'estado' => 'abierto',
        ]);

        Traslado::create([
            'servicio_id' => $servicio->id,
            'vehiculo_id' => $vehiculo->id,
            'chofer_id' => $choferUser->id,
            'salida_at' => now()->addHour(),
            'cementerio' => 'Cementerio Parque del Sendero',
            'estado' => 'programado',
        ]);

        $response = $this->actingAs($choferUser)->get(route('movil.chofer'));

        $response->assertOk();
        // Sí ve el nombre del difunto y el destino
        $response->assertSee('Persona Fallecida Confidencial');
        $response->assertSee('Cementerio Parque del Sendero');
        $response->assertSee('Valores Ocultos (RBAC)');

        // Minimización de datos (Ley 21.663): NO ve información financiera ni causa judicial
        $response->assertDontSee('$5.000.000');
        $response->assertDontSee('5000000');
        $response->assertDontSee('Causa de Muerte Reservada Judicial');
    }

    public function test_can_download_legal_documents(): void
    {
        $admin = User::factory()->create(['role' => 'administrador']);

        $servicio = Servicio::create([
            'created_by' => $admin->id,
            'folio' => 'FC-DOWN-001',
            'cliente_nombre' => 'Cliente Descarga',
            'difunto_nombre' => 'Difunto Descarga',
            'convenio' => 'particular',
            'monto_servicio' => 1000000,
            'estado' => 'abierto',
        ]);

        $path = 'legales/'.$servicio->id.'/orden_test.pdf';
        Storage::disk('s3')->put($path, 'Contenido PDF de Prueba');

        DocumentoLegal::create([
            'expediente_id' => $servicio->id,
            'contexto_deceso' => 'SML_VIOLENTO',
            'ruta_orden_autopsia_sml' => $path,
        ]);

        $response = $this->actingAs($admin)->get(route('servicios.documentos_legales.descargar', [$servicio, 'sml', 'inline' => 1]));

        $response->assertOk();
    }
}

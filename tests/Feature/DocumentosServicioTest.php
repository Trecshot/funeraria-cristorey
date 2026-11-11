<?php

namespace Tests\Feature;

use App\Models\DocumentoServicio;
use App\Models\HistorialServicio;
use App\Models\Servicio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentosServicioTest extends TestCase
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

    public function test_create_and_edit_forms_render_legal_copywriting_without_infrastructure_jargon(): void
    {
        $admin = User::factory()->create(['role' => 'administrador']);

        $response = $this->actingAs($admin)->get(route('servicios.create'));
        $response->assertOk();
        $response->assertSee('Documentación Legal y Anexos');
        $response->assertSee('Formatos admitidos: PDF, JPG o PNG (Máx. 15 MB por archivo)');
        $response->assertDontSee('Amazon S3');
        $response->assertDontSee('Bucket Privado');

        $servicio = Servicio::create([
            'created_by' => $admin->id,
            'folio' => 'FC-TEST-DOCS-01',
            'cliente_nombre' => 'Ana Morales',
            'difunto_nombre' => 'Raúl Morales',
            'convenio' => 'particular',
            'monto_servicio' => 1200000,
            'estado' => 'abierto',
        ]);

        $editResponse = $this->actingAs($admin)->get(route('servicios.edit', $servicio));
        $editResponse->assertOk();
        $editResponse->assertSee('Documentación Legal y Anexos');
        $editResponse->assertDontSee('Amazon S3');
    }

    public function test_document_upload_attaches_file_and_updates_milestones(): void
    {
        $admin = User::factory()->create(['role' => 'administrador']);
        $file = UploadedFile::fake()->create('resolucion_seremi_001.pdf', 800, 'application/pdf');

        $response = $this->actingAs($admin)->post(route('servicios.store'), [
            'cliente_nombre' => 'Familia Sepúlveda',
            'difunto_nombre' => 'Alberto Sepúlveda',
            'convenio' => 'ips',
            'monto_servicio' => 1450000,
            'estado' => 'abierto',
            'tipos_documentos' => ['resolucion_seremi'],
            'documentos' => [$file],
        ]);

        $response->assertRedirect();
        $servicio = Servicio::where('difunto_nombre', 'Alberto Sepúlveda')->firstOrFail();

        $this->assertDatabaseHas('documentos_servicio', [
            'servicio_id' => $servicio->id,
            'tipo' => 'resolucion_seremi',
            'nombre_original' => 'resolucion_seremi_001.pdf',
        ]);

        $documento = $servicio->documentos()->where('tipo', 'resolucion_seremi')->firstOrFail();
        Storage::disk('s3')->assertExists($documento->path);

        $this->assertDatabaseHas('historial_servicios', [
            'servicio_id' => $servicio->id,
            'hito' => HistorialServicio::HITO_DOCUMENTAL,
            'titulo' => 'Documentación Validada',
        ]);
    }

    public function test_document_inline_preview_and_download(): void
    {
        $admin = User::factory()->create(['role' => 'administrador']);
        $servicio = Servicio::create([
            'created_by' => $admin->id,
            'folio' => 'FC-DOC-PREVIEW',
            'cliente_nombre' => 'Contratante',
            'difunto_nombre' => 'Difunto',
            'convenio' => 'particular',
            'monto_servicio' => 1000000,
            'estado' => 'abierto',
        ]);

        $path = Storage::disk('s3')->putFile("servicios/{$servicio->id}", UploadedFile::fake()->create('certificado.pdf', 300, 'application/pdf'));

        $documento = DocumentoServicio::create([
            'servicio_id' => $servicio->id,
            'uploaded_by' => $admin->id,
            'tipo' => 'certificado_defuncion',
            'nombre_original' => 'certificado.pdf',
            'disk' => 's3',
            'path' => $path,
            'ruta' => $path,
        ]);

        // Preview inline
        $inlineResponse = $this->actingAs($admin)->get(route('servicios.documentos.descargar', [
            'servicio' => $servicio,
            'documento' => $documento,
            'inline' => 1,
        ]));
        $inlineResponse->assertOk();
        $this->assertStringContainsString('inline', (string) $inlineResponse->headers->get('Content-Disposition'));

        // Direct download
        $downloadResponse = $this->actingAs($admin)->get(route('servicios.documentos.descargar', [
            'servicio' => $servicio,
            'documento' => $documento,
        ]));
        $downloadResponse->assertOk();
        $this->assertStringContainsString('attachment', (string) $downloadResponse->headers->get('Content-Disposition'));
    }

    public function test_document_can_be_deleted_and_milestones_are_recalculated(): void
    {
        $admin = User::factory()->create(['role' => 'administrador']);
        $servicio = Servicio::create([
            'created_by' => $admin->id,
            'folio' => 'FC-DEL-DOC',
            'cliente_nombre' => 'Contratante',
            'difunto_nombre' => 'Difunto',
            'convenio' => 'particular',
            'monto_servicio' => 1000000,
            'estado' => 'abierto',
        ]);

        $path = Storage::disk('s3')->putFile("servicios/{$servicio->id}", UploadedFile::fake()->create('resolucion.pdf', 400, 'application/pdf'));

        $documento = DocumentoServicio::create([
            'servicio_id' => $servicio->id,
            'uploaded_by' => $admin->id,
            'tipo' => 'resolucion_seremi',
            'nombre_original' => 'resolucion.pdf',
            'disk' => 's3',
            'path' => $path,
            'ruta' => $path,
        ]);

        Storage::disk('s3')->assertExists($path);
        $servicio->sincronizarHitosOperativos($admin->id);

        $response = $this->actingAs($admin)->delete(route('servicios.documentos.destroy', [$servicio, $documento]));
        $response->assertRedirect();
        $response->assertSessionHas('status');

        $this->assertDatabaseMissing('documentos_servicio', ['id' => $documento->id]);
        Storage::disk('s3')->assertMissing($path);

        // Al eliminar la resolución requerida, el hito legal vuelve a estar pendiente
        $servicio->refresh();
        $this->assertContains('resolucion_seremi', $servicio->calcularDocumentosFaltantes());
    }

    public function test_unauthorized_user_cannot_access_or_delete_documents(): void
    {
        $admin = User::factory()->create(['role' => 'administrador']);
        $chofer = User::factory()->create(['role' => 'chofer']);

        $servicio = Servicio::create([
            'created_by' => $admin->id,
            'folio' => 'FC-RESTRICTED',
            'cliente_nombre' => 'Cliente',
            'difunto_nombre' => 'Difunto',
            'convenio' => 'particular',
            'monto_servicio' => 1000000,
            'estado' => 'abierto',
        ]);

        $path = Storage::disk('s3')->putFile("servicios/{$servicio->id}", UploadedFile::fake()->create('resolucion.pdf', 100, 'application/pdf'));

        $documento = DocumentoServicio::create([
            'servicio_id' => $servicio->id,
            'uploaded_by' => $admin->id,
            'tipo' => 'resolucion_seremi',
            'nombre_original' => 'resolucion.pdf',
            'disk' => 's3',
            'path' => $path,
            'ruta' => $path,
        ]);

        $this->actingAs($chofer)
            ->get(route('servicios.documentos.descargar', [$servicio, $documento]))
            ->assertForbidden();

        $this->actingAs($chofer)
            ->delete(route('servicios.documentos.destroy', [$servicio, $documento]))
            ->assertForbidden();
    }
}

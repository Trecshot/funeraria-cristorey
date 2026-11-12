<?php

namespace App\Services;

use App\Http\Requests\Servicio\GuardarServicioRequest;
use App\Models\DocumentoLegal;
use App\Models\DocumentoServicio;
use App\Models\HistorialAuditoria;
use App\Models\HistorialServicio;
use App\Models\Servicio;
use App\Models\Urna;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ServicioExpedienteService
{
    public function persistir(GuardarServicioRequest $request, Servicio $servicio): void
    {
        $data = $request->validated();
        $documentos = $data['documentos'] ?? [];
        $destino = $data['destino'] ?? null;

        // Datos del contexto legal (Matriz de Contextos de Deceso - Ley 21.719)
        $contextoDeceso = $data['contexto_deceso'] ?? 'NATURAL';
        $folioMedico = $data['folio_certificado_medico'] ?? null;
        $folioPase = $data['folio_pase_sepultacion'] ?? null;
        $trasladoInterurbano = $request->boolean('traslado_interurbano');
        $testigos = $data['testigos'] ?? [];

        unset(
            $data['documentos'],
            $data['destino'],
            $data['tipos_documentos'],
            $data['contexto_deceso'],
            $data['folio_certificado_medico'],
            $data['folio_pase_sepultacion'],
            $data['traslado_interurbano'],
            $data['testigos'],
            $data['orden_autopsia_sml'],
            $data['resolucion_seremi']
        );

        $this->assertS3Configured($documentos);

        $preserveUfValue = $servicio->exists
            && $data['convenio'] === 'afp'
            && ! filled(config('funeraria.uf_value_clp'))
            && $servicio->uf_value_clp;

        $servicio->fill($data);
        $servicio->requiere_sml = filled($servicio->causa_sml) || $contextoDeceso === 'SML_VIOLENTO';
        if (! $preserveUfValue) {
            $servicio->uf_value_clp = null;
        }
        $servicio->calcularAportes();

        DB::transaction(function () use (
            $request,
            $servicio,
            $destino,
            $documentos,
            $contextoDeceso,
            $folioMedico,
            $folioPase,
            $trasladoInterurbano,
            $testigos
        ): void {
            $isNew = ! $servicio->exists;
            $servicio->save();
            $servicio->obtenerOCrearLiquidacion();
            $this->sincronizarTraslado($servicio, $destino);
            $this->subirDocumentos($request, $servicio, $documentos);
            $this->sincronizarDocumentoLegal(
                $request,
                $servicio,
                $isNew,
                $contextoDeceso,
                $folioMedico,
                $folioPase,
                $trasladoInterurbano,
                $testigos
            );
        });

        $servicio->sincronizarHitosOperativos($request->user()->id);
    }

    public function asignarUrna(Servicio $servicio, Urna $urna, int $userId): void
    {
        $servicio->urna_id = $urna->id;
        $servicio->save();

        $servicio->registrarHito(
            HistorialServicio::HITO_LOGISTICA_URNA,
            'Urna Asignada',
            "Urna modelo {$urna->modelo} ({$urna->color} - {$urna->categoria_etaria}) asignada desde bodega.",
            $userId
        );
    }

    public function eliminarExpediente(Servicio $servicio): void
    {
        foreach ($servicio->documentos as $documento) {
            if ($documento->path && Storage::disk($documento->disk)->exists($documento->path)) {
                Storage::disk($documento->disk)->delete($documento->path);
            }
        }

        $servicio->delete();
    }

    public function eliminarDocumento(Servicio $servicio, DocumentoServicio $documento, int $userId): array
    {
        if ($documento->path && Storage::disk($documento->disk)->exists($documento->path)) {
            Storage::disk($documento->disk)->delete($documento->path);
        }

        $nombre = $documento->nombre_original;
        $tipoLabel = $documento->tipoLabel();
        $documento->delete();

        $servicio->load('documentos');
        $servicio->sincronizarHitosOperativos($userId);

        return ['nombre' => $nombre, 'tipoLabel' => $tipoLabel];
    }

    private function sincronizarTraslado(Servicio $servicio, ?string $destino): void
    {
        if ($servicio->estado === 'cancelado' || ! $servicio->cortejo_at || ! $destino) {
            $servicio->traslados()->where('estado', 'programado')->delete();

            return;
        }

        $traslado = $servicio->traslados()->firstOrNew();
        $traslado->fill([
            'salida_at' => $servicio->cortejo_at,
            'cementerio' => $destino,
        ])->save();
    }

    private function subirDocumentos(Request $request, Servicio $servicio, array $files): void
    {
        $disk = 's3';

        foreach ($files as $index => $file) {
            if (! $file) {
                continue;
            }

            $type = $request->input("tipos_documentos.$index");
            abort_unless(isset(DocumentoServicio::TIPOS[$type]), 422, 'El tipo de documento no es válido.');

            $path = Storage::disk($disk)->putFile("servicios/{$servicio->id}", $file);
            if (! $path) {
                abort(503, 'No se pudo guardar el documento en S3. Revisa la conexión y permisos del bucket.');
            }

            $documento = $servicio->documentos()->firstOrNew(['tipo' => $type]);
            $oldDisk = $documento->exists ? $documento->disk : null;
            $oldPath = $documento->exists ? $documento->path : null;

            if ($oldPath && Storage::disk($oldDisk)->exists($oldPath)) {
                Storage::disk($oldDisk)->delete($oldPath);
            }

            $documento->fill([
                'uploaded_by' => $request->user()->id,
                'tipo' => $type,
                'nombre_original' => $file->getClientOriginalName(),
                'disk' => $disk,
                'path' => $path,
                'ruta' => $path,
            ])->save();
        }
    }

    private function sincronizarDocumentoLegal(
        Request $request,
        Servicio $servicio,
        bool $isNew,
        string $contextoDeceso,
        ?string $folioMedico,
        ?string $folioPase,
        bool $trasladoInterurbano,
        array $testigos
    ): void {
        $docLegal = $servicio->documentoLegal ?: new DocumentoLegal(['expediente_id' => $servicio->id]);
        $docLegal->contexto_deceso = $contextoDeceso;
        $docLegal->traslado_interurbano = $trasladoInterurbano;

        if ($folioMedico !== null) {
            $docLegal->folio_certificado_medico = $folioMedico;
        }
        if ($folioPase !== null) {
            $docLegal->folio_pase_sepultacion = $folioPase;
        }

        if ($contextoDeceso === 'RURAL_TESTIGOS' && ! empty($testigos)) {
            $docLegal->testigos_rurales = array_values(
                array_filter($testigos, fn ($t) => ! empty($t['nombre']) || ! empty($t['rut']))
            );
        } elseif ($contextoDeceso !== 'RURAL_TESTIGOS') {
            $docLegal->testigos_rurales = null;
        }

        $disk = config('filesystems.disks.s3.bucket') ? 's3' : config('filesystems.default', 'local');

        if ($request->hasFile('orden_autopsia_sml')) {
            $fileSml = $request->file('orden_autopsia_sml');
            $pathSml = Storage::disk($disk)->putFile("legales/{$servicio->id}", $fileSml);
            $docLegal->ruta_orden_autopsia_sml = $pathSml;

            HistorialAuditoria::registrar(
                $servicio->id,
                $request->user()?->id,
                'UPDATE',
                'documentos_legales',
                'Carga de Orden de Autopsia / Parte de Fiscalía SML',
                $request->ip()
            );
        }

        if ($request->hasFile('resolucion_seremi')) {
            $fileSeremi = $request->file('resolucion_seremi');
            $pathSeremi = Storage::disk($disk)->putFile("legales/{$servicio->id}", $fileSeremi);
            $docLegal->ruta_resolucion_seremi = $pathSeremi;

            HistorialAuditoria::registrar(
                $servicio->id,
                $request->user()?->id,
                'UPDATE',
                'documentos_legales',
                'Carga de Resolución Sanitaria de Traslado (Art. 75 DTO 357)',
                $request->ip()
            );
        }

        $docLegal->save();

        // Auditoría Inmutable de Ciberseguridad (Ley N° 21.663)
        HistorialAuditoria::registrar(
            $servicio->id,
            $request->user()?->id,
            $isNew ? 'CREATE' : 'UPDATE',
            'documentos_legales',
            "Actualización de antecedentes legales del expediente (Contexto: {$docLegal->contextoLabel()})",
            $request->ip()
        );
    }

    private function assertS3Configured(array $files): void
    {
        if (count(array_filter($files)) === 0) {
            return;
        }

        $config = config('filesystems.disks.s3');
        abort_unless(
            filled($config['bucket'] ?? null),
            503,
            'Configura AWS_BUCKET y las credenciales de AWS o un rol IAM para cargar documentos privados en S3.'
        );
    }
}

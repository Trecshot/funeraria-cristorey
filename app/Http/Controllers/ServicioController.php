<?php

namespace App\Http\Controllers;

use App\Models\CuotaPago;
use App\Models\DocumentoLegal;
use App\Models\DocumentoServicio;
use App\Models\HistorialAuditoria;
use App\Models\HistorialServicio;
use App\Models\Liquidacion;
use App\Models\Servicio;
use App\Models\Urna;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ServicioController extends Controller
{
    public function index(): View
    {
        return view('servicios.index', [
            'servicios' => Servicio::with(['creador', 'urna', 'traslados.chofer', 'traslados.vehiculo', 'documentos', 'historial'])->latest()->paginate(12),
        ]);
    }

    public function create(): View
    {
        return view('servicios.create', [
            'servicio' => new Servicio,
            'urnas' => Urna::orderBy('modelo')->get(),
            'documentoLegal' => new DocumentoLegal(['contexto_deceso' => 'NATURAL']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $servicio = new Servicio([
            'created_by' => $request->user()->id,
            'folio' => 'FC-'.Str::upper((string) Str::uuid()),
        ]);

        $this->persistServicio($request, $servicio);

        return redirect()->route('servicios.show', $servicio)->with('status', 'Expediente creado.');
    }

    public function show(Servicio $servicio): View
    {
        $servicio->obtenerOCrearLiquidacion();
        $servicio->load([
            'documentos.usuario',
            'traslados.vehiculo',
            'traslados.chofer',
            'urna',
            'historial.usuario',
            'liquidacion.cuotas',
            'creador',
        ]);

        $pasos = $servicio->sincronizarHitosOperativos();
        $porcentajeAvance = $servicio->calcularPorcentajeAvance();
        $urnasDisponibles = Urna::orderBy('modelo')->get();

        return view('servicios.show', compact('servicio', 'pasos', 'porcentajeAvance', 'urnasDisponibles'));
    }

    public function edit(Servicio $servicio): View
    {
        $servicio->load(['documentos.usuario', 'urna', 'documentoLegal']);

        return view('servicios.create', [
            'servicio' => $servicio,
            'urnas' => Urna::orderBy('modelo')->get(),
            'documentoLegal' => $servicio->documentoLegal ?: new DocumentoLegal(['contexto_deceso' => 'NATURAL']),
        ]);
    }

    public function asignarUrna(Request $request, Servicio $servicio): RedirectResponse
    {
        $validated = $request->validate([
            'urna_id' => ['required', 'exists:urnas,id'],
        ]);

        $urna = Urna::findOrFail($validated['urna_id']);
        $servicio->urna_id = $urna->id;
        $servicio->save();

        $servicio->registrarHito(
            HistorialServicio::HITO_LOGISTICA_URNA,
            'Urna Asignada',
            "Urna modelo {$urna->modelo} ({$urna->color} - {$urna->categoria_etaria}) asignada desde bodega.",
            $request->user()->id
        );

        return redirect()->route('servicios.show', $servicio)->with('status', "Urna {$urna->modelo} asignada al expediente correctamente.");
    }

    public function update(Request $request, Servicio $servicio): RedirectResponse
    {
        $this->persistServicio($request, $servicio);

        return redirect()->route('servicios.show', $servicio)->with('status', 'Expediente actualizado.');
    }

    public function destroy(Servicio $servicio): RedirectResponse
    {
        foreach ($servicio->documentos as $documento) {
            if ($documento->path && Storage::disk($documento->disk)->exists($documento->path)) {
                Storage::disk($documento->disk)->delete($documento->path);
            }
        }

        $servicio->delete();

        return redirect()->route('servicios.index')->with('status', 'Expediente eliminado.');
    }

    public function descargarDocumento(Request $request, Servicio $servicio, DocumentoServicio $documento): StreamedResponse
    {
        abort_unless($documento->servicio_id === $servicio->id, 404);

        if ($request->boolean('inline')) {
            return Storage::disk($documento->disk)
                ->response($documento->path, $documento->nombre_original, [
                    'Content-Disposition' => 'inline; filename="'.addslashes($documento->nombre_original).'"',
                ]);
        }

        return Storage::disk($documento->disk)
            ->download($documento->path, $documento->nombre_original);
    }

    public function eliminarDocumento(Request $request, Servicio $servicio, DocumentoServicio $documento): RedirectResponse
    {
        abort_unless($documento->servicio_id === $servicio->id, 404);

        if ($documento->path && Storage::disk($documento->disk)->exists($documento->path)) {
            Storage::disk($documento->disk)->delete($documento->path);
        }

        $nombre = $documento->nombre_original;
        $tipoLabel = $documento->tipoLabel();
        $documento->delete();

        $servicio->load('documentos');
        $servicio->sincronizarHitosOperativos($request->user()->id);

        return back()->with('status', "Documento '{$tipoLabel}' ({$nombre}) eliminado correctamente.");
    }

    public function pagarCuota(Request $request, Servicio $servicio, CuotaPago $cuota): RedirectResponse
    {
        $liquidacion = $servicio->obtenerOCrearLiquidacion();
        abort_unless($cuota->liquidacion_id === $liquidacion->id, 404);

        $validated = $request->validate([
            'metodo_pago' => ['required', 'string', 'max:50'],
            'comprobante_referencia' => ['nullable', 'string', 'max:100'],
            'observaciones' => ['nullable', 'string', 'max:500'],
        ]);

        $cuota->update([
            'estado' => CuotaPago::ESTADO_PAGADA,
            'fecha_pago' => now(),
            'metodo_pago' => $validated['metodo_pago'],
            'comprobante_referencia' => $validated['comprobante_referencia'] ?? null,
            'observaciones' => $validated['observaciones'] ?? null,
        ]);

        $liquidacion->recalcularEstados();

        $servicio->registrarHito(
            HistorialServicio::HITO_FINALIZADO,
            'Pago de Cuota Registrado',
            "Cuota #{$cuota->numero_cuota} ({$cuota->montoFormateado()}) pagada vía {$validated['metodo_pago']}.",
            $request->user()->id
        );

        return back()->with('status', "Pago de Cuota #{$cuota->numero_cuota} ({$cuota->montoFormateado()}) registrado exitosamente.");
    }

    public function descargarDocumentoLegal(Request $request, Servicio $servicio, string $tipo): StreamedResponse
    {
        $docLegal = $servicio->documentoLegal;
        abort_unless($docLegal, 404);

        $ruta = match ($tipo) {
            'sml' => $docLegal->ruta_orden_autopsia_sml,
            'seremi' => $docLegal->ruta_resolucion_seremi,
            'cesion' => $docLegal->ruta_cesion_derechos,
            default => null,
        };

        abort_unless($ruta, 404);

        $disk = config('filesystems.disks.s3.bucket') ? 's3' : config('filesystems.default', 'local');
        $nombre = basename($ruta);

        if ($request->boolean('inline')) {
            return Storage::disk($disk)->response($ruta, $nombre, [
                'Content-Disposition' => 'inline; filename="'.addslashes($nombre).'"',
            ]);
        }

        return Storage::disk($disk)->download($ruta, $nombre);
    }

    private function persistServicio(Request $request, Servicio $servicio): void
    {
        $data = $this->validatedData($request, $servicio);
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
            $this->syncTraslado($servicio, $destino);
            $this->uploadDocuments($request, $servicio, $documentos);

            // Gestión del DocumentoLegal (Cifrado Data-at-Rest AES-256)
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
                $docLegal->testigos_rurales = array_values(array_filter($testigos, fn ($t) => ! empty($t['nombre']) || ! empty($t['rut'])));
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
        });

        $servicio->sincronizarHitosOperativos($request->user()->id);
    }

    private function validatedData(Request $request, ?Servicio $servicio = null): array
    {
        $rules = [
            'cliente_nombre' => ['required', 'string', 'max:255'],
            'cliente_telefono' => ['nullable', 'string', 'max:30'],
            'difunto_nombre' => ['required', 'string', 'max:255'],
            'escolaridad' => ['nullable', 'string', 'max:255'],
            'oficio' => ['nullable', 'string', 'max:255'],
            'causa_sml' => ['nullable', 'string', 'max:5000'],
            'convenio' => ['required', Rule::in(array_keys(Servicio::CONVENIOS))],
            'urna_id' => ['nullable', 'exists:urnas,id'],
            'monto_servicio' => ['required', 'integer', 'min:0', 'max:9999999999'],
            'cortejo_at' => ['nullable', 'date'],
            'destino' => ['nullable', 'string', 'max:255'],
            'estado' => ['required', Rule::in(array_keys(Servicio::ESTADOS))],

            'contexto_deceso' => ['nullable', Rule::in(['NATURAL', 'SML_VIOLENTO', 'RURAL_TESTIGOS'])],
            'folio_certificado_medico' => ['nullable', 'string', 'max:100'],
            'folio_pase_sepultacion' => ['nullable', 'string', 'max:100'],
            'traslado_interurbano' => ['nullable', 'boolean'],
            'orden_autopsia_sml' => ['nullable', 'file', 'max:15360', 'mimes:pdf,jpg,jpeg,png'],
            'resolucion_seremi' => ['nullable', 'file', 'max:15360', 'mimes:pdf,jpg,jpeg,png'],
            'testigos' => ['nullable', 'array'],
            'testigos.*.nombre' => ['nullable', 'string', 'max:255'],
            'testigos.*.rut' => ['nullable', 'string', 'max:20'],

            'documentos' => ['sometimes', 'array'],
            'documentos.*' => ['nullable', 'file', 'max:15360', 'mimes:pdf,jpg,jpeg,png'],
            'tipos_documentos' => ['sometimes', 'array'],
            'tipos_documentos.*' => ['required', Rule::in(array_keys(DocumentoServicio::TIPOS))],
        ];

        // Validaciones condicionales cuando se provee el contexto de deceso
        $contexto = $request->input('contexto_deceso');
        if ($contexto === 'NATURAL') {
            $rules['folio_certificado_medico'] = ['required', 'string', 'max:100'];
        } elseif ($contexto === 'SML_VIOLENTO') {
            $tieneArchivoSml = $servicio?->documentoLegal?->ruta_orden_autopsia_sml;
            if (! $tieneArchivoSml && ! $request->hasFile('orden_autopsia_sml')) {
                $rules['orden_autopsia_sml'] = ['required', 'file', 'max:15360', 'mimes:pdf,jpg,jpeg,png'];
            }
        } elseif ($contexto === 'RURAL_TESTIGOS') {
            $rules['testigos'] = ['required', 'array', 'min:2'];
            $rules['testigos.*.nombre'] = ['required', 'string', 'max:255'];
            $rules['testigos.*.rut'] = ['required', 'string', 'max:20'];
        }

        if ($request->boolean('traslado_interurbano')) {
            $tieneSeremi = ($servicio?->documentoLegal?->ruta_resolucion_seremi)
                || ($servicio && $servicio->documentos()->where('tipo', 'resolucion_seremi')->exists());
            if (! $tieneSeremi && ! $request->hasFile('resolucion_seremi')) {
                $rules['resolucion_seremi'] = ['required', 'file', 'max:15360', 'mimes:pdf,jpg,jpeg,png'];
            }
        }

        $messages = [
            'contexto_deceso.required' => 'Debes indicar el contexto del deceso para determinar los requisitos legales.',
            'folio_certificado_medico.required' => 'El Folio del Certificado Médico de Defunción es obligatorio en fallecimientos por causa natural.',
            'orden_autopsia_sml.required' => 'Debe adjuntar la Orden de Autopsia / Oficio de Fiscalía para fallecimientos con intervención del SML.',
            'testigos.required' => 'Para fallecimientos en zona rural se requiere la identificación de al menos 2 testigos.',
            'testigos.min' => 'Se requieren al menos 2 testigos presenciales para la inscripción en zona rural.',
            'testigos.*.nombre.required' => 'El nombre completo del testigo rural es obligatorio.',
            'testigos.*.rut.required' => 'El RUT del testigo rural es obligatorio.',
            'resolucion_seremi.required' => 'El traslado interurbano exige la Resolución Sanitaria de la Seremi de Salud (Art. 75 DTO 357).',
        ];

        return $request->validate($rules, $messages);
    }

    private function syncTraslado(Servicio $servicio, ?string $destino): void
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

    private function uploadDocuments(Request $request, Servicio $servicio, array $files): void
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

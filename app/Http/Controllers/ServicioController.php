<?php

namespace App\Http\Controllers;

use App\Models\DocumentoServicio;
use App\Models\Servicio;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ServicioController extends Controller
{
    private const DOCUMENT_TYPES = [
        'certificado_defuncion' => 'Certificado de defuncion',
        'resolucion_seremi' => 'Resolucion Seremi',
        'oficio_fiscalia_sml' => 'Oficio de Fiscalia / SML',
    ];

    public function index(): View
    {
        return view('servicios.index', [
            'servicios' => Servicio::with('creador')->latest()->paginate(12),
        ]);
    }

    public function create(): View
    {
        return view('servicios.create', ['servicio' => new Servicio]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        $data['created_by'] = $request->user()->id;
        $documentos = $data['documentos'] ?? [];
        $destino = $data['destino'] ?? null;
        unset($data['documentos']);
        unset($data['destino']);
        $data['requiere_sml'] = filled($data['causa_sml'] ?? null);
        $data['folio'] = 'FC-'.Str::upper((string) Str::uuid());
        $data = array_merge($data, $this->financialAttributes($data));
        $data = $this->compatibleServiceColumns($data, $destino);

        $this->assertS3Configured($documentos);
        $servicio = DB::transaction(function () use ($data, $destino, $documentos, $request) {
            $servicio = Servicio::create($data);
            $this->syncTraslado($servicio, $destino);
            $this->uploadDocuments($request, $servicio, $documentos);

            return $servicio;
        });

        return redirect()->route('servicios.show', $servicio)->with('status', 'Expediente creado.');
    }

    public function show(Servicio $servicio): View
    {
        $servicio->load(['documentos.usuario', 'traslados.vehiculo', 'traslados.chofer']);

        return view('servicios.show', compact('servicio'));
    }

    public function edit(Servicio $servicio): View
    {
        return view('servicios.create', compact('servicio'));
    }

    public function update(Request $request, Servicio $servicio): RedirectResponse
    {
        $data = $this->validatedData($request);
        $documentos = $data['documentos'] ?? [];
        $destino = $data['destino'] ?? null;
        unset($data['documentos']);
        unset($data['destino']);
        $data['requiere_sml'] = filled($data['causa_sml'] ?? null);
        $data = array_merge($data, $this->financialAttributes($data, $servicio));
        $data = $this->compatibleServiceColumns($data, $destino);

        $this->assertS3Configured($documentos);
        DB::transaction(function () use ($data, $destino, $documentos, $request, $servicio) {
            $servicio->update($data);
            $this->syncTraslado($servicio, $destino);
            $this->uploadDocuments($request, $servicio, $documentos);
        });

        return redirect()->route('servicios.show', $servicio)->with('status', 'Expediente actualizado.');
    }

    public function destroy(Servicio $servicio): RedirectResponse
    {
        foreach ($servicio->documentos as $documento) {
            if (! Storage::disk($documento->disk)->delete($documento->path)) {
                abort(503, 'No se pudo eliminar un documento privado. El expediente se conservó.');
            }
        }

        $servicio->delete();

        return redirect()->route('servicios.index')->with('status', 'Expediente eliminado.');
    }

    public function descargarDocumento(Servicio $servicio, DocumentoServicio $documento): StreamedResponse
    {
        abort_unless($documento->servicio_id === $servicio->id, 404);

        return Storage::disk($documento->disk)
            ->download($documento->path, $documento->nombre_original);
    }

    private function validatedData(Request $request): array
    {
        $types = implode(',', array_keys(self::DOCUMENT_TYPES));

        return $request->validate([
            'cliente_nombre' => ['required', 'string', 'max:255'],
            'cliente_telefono' => ['nullable', 'string', 'max:30'],
            'difunto_nombre' => ['required', 'string', 'max:255'],
            'escolaridad' => ['nullable', 'string', 'max:255'],
            'oficio' => ['nullable', 'string', 'max:255'],
            'causa_sml' => ['nullable', 'string', 'max:5000'],
            'convenio' => ['required', 'in:particular,ips,afp,dipreca,sindicato'],
            'monto_servicio' => ['required', 'integer', 'min:0', 'max:9999999999'],
            'cortejo_at' => ['nullable', 'date'],
            'destino' => ['nullable', 'string', 'max:255'],
            'estado' => ['required', 'in:abierto,en_preparacion,completado,cancelado'],
            'documentos' => ['sometimes', 'array'],
            'documentos.*' => ['nullable', 'file', 'max:15360', 'mimes:pdf,jpg,jpeg,png'],
            'tipos_documentos' => ['sometimes', 'array'],
            'tipos_documentos.*' => ['required', 'in:'.$types],
        ]);
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
        ]);
        if (Schema::hasColumn('traslados', 'destino')) {
            $traslado->destino = $destino;
        }
        $traslado->save();
    }

    private function uploadDocuments(Request $request, Servicio $servicio, array $files): void
    {
        $disk = 's3';

        foreach ($files as $index => $file) {
            if (! $file) {
                continue;
            }

            $type = $request->input("tipos_documentos.$index");
            abort_unless(isset(self::DOCUMENT_TYPES[$type]), 422, 'El tipo de documento no es válido.');

            $path = Storage::disk($disk)->putFile("servicios/{$servicio->id}", $file);
            if (! $path) {
                abort(503, 'No se pudo guardar el documento en S3. Revisa la conexión y permisos del bucket.');
            }

            $documento = $servicio->documentos()->firstOrNew(['tipo' => $type]);
            $oldDisk = $documento->exists ? $documento->disk : null;
            $oldPath = $documento->exists ? $documento->path : null;

            if ($oldPath && $oldDisk !== $disk && ! Storage::disk($oldDisk)->delete($oldPath)) {
                Storage::disk($disk)->delete($path);
                abort(503, 'No se pudo reemplazar el documento privado anterior.');
            }
            if ($oldPath && $oldDisk === $disk && ! Storage::disk($disk)->delete($oldPath)) {
                Storage::disk($disk)->delete($path);
                abort(503, 'No se pudo reemplazar el documento privado anterior.');
            }

            $documento->fill([
                'uploaded_by' => $request->user()->id,
                'tipo' => $type,
                'nombre_original' => $file->getClientOriginalName(),
                'disk' => $disk,
                'path' => $path,
            ])->save();
        }
    }

    private function financialAttributes(array $data, ?Servicio $existing = null): array
    {
        if (
            $data['convenio'] === 'afp'
            && ! filled(config('funeraria.uf_value_clp'))
            && $existing?->uf_value_clp
        ) {
            $data['uf_value_clp'] = $existing->uf_value_clp;
        }

        $servicio = new Servicio($data);
        $servicio->calcularAportes();

        return [
            'aporte_convenio' => $servicio->aporte_convenio,
            'copago' => $servicio->copago,
            'uf_value_clp' => $servicio->uf_value_clp,
        ];
    }

    private function compatibleServiceColumns(array $data, ?string $destino): array
    {
        foreach ([
            'cliente_contratante' => 'cliente_nombre',
            'telefono_cliente' => 'cliente_telefono',
            'difunto' => 'difunto_nombre',
            'total_servicio' => 'monto_servicio',
        ] as $compatibilityColumn => $sourceColumn) {
            if (Schema::hasColumn('servicios', $compatibilityColumn)) {
                $data[$compatibilityColumn] = $data[$sourceColumn];
            }
        }

        if ($destino !== null && Schema::hasColumn('servicios', 'destino')) {
            $data['destino'] = $destino;
        }

        return $data;
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

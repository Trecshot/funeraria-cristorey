<?php

namespace App\Http\Controllers;

use App\Http\Requests\Servicio\AsignarUrnaRequest;
use App\Http\Requests\Servicio\GuardarServicioRequest;
use App\Http\Requests\Servicio\PagarCuotaRequest;
use App\Models\CuotaPago;
use App\Models\DocumentoLegal;
use App\Models\DocumentoServicio;
use App\Models\Servicio;
use App\Models\Urna;
use App\Services\FinanzasService;
use App\Services\ServicioExpedienteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ServicioController extends Controller
{
    public function __construct(
        protected ServicioExpedienteService $expedienteService,
        protected FinanzasService $finanzasService
    ) {}

    public function index(): View
    {
        return view('servicios.index', [
            'servicios' => Servicio::with([
                'creador',
                'urna',
                'traslados.chofer',
                'traslados.vehiculo',
                'documentos',
                'historial',
                'documentoLegal',
            ])->latest()->paginate(12),
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

    public function store(GuardarServicioRequest $request): RedirectResponse
    {
        $servicio = new Servicio([
            'created_by' => $request->user()->id,
            'folio' => 'FC-'.Str::upper((string) Str::uuid()),
        ]);

        $this->expedienteService->persistir($request, $servicio);

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
            'documentoLegal',
            'auditorias.usuario',
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

    public function asignarUrna(AsignarUrnaRequest $request, Servicio $servicio): RedirectResponse
    {
        $urna = Urna::findOrFail($request->validated('urna_id'));
        $this->expedienteService->asignarUrna($servicio, $urna, $request->user()->id);

        return redirect()->route('servicios.show', $servicio)
            ->with('status', "Urna {$urna->modelo} asignada al expediente correctamente.");
    }

    public function update(GuardarServicioRequest $request, Servicio $servicio): RedirectResponse
    {
        $this->expedienteService->persistir($request, $servicio);

        return redirect()->route('servicios.show', $servicio)->with('status', 'Expediente actualizado.');
    }

    public function destroy(Servicio $servicio): RedirectResponse
    {
        $this->expedienteService->eliminarExpediente($servicio);

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

        $info = $this->expedienteService->eliminarDocumento($servicio, $documento, $request->user()->id);

        return back()->with('status', "Documento '{$info['tipoLabel']}' ({$info['nombre']}) eliminado correctamente.");
    }

    public function pagarCuota(PagarCuotaRequest $request, Servicio $servicio, CuotaPago $cuota): RedirectResponse
    {
        $liquidacion = $servicio->obtenerOCrearLiquidacion();
        abort_unless($cuota->liquidacion_id === $liquidacion->id, 404);

        $this->finanzasService->registrarPagoCuota($servicio, $cuota, $request->validated(), $request->user()->id);

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
}

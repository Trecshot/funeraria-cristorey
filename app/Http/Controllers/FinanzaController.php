<?php

namespace App\Http\Controllers;

use App\Models\Liquidacion;
use App\Models\Servicio;
use App\Services\IndicadoresService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FinanzaController extends Controller
{
    public function __construct(
        protected IndicadoresService $indicadoresService
    ) {}

    public function index(): View
    {
        $ufValue = $this->indicadoresService->getValorUF();
        $uf15Clp = round(15 * $ufValue);

        // Obtenemos los servicios con su liquidación
        $servicios = Servicio::with(['liquidacion', 'traslados'])->latest()->paginate(15);

        // Aseguramos que cada servicio en la lista tenga su liquidación creada y sincronizada
        foreach ($servicios as $servicio) {
            $servicio->obtenerOCrearLiquidacion($ufValue);
        }

        // Métricas globales (KPIs)
        $todosServicios = Servicio::with('liquidacion')->get();
        foreach ($todosServicios as $s) {
            $s->obtenerOCrearLiquidacion($ufValue);
        }

        $totalCobrarInstituciones = Liquidacion::whereIn('estado_subvencion', ['tramitacion', 'aprobado'])
            ->sum('monto_subvencion');

        $totalCobrarCopagosFamilias = Liquidacion::where('estado_copago', '!=', 'pagado_total')
            ->selectRaw('SUM(GREATEST(0, CAST(monto_copago_familia AS SIGNED) - CAST(monto_copago_pagado AS SIGNED))) as total')
            ->value('total') ?? 0;

        $inicioMes = Carbon::now('America/Santiago')->startOfMonth();
        $totalLiquidadoMes = Liquidacion::where(function ($query) use ($inicioMes) {
            $query->where('estado_subvencion', 'liquidado')
                ->where('updated_at', '>=', $inicioMes);
        })->sum('monto_subvencion') + Liquidacion::where('updated_at', '>=', $inicioMes)->sum('monto_copago_pagado');

        // Estado del Call Center 600 institucional
        $ahoraSantiago = Carbon::now('America/Santiago');
        $esFinDeSemana = $ahoraSantiago->isWeekend();
        $esHorarioHabil = ! $esFinDeSemana && $ahoraSantiago->hour >= 9 && $ahoraSantiago->hour < 17;

        return view('finanzas.index', [
            'servicios' => $servicios,
            'ufValue' => $ufValue,
            'uf15Clp' => $uf15Clp,
            'kpis' => [
                'total_instituciones' => (int) $totalCobrarInstituciones,
                'total_familias' => (int) $totalCobrarCopagosFamilias,
                'total_liquidado_mes' => (int) $totalLiquidadoMes,
            ],
            'callCenterAbierto' => $esHorarioHabil,
            'horaSantiago' => $ahoraSantiago->format('H:i'),
            'diaSemana' => $ahoraSantiago->translatedFormat('l d \d\e F'),
        ]);
    }

    public function actualizarLiquidacion(Request $request, Servicio $servicio): RedirectResponse
    {
        $validated = $request->validate([
            'folio_factura' => ['nullable', 'string', 'max:50'],
            'folio_boleta' => ['nullable', 'string', 'max:50'],
            'estado_subvencion' => ['required', Rule::in(array_keys(Liquidacion::ESTADOS_SUBVENCION))],
            'estado_copago' => ['required', Rule::in(array_keys(Liquidacion::ESTADOS_COPAGO))],
            'metodo_pago_familia' => ['nullable', 'string', 'max:100'],
            'monto_copago_pagado' => ['nullable', 'integer', 'min:0'],
            'observaciones' => ['nullable', 'string', 'max:1000'],
        ]);

        $liquidacion = $servicio->obtenerOCrearLiquidacion();

        $pagado = (int) ($validated['monto_copago_pagado'] ?? $liquidacion->monto_copago_pagado);
        if ($validated['estado_copago'] === 'pagado_total' && $pagado < $liquidacion->monto_copago_familia) {
            $pagado = $liquidacion->monto_copago_familia;
        }

        $fechaLiquidacion = $liquidacion->fecha_liquidacion;
        if ($validated['estado_subvencion'] === 'liquidado' && ! $fechaLiquidacion) {
            $fechaLiquidacion = now();
        }

        $liquidacion->update([
            'folio_factura' => $validated['folio_factura'],
            'folio_boleta' => $validated['folio_boleta'],
            'estado_subvencion' => $validated['estado_subvencion'],
            'estado_copago' => $validated['estado_copago'],
            'metodo_pago_familia' => $validated['metodo_pago_familia'],
            'monto_copago_pagado' => $pagado,
            'fecha_liquidacion' => $fechaLiquidacion,
            'observaciones' => $validated['observaciones'] ?? null,
        ]);

        return redirect()->route('finanzas.index')->with('status', 'Liquidación y folios actualizados correctamente.');
    }

    public function generarPdf(Servicio $servicio): Response
    {
        $servicio->load(['liquidacion', 'traslados']);
        $liquidacion = $servicio->obtenerOCrearLiquidacion();
        $ufValue = $this->indicadoresService->getValorUF();

        $pdf = Pdf::loadView('finanzas.comprobante', compact('servicio', 'liquidacion', 'ufValue'));

        return $pdf->stream('comprobante-servicio-'.$servicio->id.'.pdf');
    }

    public function generarFacturaExentaPdf(Servicio $servicio): Response
    {
        $servicio->load(['liquidacion', 'traslados']);
        $liquidacion = $servicio->obtenerOCrearLiquidacion();
        $ufValue = $this->indicadoresService->getValorUF();

        $pdf = Pdf::loadView('finanzas.factura_exenta', compact('servicio', 'liquidacion', 'ufValue'));

        return $pdf->stream('factura-exenta-'.$servicio->id.'.pdf');
    }

    public function generarBoletaPdf(Servicio $servicio): Response
    {
        $servicio->load(['liquidacion', 'traslados']);
        $liquidacion = $servicio->obtenerOCrearLiquidacion();
        $ufValue = $this->indicadoresService->getValorUF();

        $pdf = Pdf::loadView('finanzas.boleta_copago', compact('servicio', 'liquidacion', 'ufValue'));

        return $pdf->stream('boleta-copago-'.$servicio->id.'.pdf');
    }
}

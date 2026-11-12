<?php

namespace App\Services;

use App\Models\CuotaPago;
use App\Models\HistorialServicio;
use App\Models\Liquidacion;
use App\Models\Servicio;
use Carbon\Carbon;

class FinanzasService
{
    public function __construct(
        protected IndicadoresService $indicadoresService
    ) {}

    /**
     * Obtiene los datos consolidados del panel financiero, KPIs y estado del Call Center.
     *
     * @return array<string, mixed>
     */
    public function obtenerPanelFinanciero(): array
    {
        $ufValue = $this->indicadoresService->getValorUF();
        $uf15Clp = round(15 * $ufValue);

        $servicios = Servicio::with(['liquidacion', 'traslados'])->latest()->paginate(15);

        foreach ($servicios as $servicio) {
            $servicio->obtenerOCrearLiquidacion($ufValue);
        }

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

        $ahoraSantiago = Carbon::now('America/Santiago');
        $esFinDeSemana = $ahoraSantiago->isWeekend();
        $esHorarioHabil = ! $esFinDeSemana && $ahoraSantiago->hour >= 9 && $ahoraSantiago->hour < 17;

        return [
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
        ];
    }

    public function actualizarLiquidacion(Servicio $servicio, array $validated): Liquidacion
    {
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
            'folio_factura' => $validated['folio_factura'] ?? null,
            'folio_boleta' => $validated['folio_boleta'] ?? null,
            'estado_subvencion' => $validated['estado_subvencion'],
            'estado_copago' => $validated['estado_copago'],
            'metodo_pago_familia' => $validated['metodo_pago_familia'] ?? null,
            'monto_copago_pagado' => $pagado,
            'fecha_liquidacion' => $fechaLiquidacion,
            'observaciones' => $validated['observaciones'] ?? null,
        ]);

        return $liquidacion;
    }

    public function registrarPagoCuota(Servicio $servicio, CuotaPago $cuota, array $validated, int $userId): void
    {
        $liquidacion = $servicio->obtenerOCrearLiquidacion();

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
            $userId
        );
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\Finanzas\UpdateLiquidacionRequest;
use App\Models\Servicio;
use App\Services\FinanzasService;
use App\Services\IndicadoresService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\View\View;

class FinanzaController extends Controller
{
    public function __construct(
        protected IndicadoresService $indicadoresService,
        protected FinanzasService $finanzasService
    ) {}

    public function index(): View
    {
        return view('finanzas.index', $this->finanzasService->obtenerPanelFinanciero());
    }

    public function actualizarLiquidacion(UpdateLiquidacionRequest $request, Servicio $servicio): RedirectResponse
    {
        $this->finanzasService->actualizarLiquidacion($servicio, $request->validated());

        return redirect()->route('finanzas.index')->with('status', 'Liquidación y folios actualizados correctamente.');
    }

    public function generarPdf(Servicio $servicio): Response
    {
        return $this->renderizarPdfFinanciero($servicio, 'finanzas.comprobante', 'comprobante-servicio-'.$servicio->id.'.pdf');
    }

    public function generarFacturaExentaPdf(Servicio $servicio): Response
    {
        return $this->renderizarPdfFinanciero($servicio, 'finanzas.factura_exenta', 'factura-exenta-'.$servicio->id.'.pdf');
    }

    public function generarBoletaPdf(Servicio $servicio): Response
    {
        return $this->renderizarPdfFinanciero($servicio, 'finanzas.boleta_copago', 'boleta-copago-'.$servicio->id.'.pdf');
    }

    private function renderizarPdfFinanciero(Servicio $servicio, string $vista, string $nombreArchivo): Response
    {
        $servicio->load(['liquidacion', 'traslados']);
        $liquidacion = $servicio->obtenerOCrearLiquidacion();
        $ufValue = $this->indicadoresService->getValorUF();

        $pdf = Pdf::loadView($vista, compact('servicio', 'liquidacion', 'ufValue'));

        return $pdf->stream($nombreArchivo);
    }
}

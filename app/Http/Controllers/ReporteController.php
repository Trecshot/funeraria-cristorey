<?php

namespace App\Http\Controllers;

use App\Http\Requests\Reportes\ExportarReporteRequest;
use App\Models\Servicio;
use App\Services\ReporteFiscalizacionService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReporteController extends Controller
{
    public function __construct(
        protected ReporteFiscalizacionService $reporteService
    ) {}

    /**
     * Muestra el panel de generación de informes normativos y fiscalización.
     */
    public function index(): View
    {
        $metricas = $this->reporteService->obtenerMetricasGlobales();

        $expedientesRecientes = Servicio::with('documentoLegal')
            ->latest()
            ->take(8)
            ->get();

        return view('reportes.index', compact('metricas', 'expedientesRecientes'));
    }

    /**
     * Exporta el informe normativo en formato CSV mediante StreamedResponse (o delega a PDF).
     * Cumple con la Ley N° 21.719 (Anonimización opcional para estadística general).
     */
    public function exportarCsv(ExportarReporteRequest $request): StreamedResponse|Response
    {
        if ($request->input('formato') === 'pdf') {
            return $this->exportarPdf($request);
        }

        $inicio = Carbon::parse($request->fecha_inicio)->startOfDay();
        $fin = Carbon::parse($request->fecha_fin)->endOfDay();
        $anonimizado = $request->boolean('anonimizado');

        $query = $this->reporteService->construirConsulta($request, $inicio, $fin);

        $prefijo = $anonimizado ? 'Reporte_Estadistico_Anonimizado_' : 'Reporte_Seremi_';
        $fileName = $prefijo.$inicio->format('Ymd').'_al_'.$fin->format('Ymd').'.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename={$fileName}",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(
            fn () => $this->reporteService->escribirCsvStream($query, $anonimizado),
            200,
            $headers
        );
    }

    /**
     * Genera el Resumen Ejecutivo en PDF para Fiscalización de Seremi / Registro Civil.
     */
    public function exportarPdf(ExportarReporteRequest $request): Response
    {
        $inicio = Carbon::parse($request->fecha_inicio)->startOfDay();
        $fin = Carbon::parse($request->fecha_fin)->endOfDay();
        $anonimizado = $request->boolean('anonimizado');
        $contexto = $request->input('contexto', 'TODOS');

        $expedientes = $this->reporteService->construirConsulta($request, $inicio, $fin)
            ->orderBy('created_at', 'desc')
            ->limit(500)
            ->get();

        $resumen = $this->reporteService->calcularResumenColeccion($expedientes);

        $pdf = Pdf::loadView('reportes.pdf_resumen', [
            'expedientes' => $expedientes,
            'inicio' => $inicio,
            'fin' => $fin,
            'contexto' => $contexto,
            'anonimizado' => $anonimizado,
            'resumen' => $resumen,
            'generadoPor' => $request->user()?->name ?? 'Administrador',
        ]);

        $fileName = ($anonimizado ? 'Resumen_Estadistico_Anonimizado_' : 'Resumen_Fiscalizacion_Seremi_')
            .$inicio->format('Ymd').'_al_'.$fin->format('Ymd').'.pdf';

        return $pdf->stream($fileName);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\DocumentoLegal;
use App\Models\HistorialAuditoria;
use App\Models\Servicio;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReporteController extends Controller
{
    /**
     * Muestra el panel de generación de informes normativos y fiscalización.
     */
    public function index(): View
    {
        $metricas = [
            'total' => Servicio::count(),
            'naturales' => DocumentoLegal::where('contexto_deceso', DocumentoLegal::CONTEXTO_NATURAL)->count(),
            'sml' => DocumentoLegal::where('contexto_deceso', DocumentoLegal::CONTEXTO_SML_VIOLENTO)->count(),
            'rurales' => DocumentoLegal::where('contexto_deceso', DocumentoLegal::CONTEXTO_RURAL_TESTIGOS)->count(),
            'interurbanos' => DocumentoLegal::where('traslado_interurbano', true)->count(),
        ];

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
    public function exportarCsv(Request $request): StreamedResponse|Response
    {
        $request->validate([
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
            'contexto' => ['nullable', 'string', 'in:TODOS,NATURAL,SML_VIOLENTO,RURAL_TESTIGOS'],
            'anonimizado' => ['nullable', 'boolean'],
            'solo_interurbano' => ['nullable', 'boolean'],
            'formato' => ['nullable', 'string', 'in:csv,pdf'],
        ]);

        if ($request->input('formato') === 'pdf') {
            return $this->exportarPdf($request);
        }

        $inicio = Carbon::parse($request->fecha_inicio)->startOfDay();
        $fin = Carbon::parse($request->fecha_fin)->endOfDay();
        $anonimizado = $request->boolean('anonimizado');
        $contexto = $request->input('contexto', 'TODOS');

        $query = $this->construirConsulta($request, $inicio, $fin);

        $prefijo = $anonimizado ? 'Reporte_Estadistico_Anonimizado_' : 'Reporte_Seremi_';
        $fileName = $prefijo . $inicio->format('Ymd') . '_al_' . $fin->format('Ymd') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename={$fileName}",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($query, $anonimizado): void {
            $file = fopen('php://output', 'w');

            // Añadir BOM para que Microsoft Excel lea correctamente los acentos (UTF-8)
            fputs($file, $bom = (chr(0xEF) . chr(0xBB) . chr(0xBF)));

            if ($anonimizado) {
                // Cumplimiento Ley N° 21.719: Estadística general sin RUT ni Nombre del Difunto
                fputcsv($file, [
                    'Folio Expediente',
                    'Fecha Registro',
                    'Causa/Contexto',
                    'Traslado Interurbano',
                    'Convenio Previsional',
                    'Estado Operativo',
                ]);
            } else {
                // Fiscalización Nominal Seremi de Salud / Registro Civil
                fputcsv($file, [
                    'Folio Expediente',
                    'Fecha Registro',
                    'RUT Difunto',
                    'Nombre Difunto',
                    'Causa/Contexto',
                    'Resolución/Certificado',
                    'Pase Sepultación',
                    'Traslado Interurbano',
                ]);
            }

            // Procesar en bloques (Chunk) de 100 para no saturar la memoria RAM del servidor
            $query->chunk(100, function ($expedientes) use ($file, $anonimizado): void {
                foreach ($expedientes as $exp) {
                    $doc = $exp->documentoLegal;
                    $contextoCodigo = $doc?->contexto_deceso ?? 'No registrado';
                    $interurbanoTexto = ($doc && $doc->traslado_interurbano) ? 'Sí (Art. 75 DTO 357)' : 'No (Local)';

                    if ($anonimizado) {
                        fputcsv($file, [
                            $exp->folio,
                            $exp->created_at?->format('d/m/Y H:i') ?? 'S/F',
                            $contextoCodigo,
                            $interurbanoTexto,
                            strtoupper((string) $exp->convenio),
                            $exp->estadoLabel(),
                        ]);
                    } else {
                        $certificadoOResolucion = $doc?->folio_certificado_medico;
                        if (! $certificadoOResolucion && $doc) {
                            if ($doc->esSmlViolento()) {
                                $certificadoOResolucion = $doc->ruta_orden_autopsia_sml ? 'Orden Fiscalía SML Adjunta' : 'SML Pendiente';
                            } elseif ($doc->esRural()) {
                                $numTestigos = is_array($doc->testigos_rurales) ? count($doc->testigos_rurales) : 0;
                                $certificadoOResolucion = "Acta Rural ({$numTestigos} Testigos)";
                            }
                        }

                        fputcsv($file, [
                            $exp->folio,
                            $exp->created_at?->format('d/m/Y H:i') ?? 'S/F',
                            $exp->rut_difunto ?? 'S/R',
                            $exp->nombre_difunto ?? 'Sin nombre',
                            $contextoCodigo,
                            $certificadoOResolucion ?? 'N/A',
                            $doc?->folio_pase_sepultacion ?? 'N/A',
                            $interurbanoTexto,
                        ]);
                    }
                }
            });

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Genera el Resumen Ejecutivo en PDF para Fiscalización de Seremi / Registro Civil.
     */
    public function exportarPdf(Request $request): Response
    {
        $request->validate([
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
            'contexto' => ['nullable', 'string', 'in:TODOS,NATURAL,SML_VIOLENTO,RURAL_TESTIGOS'],
            'anonimizado' => ['nullable', 'boolean'],
            'solo_interurbano' => ['nullable', 'boolean'],
        ]);

        $inicio = Carbon::parse($request->fecha_inicio)->startOfDay();
        $fin = Carbon::parse($request->fecha_fin)->endOfDay();
        $anonimizado = $request->boolean('anonimizado');
        $contexto = $request->input('contexto', 'TODOS');

        $expedientes = $this->construirConsulta($request, $inicio, $fin)
            ->orderBy('created_at', 'desc')
            ->limit(500)
            ->get();

        $resumen = [
            'total' => $expedientes->count(),
            'naturales' => $expedientes->filter(fn ($e) => $e->documentoLegal?->contexto_deceso === 'NATURAL')->count(),
            'sml' => $expedientes->filter(fn ($e) => $e->documentoLegal?->contexto_deceso === 'SML_VIOLENTO')->count(),
            'rurales' => $expedientes->filter(fn ($e) => $e->documentoLegal?->contexto_deceso === 'RURAL_TESTIGOS')->count(),
            'interurbanos' => $expedientes->filter(fn ($e) => (bool) $e->documentoLegal?->traslado_interurbano)->count(),
        ];

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
            . $inicio->format('Ymd') . '_al_' . $fin->format('Ymd') . '.pdf';

        return $pdf->stream($fileName);
    }

    /**
     * Construye el Query Builder optimizado con Eager Loading.
     */
    private function construirConsulta(Request $request, Carbon $inicio, Carbon $fin)
    {
        $query = Servicio::with(['documentoLegal', 'documentosLegales'])
            ->whereBetween('created_at', [$inicio, $fin]);

        if ($request->filled('contexto') && $request->contexto !== 'TODOS') {
            $query->whereHas('documentoLegal', function ($q) use ($request): void {
                $q->where('contexto_deceso', $request->contexto);
            });
        }

        if ($request->boolean('solo_interurbano')) {
            $query->whereHas('documentoLegal', function ($q): void {
                $q->where('traslado_interurbano', true);
            });
        }

        return $query;
    }
}

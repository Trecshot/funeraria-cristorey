<?php

namespace App\Services;

use App\Models\DocumentoLegal;
use App\Models\Servicio;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ReporteFiscalizacionService
{
    /**
     * @return array<string, int>
     */
    public function obtenerMetricasGlobales(): array
    {
        return [
            'total' => Servicio::count(),
            'naturales' => DocumentoLegal::where('contexto_deceso', DocumentoLegal::CONTEXTO_NATURAL)->count(),
            'sml' => DocumentoLegal::where('contexto_deceso', DocumentoLegal::CONTEXTO_SML_VIOLENTO)->count(),
            'rurales' => DocumentoLegal::where('contexto_deceso', DocumentoLegal::CONTEXTO_RURAL_TESTIGOS)->count(),
            'interurbanos' => DocumentoLegal::where('traslado_interurbano', true)->count(),
        ];
    }

    public function construirConsulta(Request $request, Carbon $inicio, Carbon $fin): Builder
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

    /**
     * @param  Collection<int, Servicio>  $expedientes
     * @return array<string, int>
     */
    public function calcularResumenColeccion(Collection $expedientes): array
    {
        return [
            'total' => $expedientes->count(),
            'naturales' => $expedientes->filter(fn ($e) => $e->documentoLegal?->contexto_deceso === 'NATURAL')->count(),
            'sml' => $expedientes->filter(fn ($e) => $e->documentoLegal?->contexto_deceso === 'SML_VIOLENTO')->count(),
            'rurales' => $expedientes->filter(fn ($e) => $e->documentoLegal?->contexto_deceso === 'RURAL_TESTIGOS')->count(),
            'interurbanos' => $expedientes->filter(fn ($e) => (bool) $e->documentoLegal?->traslado_interurbano)->count(),
        ];
    }

    public function escribirCsvStream(Builder $query, bool $anonimizado): void
    {
        $file = fopen('php://output', 'w');

        // Añadir BOM para que Microsoft Excel lea correctamente los acentos (UTF-8)
        fputs($file, chr(0xEF).chr(0xBB).chr(0xBF));

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
    }
}

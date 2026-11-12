<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Informe Normativo de Fiscalización · Funeraria Cristo Rey</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #1e293b; font-size: 10px; margin: 0; padding: 18px; }
        .header { border-bottom: 2px solid #1e1b4b; padding-bottom: 10px; margin-bottom: 14px; }
        h1 { font-size: 16px; margin: 2px 0 4px 0; color: #1e1b4b; }
        .subtitle { color: #64748b; font-size: 9px; margin: 0; }
        .kpi-table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        .kpi-cell { border: 1px solid #cbd5e1; background-color: #f8fafc; padding: 8px; text-align: center; width: 20%; }
        .kpi-title { font-size: 8px; text-transform: uppercase; color: #64748b; font-weight: bold; }
        .kpi-val { font-size: 14px; font-weight: bold; color: #0f172a; margin-top: 2px; }
        .data-table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        .data-table th { background-color: #1e1b4b; color: #ffffff; font-size: 8px; text-transform: uppercase; padding: 6px 5px; text-align: left; }
        .data-table td { border-bottom: 1px solid #e2e8f0; padding: 5px; font-size: 9px; }
        .badge { display: inline-block; padding: 1px 5px; border-radius: 3px; font-weight: bold; font-size: 8px; }
        .bg-natural { background-color: #dcfce7; color: #166534; }
        .bg-sml { background-color: #ffe4e6; color: #9f1239; }
        .bg-rural { background-color: #fef3c7; color: #92400e; }
        .footer { margin-top: 20px; border-top: 1px solid #cbd5e1; padding-top: 8px; font-size: 8px; color: #64748b; }
    </style>
</head>
<body>
    <div class="header">
        <p class="subtitle">FUNERARIA CRISTO REY · SEDE CALLE BLANCO, TEMUCO · REGIÓN DE LA ARAUCANÍA</p>
        <h1>
            @if($anonimizado)
                Informe Estadístico Anonimizado de Servicios (Ley N° 21.719)
            @else
                Informe Nominal de Fiscalización Sanitaria (Seremi de Salud / Registro Civil)
            @endif
        </h1>
        <p class="subtitle">
            Período: {{ $inicio->format('d/m/Y') }} al {{ $fin->format('d/m/Y') }}
            · Filtro Causal: {{ $contexto }}
            · Emitido el {{ now()->format('d/m/Y H:i') }} por {{ $generadoPor }}
        </p>
    </div>

    <table class="kpi-table">
        <tr>
            <td class="kpi-cell">
                <div class="kpi-title">Total Registros</div>
                <div class="kpi-val">{{ $resumen['total'] }}</div>
            </td>
            <td class="kpi-cell">
                <div class="kpi-title">Muerte Natural</div>
                <div class="kpi-val">{{ $resumen['naturales'] }}</div>
            </td>
            <td class="kpi-cell">
                <div class="kpi-title">SML / Violenta</div>
                <div class="kpi-val">{{ $resumen['sml'] }}</div>
            </td>
            <td class="kpi-cell">
                <div class="kpi-title">Zona Rural</div>
                <div class="kpi-val">{{ $resumen['rurales'] }}</div>
            </td>
            <td class="kpi-cell">
                <div class="kpi-title">Interurbanos (Seremi)</div>
                <div class="kpi-val">{{ $resumen['interurbanos'] }}</div>
            </td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th>Folio Expediente</th>
                <th>Fecha Registro</th>
                @if(! $anonimizado)
                    <th>RUT Difunto</th>
                    <th>Nombre Difunto</th>
                @endif
                <th>Causal / Contexto</th>
                @if(! $anonimizado)
                    <th>Resolución / Certificado</th>
                @endif
                <th>Traslado Interurbano</th>
                <th>Convenio</th>
            </tr>
        </thead>
        <tbody>
            @forelse($expedientes as $exp)
                @php
                    $doc = $exp->documentoLegal;
                    $ctx = $doc?->contexto_deceso ?? 'N/R';
                @endphp
                <tr>
                    <td><strong>{{ $exp->folio }}</strong></td>
                    <td>{{ $exp->created_at?->format('d/m/Y H:i') }}</td>
                    @if(! $anonimizado)
                        <td>{{ $exp->rut_difunto ?: 'S/R' }}</td>
                        <td>{{ $exp->nombre_difunto }}</td>
                    @endif
                    <td>
                        <span class="badge {{ $ctx === 'NATURAL' ? 'bg-natural' : ($ctx === 'SML_VIOLENTO' ? 'bg-sml' : 'bg-rural') }}">
                            {{ $ctx }}
                        </span>
                    </td>
                    @if(! $anonimizado)
                        <td>
                            @if($doc?->folio_certificado_medico)
                                {{ $doc->folio_certificado_medico }}
                            @elseif($doc?->esSmlViolento())
                                Orden Fiscalía SML
                            @elseif($doc?->esRural())
                                Acta 2 Testigos Rurales
                            @else
                                N/A
                            @endif
                        </td>
                    @endif
                    <td>{{ ($doc && $doc->traslado_interurbano) ? 'Sí (DTO 357)' : 'No (Local)' }}</td>
                    <td>{{ strtoupper((string) $exp->convenio) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $anonimizado ? 5 : 8 }}" style="text-align: center; padding: 14px;">
                        No existen expedientes en el rango de fechas y filtros seleccionados.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        @if($anonimizado)
            <strong>CERTIFICACIÓN DE PRIVACIDAD (LEY N° 21.719):</strong> Este documento ha sido generado aplicando anonimización de datos personales y clínicos (omisión de RUT, nombres y folios médicos nominales) para fines exclusivos de análisis estadístico general.
        @else
            <strong>DOCUMENTO CONFIDENCIAL DE FISCALIZACIÓN SANITARIA:</strong> Información nominal descifrada desde almacenamiento AES-256 para revisión exclusiva de la Autoridad Sanitaria (Seremi de Salud) o Servicio de Registro Civil e Identificación.
        @endif
    </div>
</body>
</html>

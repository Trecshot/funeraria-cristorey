<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Factura Exenta · Folio {{ $liquidacion->folio_factura ?: $servicio->id }}</title>
    <style>
        body { font-family: DejaVu Sans, Helvetica, Arial, sans-serif; color: #1e293b; font-size: 11px; line-height: 1.4; margin: 0; padding: 20px; }
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .header-table td { vertical-align: top; }
        .logo-title { font-size: 18px; font-weight: bold; color: #1e1b4b; margin: 0 0 4px 0; }
        .emisor-subtitle { font-size: 10px; color: #475569; margin: 0; }
        .sii-box {
            border: 2px solid #b91c1c;
            text-align: center;
            padding: 10px;
            width: 250px;
            float: right;
            border-radius: 4px;
        }
        .sii-box h2 { font-size: 13px; color: #b91c1c; margin: 2px 0; font-weight: bold; }
        .sii-box h3 { font-size: 11px; color: #b91c1c; margin: 2px 0; font-weight: bold; text-transform: uppercase; }
        .sii-box p { font-size: 10px; color: #b91c1c; margin: 2px 0; font-weight: bold; }
        .box { border: 1px solid #cbd5e1; border-radius: 4px; padding: 10px; margin-bottom: 14px; background-color: #f8fafc; }
        .section-title { font-size: 11px; font-weight: bold; color: #0f172a; border-bottom: 1px solid #e2e8f0; padding-bottom: 4px; margin-bottom: 6px; text-transform: uppercase; }
        .info-grid { width: 100%; border-collapse: collapse; }
        .info-grid td { padding: 3px 0; font-size: 10.5px; }
        .label { color: #64748b; font-weight: bold; width: 30%; }
        .value { color: #0f172a; }
        .items-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .items-table th { background-color: #1e1b4b; color: white; padding: 6px 8px; font-size: 10px; text-align: left; }
        .items-table td { padding: 8px; border-bottom: 1px solid #e2e8f0; font-size: 10.5px; }
        .totals-table { width: 45%; margin-left: auto; border-collapse: collapse; margin-top: 15px; }
        .totals-table td { padding: 4px 8px; font-size: 11px; }
        .totals-table tr.total-row td { font-weight: bold; font-size: 12px; border-top: 2px solid #0f172a; color: #1e1b4b; }
        .timbre {
            border: 1px solid #94a3b8;
            padding: 10px;
            margin-top: 30px;
            text-align: center;
            font-size: 9px;
            color: #64748b;
            border-radius: 4px;
        }
        .text-right { text-align: right; }
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            <td style="width: 58%;">
                <h1 class="logo-title">FUNERARIA CRISTO REY SpA</h1>
                <p class="emisor-subtitle">Giro: Servicios Funerarios, Inhumación y Traslados Integrales</p>
                <p class="emisor-subtitle">Casa Matriz: Calle Blanco 1234, Temuco, La Araucanía</p>
                <p class="emisor-subtitle">Teléfono: (45) 234 5678 / +56 9 8765 4321</p>
                <p class="emisor-subtitle">Email: finanzas@cristorey.cl · www.cristorey.cl</p>
            </td>
            <td style="width: 42%;">
                <div class="sii-box">
                    <h2>R.U.T.: 76.543.210-9</h2>
                    <h3>FACTURA NO AFECTA O EXENTA ELECTRÓNICA</h3>
                    <h2>N° {{ $liquidacion->folio_factura ?: 'FE-'.str_pad((string)$servicio->id, 5, '0', STR_PAD_LEFT) }}</h2>
                    <p>S.I.I. - DIRECCIÓN REGIONAL TEMUCO</p>
                </div>
            </td>
        </tr>
    </table>

    <div class="box">
        <div class="section-title">Receptor Institucional / Convenio Previsional</div>
        <table class="info-grid">
            <tr>
                <td class="label">Institución Pagadora:</td>
                <td class="value">
                    @if(strtolower($servicio->convenio) === 'afp')
                        <strong>ADMINISTRADORA DE FONDOS DE PENSIONES (AFP) · CUOTA MORTUORIA (D.L. 3.500)</strong>
                    @elseif(strtolower($servicio->convenio) === 'ips')
                        <strong>INSTITUTO DE PREVISIÓN SOCIAL (IPS) · ASIGNACIÓN POR MUERTE (PGU / LEY 20.255)</strong>
                    @else
                        <strong>CONVENIO {{ strtoupper($servicio->convenio) }}</strong>
                    @endif
                </td>
            </tr>
            <tr>
                <td class="label">RUT Institucional:</td>
                <td class="value">
                    {{ strtolower($servicio->convenio) === 'ips' ? '60.805.000-0 (IPS)' : '76.000.000-K (AFP Asignada)' }}
                </td>
            </tr>
            <tr>
                <td class="label">Fecha de Emisión:</td>
                <td class="value">{{ now()->format('d/m/Y') }} (Santiago, Chile)</td>
            </tr>
            <tr>
                <td class="label">Estado de Tramitación:</td>
                <td class="value"><strong>{{ $liquidacion->estadoSubvencionLabel() }}</strong></td>
            </tr>
        </table>
    </div>

    <div class="box">
        <div class="section-title">Antecedentes del Causante (Fallecido) y Expediente</div>
        <table class="info-grid">
            <tr>
                <td class="label">Nombre Causante:</td>
                <td class="value"><strong>{{ $servicio->difunto }}</strong></td>
                <td class="label">RUT Causante:</td>
                <td class="value">{{ $servicio->difunto_rut ?: 'No registrado' }}</td>
            </tr>
            <tr>
                <td class="label">Contratante / Deudo:</td>
                <td class="value">{{ $servicio->cliente_contratante }}</td>
                <td class="label">Expediente Interno:</td>
                <td class="value">#{{ $servicio->id }} (Folio: {{ $servicio->folio }})</td>
            </tr>
            <tr>
                <td class="label">Destino / Inhumación:</td>
                <td class="value" colspan="3">{{ $servicio->destino ?: 'Cementerio informado en hoja de ruta' }}</td>
            </tr>
        </table>
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th>Descripción de la Prestación Funeraria</th>
                <th style="width: 15%; text-align: center;">Cantidad</th>
                <th style="width: 25%; text-align: right;">Total Exento</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <strong>Cobertura de Prestación Funeraria Institucional</strong><br>
                    <span style="font-size: 9.5px; color: #475569;">
                        @if(strtolower($servicio->convenio) === 'afp')
                            Cuota Mortuoria de 15 UF (Valor UF de referencia: ${{ number_format((float) $ufValue, 0, ',', '.') }} CLP) según Art. 88 D.L. 3.500 para el causante {{ $servicio->difunto }}.
                        @elseif(strtolower($servicio->convenio) === 'ips')
                            Asignación por Muerte fijada por ley ($1.070.000 CLP) otorgada por el Instituto de Previsión Social (IPS).
                        @else
                            Subvención por convenio funerario {{ ucfirst($servicio->convenio) }}.
                        @endif
                    </span>
                </td>
                <td style="text-align: center;">1</td>
                <td class="text-right"><strong>${{ number_format((float) $liquidacion->monto_subvencion, 0, ',', '.') }} CLP</strong></td>
            </tr>
        </tbody>
    </table>

    <table class="totals-table">
        <tr>
            <td>Subtotal Exento:</td>
            <td class="text-right">${{ number_format((float) $liquidacion->monto_subvencion, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>I.V.A. (0% Exento):</td>
            <td class="text-right">$0</td>
        </tr>
        <tr class="total-row">
            <td>TOTAL A LIQUIDAR:</td>
            <td class="text-right">${{ number_format((float) $liquidacion->monto_subvencion, 0, ',', '.') }} CLP</td>
        </tr>
    </table>

    <div class="timbre">
        <p style="margin: 0; font-weight: bold; color: #1e293b;">TIMBRE ELECTRÓNICO S.I.I.</p>
        <p style="margin: 2px 0;">Res. N° 80 de 2014 - Verifique documento en www.sii.cl</p>
        <p style="margin: 2px 0; font-family: monospace;">FDO: {{ hash('sha256', 'FACTURA-'.$servicio->id.'-'.$liquidacion->folio_factura) }}</p>
    </div>
</body>
</html>

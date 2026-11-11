<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Boleta Copago · Folio {{ $liquidacion->folio_boleta ?: $servicio->id }}</title>
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
        .totals-table { width: 50%; margin-left: auto; border-collapse: collapse; margin-top: 15px; }
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
                    <h3>BOLETA ELECTRÓNICA EXENTA</h3>
                    <h2>N° {{ $liquidacion->folio_boleta ?: 'BE-'.str_pad((string)$servicio->id, 5, '0', STR_PAD_LEFT) }}</h2>
                    <p>S.I.I. - DIRECCIÓN REGIONAL TEMUCO</p>
                </div>
            </td>
        </tr>
    </table>

    <div class="box">
        <div class="section-title">Datos del Cliente / Contratante (Deudos)</div>
        <table class="info-grid">
            <tr>
                <td class="label">Señor(a):</td>
                <td class="value"><strong>{{ $servicio->cliente_contratante }}</strong></td>
                <td class="label">RUT Contratante:</td>
                <td class="value">{{ $servicio->cliente_rut ?: 'No registrado' }}</td>
            </tr>
            <tr>
                <td class="label">Teléfono:</td>
                <td class="value">{{ $servicio->cliente_telefono ?: 'S/I' }}</td>
                <td class="label">Fecha de Emisión:</td>
                <td class="value">{{ now()->format('d/m/Y') }}</td>
            </tr>
            <tr>
                <td class="label">Medio de Pago:</td>
                <td class="value"><strong>{{ $liquidacion->metodo_pago_familia ?: 'Por registrar' }}</strong></td>
                <td class="label">Estado Copago:</td>
                <td class="value"><strong>{{ $liquidacion->estadoCopagoLabel() }}</strong></td>
            </tr>
        </table>
    </div>

    <div class="box">
        <div class="section-title">Detalle del Expediente Funerario</div>
        <table class="info-grid">
            <tr>
                <td class="label">Causante (Fallecido):</td>
                <td class="value"><strong>{{ $servicio->difunto }}</strong></td>
                <td class="label">RUT Difunto:</td>
                <td class="value">{{ $servicio->difunto_rut ?: 'No registrado' }}</td>
            </tr>
            <tr>
                <td class="label">Expediente N°:</td>
                <td class="value">#{{ $servicio->id }} (Folio: {{ $servicio->folio }})</td>
                <td class="label">Convenio Aplicado:</td>
                <td class="value">{{ ucfirst($servicio->convenio) }}</td>
            </tr>
            <tr>
                <td class="label">Destino / Sepultación:</td>
                <td class="value" colspan="3">{{ $servicio->destino ?: 'Informado en cortejo' }}</td>
            </tr>
        </table>
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th>Detalle de la Prestación</th>
                <th style="width: 15%; text-align: center;">Cantidad</th>
                <th style="width: 25%; text-align: right;">Monto</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Costo Total del Plan Funerario Integral Contratado</td>
                <td style="text-align: center;">1</td>
                <td class="text-right">${{ number_format((float) $servicio->monto_servicio, 0, ',', '.') }} CLP</td>
            </tr>
            <tr>
                <td>
                    Descuento Cobertura Previsional Institucional ({{ strtoupper($servicio->convenio) }})<br>
                    <span style="font-size: 9.5px; color: #64748b;">(Facturado separadamente a la institución mediante Factura Exenta N° {{ $liquidacion->folio_factura }})</span>
                </td>
                <td style="text-align: center;">1</td>
                <td class="text-right" style="color: #b91c1c;">- ${{ number_format((float) $liquidacion->monto_subvencion, 0, ',', '.') }} CLP</td>
            </tr>
            <tr style="background-color: #f1f5f9;">
                <td><strong>COPAGO DIRECTO FAMILIA CONTRATANTE</strong></td>
                <td style="text-align: center;">1</td>
                <td class="text-right"><strong>${{ number_format((float) $liquidacion->monto_copago_familia, 0, ',', '.') }} CLP</strong></td>
            </tr>
        </tbody>
    </table>

    <table class="totals-table">
        <tr>
            <td>Copago Acordado:</td>
            <td class="text-right">${{ number_format((float) $liquidacion->monto_copago_familia, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Monto Abonado / Recibido:</td>
            <td class="text-right" style="color: #047857; font-weight: bold;">${{ number_format((float) $liquidacion->monto_copago_pagado, 0, ',', '.') }}</td>
        </tr>
        <tr class="total-row">
            <td>SALDO PENDIENTE:</td>
            <td class="text-right" style="color: {{ $liquidacion->saldoCopagoPendiente() > 0 ? '#b91c1c' : '#047857' }};">
                ${{ number_format((float) $liquidacion->saldoCopagoPendiente(), 0, ',', '.') }} CLP
            </td>
        </tr>
    </table>

    <div class="timbre">
        <p style="margin: 0; font-weight: bold; color: #1e293b;">TIMBRE ELECTRÓNICO S.I.I.</p>
        <p style="margin: 2px 0;">Res. N° 80 de 2014 - Verifique documento en www.sii.cl</p>
        <p style="margin: 2px 0; font-family: monospace;">FDO: {{ hash('sha256', 'BOLETA-'.$servicio->id.'-'.$liquidacion->folio_boleta) }}</p>
    </div>
</body>
</html>

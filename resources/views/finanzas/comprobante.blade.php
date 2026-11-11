<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Comprobante de Liquidación · Servicio #{{ $servicio->id }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #292524; font-size: 11px; margin: 0; padding: 20px; }
        h1 { font-size: 20px; margin: 4px 0 10px 0; color: #1e1b4b; }
        .muted { color: #78716c; font-size: 10px; }
        .box { border: 1px solid #d6d3d1; border-radius: 6px; padding: 12px; margin-top: 14px; background-color: #fafaf9; }
        .box-title { font-weight: bold; font-size: 11px; color: #1c1917; border-bottom: 1px solid #e7e5e4; padding-bottom: 4px; margin-bottom: 8px; text-transform: uppercase; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 6px 4px; border-bottom: 1px solid #e7e5e4; font-size: 11px; }
        .label { color: #57534e; font-weight: bold; width: 35%; }
        .value { color: #1c1917; }
        .badge { display: inline-block; padding: 2px 6px; border-radius: 4px; font-weight: bold; font-size: 9px; }
        .badge-green { background-color: #dcfce7; color: #15803d; }
        .badge-amber { background-color: #fef3c7; color: #b45309; }
        .badge-blue { background-color: #dbeafe; color: #1d4ed8; }
    </style>
</head>
<body>
    <p class="muted">Funeraria Cristo Rey · Sede Calle Blanco, Temuco</p>
    <h1>Comprobante de Liquidación y Desglose Financiero</h1>
    <p class="muted">Expediente #{{ $servicio->id }} (Folio: {{ $servicio->folio }}) · Fecha: {{ now()->format('d/m/Y H:i') }}</p>

    <div class="box">
        <div class="box-title">Antecedentes del Servicio</div>
        <table>
            <tr><td class="label">Difunto (Causante)</td><td class="value"><strong>{{ $servicio->difunto }}</strong> (RUT: {{ $servicio->difunto_rut ?: 'No registrado' }})</td></tr>
            <tr><td class="label">Contratante (Deudos)</td><td class="value">{{ $servicio->cliente_contratante }} (Tel: {{ $servicio->cliente_telefono ?: 'S/I' }})</td></tr>
            <tr><td class="label">Convenio Previsional</td><td class="value"><strong>{{ ucfirst($servicio->convenio) }}</strong></td></tr>
            <tr><td class="label">Destino / Cementerio</td><td class="value">{{ $servicio->destino ?: 'No informado' }}</td></tr>
            <tr><td class="label">Fecha y Hora de Cortejo</td><td class="value">{{ $servicio->cortejo_at?->format('d/m/Y H:i') ?? 'No informada' }}</td></tr>
        </table>
    </div>

    <div class="box">
        <div class="box-title">Desglose Financiero y Copagos</div>
        <table>
            <tr>
                <td class="label">Costo Total del Servicio</td>
                <td class="value font-bold">${{ number_format((float) $servicio->monto_servicio, 0, ',', '.') }} CLP</td>
            </tr>
            <tr>
                <td class="label">Subvención Convenio ({{ strtoupper($servicio->convenio) }})</td>
                <td class="value">
                    ${{ number_format((float) ($liquidacion ? $liquidacion->monto_subvencion : $servicio->aporteConvenio()), 0, ',', '.') }} CLP
                    @if(strtolower($servicio->convenio) === 'afp' && isset($ufValue))
                        <span class="muted">(15 UF a ${{ number_format((float) $ufValue, 0, ',', '.') }}/UF)</span>
                    @endif
                </td>
            </tr>
            <tr>
                <td class="label">Estado Subvención</td>
                <td class="value">
                    <span class="badge {{ $liquidacion && $liquidacion->estado_subvencion === 'liquidado' ? 'badge-green' : 'badge-amber' }}">
                        {{ $liquidacion ? $liquidacion->estadoSubvencionLabel() : 'En tramitación' }}
                    </span>
                    @if($liquidacion && $liquidacion->folio_factura)
                        <span class="muted">· Factura Exenta N° {{ $liquidacion->folio_factura }}</span>
                    @endif
                </td>
            </tr>
            <tr>
                <td class="label">Copago Familia</td>
                <td class="value font-bold">${{ number_format((float) ($liquidacion ? $liquidacion->monto_copago_familia : $servicio->copagoEstimado()), 0, ',', '.') }} CLP</td>
            </tr>
            <tr>
                <td class="label">Estado Copago Familia</td>
                <td class="value">
                    <span class="badge {{ $liquidacion && $liquidacion->estado_copago === 'pagado_total' ? 'badge-green' : 'badge-amber' }}">
                        {{ $liquidacion ? $liquidacion->estadoCopagoLabel() : 'Pendiente' }}
                    </span>
                    @if($liquidacion && $liquidacion->folio_boleta)
                        <span class="muted">· Boleta N° {{ $liquidacion->folio_boleta }}</span>
                    @endif
                </td>
            </tr>
            @if($liquidacion)
                <tr>
                    <td class="label">Monto Abonado Familia</td>
                    <td class="value">${{ number_format((float) $liquidacion->monto_copago_pagado, 0, ',', '.') }} CLP</td>
                </tr>
                <tr>
                    <td class="label">Saldo Pendiente Familia</td>
                    <td class="value font-bold" style="color: {{ $liquidacion->saldoCopagoPendiente() > 0 ? '#b91c1c' : '#15803d' }};">
                        ${{ number_format((float) $liquidacion->saldoCopagoPendiente(), 0, ',', '.') }} CLP
                    </td>
                </tr>
                @if($liquidacion->metodo_pago_familia)
                    <tr>
                        <td class="label">Medio de Pago Registrado</td>
                        <td class="value">{{ $liquidacion->metodo_pago_familia }}</td>
                    </tr>
                @endif
            @endif
        </table>
    </div>

    <p class="muted" style="margin-top: 20px;">
        Documento interno de control contable y liquidación de servicios funerarios. Los documentos tributarios oficiales correspondientes (Factura Exenta para convenio y Boleta para la familia) deben ser validados ante el SII.
    </p>
</body>
</html>

<!DOCTYPE html>
<html lang="es">
<head><meta charset="utf-8"><title>Comprobante · Servicio #{{ $servicio->id }}</title>
<style>
    body { font-family: DejaVu Sans, sans-serif; color: #292524; font-size: 12px; }
    h1 { font-size: 22px; margin-bottom: 4px; }
    .muted { color: #78716c; }
    .box { border: 1px solid #d6d3d1; border-radius: 6px; padding: 14px; margin-top: 16px; }
    table { width: 100%; border-collapse: collapse; }
    td { padding: 7px 0; border-bottom: 1px solid #e7e5e4; }
</style></head>
<body>
    <p class="muted">Funeraria Cristo Rey</p>
    <h1>Comprobante de servicio</h1>
    <p class="muted">Expediente #{{ $servicio->id }} · {{ $servicio->created_at->format('d/m/Y') }}</p>
    <div class="box">
        <table>
            <tr><td>Difunto</td><td>{{ $servicio->difunto }}</td></tr>
            <tr><td>Contratante</td><td>{{ $servicio->cliente_contratante }}</td></tr>
            <tr><td>Convenio</td><td>{{ ucfirst($servicio->convenio) }}</td></tr>
            <tr><td>Destino</td><td>{{ $servicio->destino ?: 'No informado' }}</td></tr>
            <tr><td>Fecha de cortejo</td><td>{{ $servicio->cortejo_at?->format('d/m/Y H:i') ?? 'No informada' }}</td></tr>
            <tr><td>Total del servicio</td><td>${{ number_format((float) $servicio->monto_servicio, 0, ',', '.') }} CLP</td></tr>
            <tr><td>Copago estimado</td><td>{{ $servicio->copagoEstimado() === null ? 'Por confirmar (15 UF)' : '$'.number_format($servicio->copagoEstimado(), 0, ',', '.').' CLP' }}</td></tr>
        </table>
    </div>
    <p class="muted">Documento referencial. Confirmar los aportes y condiciones vigentes del convenio.</p>
</body>
</html>
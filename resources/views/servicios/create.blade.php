@extends('layouts.app')

@section('title', $servicio->exists ? 'Editar expediente' : 'Nuevo expediente')
@section('heading', $servicio->exists ? 'Editar expediente' : 'Apertura de expediente')

@section('content')
    @php
        $convenio = old('convenio', $servicio->convenio ?? 'particular');
        $ufValue = config('funeraria.uf_value_clp');
    @endphp
    <form method="POST" action="{{ $servicio->exists ? route('servicios.update', $servicio) : route('servicios.store') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @if($servicio->exists) @method('PUT') @endif
        <section class="panel">
            <h2 class="text-lg">Cliente contratante</h2>
            <div class="mt-4 grid gap-4 md:grid-cols-2">
                <label><span class="label">Nombre completo *</span><input class="field" name="cliente_nombre" value="{{ old('cliente_nombre', $servicio->cliente_nombre) }}" required></label>
                <label><span class="label">Teléfono</span><input class="field" name="cliente_telefono" value="{{ old('cliente_telefono', $servicio->cliente_telefono) }}" autocomplete="tel"></label>
            </div>
        </section>

        <section class="panel">
            <h2 class="text-lg">Datos del difunto</h2>
            <div class="mt-4 grid gap-4 md:grid-cols-2">
                <label><span class="label">Nombre completo *</span><input class="field" name="difunto_nombre" value="{{ old('difunto_nombre', $servicio->difunto_nombre) }}" required></label>
                <label><span class="label">Escolaridad</span><input class="field" name="escolaridad" value="{{ old('escolaridad', $servicio->escolaridad) }}"></label>
                <label><span class="label">Oficio</span><input class="field" name="oficio" value="{{ old('oficio', $servicio->oficio) }}"></label>
                <label><span class="label">Causa SML (si corresponde)</span><input class="field" name="causa_sml" value="{{ old('causa_sml', $servicio->causa_sml) }}"></label>
            </div>
        </section>

        <section class="panel">
            <h2 class="text-lg">Convenio y copago</h2>
            <div class="mt-4 grid gap-4 md:grid-cols-3">
                <label>
                    <span class="label">Convenio previsional</span>
                    <select class="field" id="convenio" name="convenio">
                        @foreach(['particular' => 'Particular', 'ips' => 'IPS · $1.070.000', 'afp' => 'AFP · 15 UF', 'dipreca' => 'Dipreca', 'sindicato' => 'Sindicato'] as $value => $label)
                            <option value="{{ $value }}" @selected($convenio === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label><span class="label">Total del servicio (CLP) *</span><input class="field" id="total-servicio" type="number" min="0" step="1" name="monto_servicio" value="{{ old('monto_servicio', $servicio->monto_servicio ?? 0) }}" required></label>
                <div class="rounded-lg bg-stone-50 p-3 text-sm">
                    <p class="font-medium text-stone-700">Copago familiar estimado</p>
                    <p id="copago-estimado" class="mt-1 text-lg font-semibold text-stone-900">—</p>
                    <p id="convenio-ayuda" class="mt-1 text-xs text-stone-500" aria-live="polite"></p>
                </div>
            </div>
        </section>

        <section class="panel">
            <h2 class="text-lg">Cortejo y traslado</h2>
            <div class="mt-4 grid gap-4 md:grid-cols-3">
                <label><span class="label">Fecha y hora</span><input class="field" type="datetime-local" name="cortejo_at" value="{{ old('cortejo_at', $servicio->cortejo_at?->format('Y-m-d\TH:i')) }}"></label>
                <label><span class="label">Cementerio / destino</span><input class="field" name="destino" value="{{ old('destino', $servicio->destino) }}"></label>
                <label>
                    <span class="label">Estado</span>
                    <select class="field" name="estado">
                        @foreach(['abierto' => 'Abierto', 'en_preparacion' => 'En preparación', 'completado' => 'Completado', 'cancelado' => 'Cancelado'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('estado', $servicio->estado ?? 'abierto') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
            </div>
        </section>

        <section class="panel">
            <h2 class="text-lg">Documentos de respaldo</h2>
            <p class="mt-1 text-sm text-stone-500">Archivos PDF, JPG o PNG de hasta 15 MB. Se guardan en S3 privado; debes configurar sus credenciales en el entorno.</p>
            <div class="mt-4 grid gap-4 md:grid-cols-3">
                @foreach(['certificado_defuncion' => 'Certificado de defunción', 'resolucion_seremi' => 'Resolución Seremi', 'oficio_fiscalia_sml' => 'Oficio de Fiscalía / SML'] as $index => $label)
                    <label class="block">
                        <span class="label">{{ $label }}</span>
                        <input type="hidden" name="tipos_documentos[{{ $loop->index }}]" value="{{ $index }}">
                        <input class="field" type="file" name="documentos[{{ $loop->index }}]" accept=".pdf,.jpg,.jpeg,.png">
                    </label>
                @endforeach
            </div>
        </section>

        <div class="flex flex-wrap justify-end gap-3">
            <a class="button-secondary" href="{{ route('servicios.index') }}">Cancelar</a>
            <button class="button-primary">{{ $servicio->exists ? 'Guardar cambios' : 'Crear expediente' }}</button>
        </div>
    </form>
@endsection

@push('scripts')
<script>
    (() => {
        const convenio = document.getElementById('convenio');
        const total = document.getElementById('total-servicio');
        const copago = document.getElementById('copago-estimado');
        const ayuda = document.getElementById('convenio-ayuda');
        const ufValue = @json($ufValue ? (float) $ufValue : null);
        const pesos = value => new Intl.NumberFormat('es-CL', { style: 'currency', currency: 'CLP', maximumFractionDigits: 0 }).format(value);

        function actualizarCopago() {
            const amount = Number(total.value || 0);
            let aporte = 0;
            if (convenio.value === 'ips') {
                aporte = 1070000;
                ayuda.textContent = `Aporte de referencia: ${pesos(aporte)}.`;
            } else if (convenio.value === 'afp' && ufValue) {
                aporte = 15 * ufValue;
                ayuda.textContent = `15 UF calculadas con el valor configurado de ${pesos(ufValue)} por UF.`;
            } else if (convenio.value === 'afp') {
                copago.textContent = 'No estimable';
                ayuda.textContent = 'Configura UF_VALUE_CLP para calcular el equivalente en pesos.';
                return;
            } else if (['dipreca', 'sindicato'].includes(convenio.value)) {
                copago.textContent = 'Por confirmar';
                ayuda.textContent = 'El aporte de este convenio no está configurado.';
                return;
            } else {
                ayuda.textContent = 'Sin aporte previsional aplicado.';
            }

            copago.textContent = pesos(Math.max(0, amount - aporte));
        }

        convenio.addEventListener('change', actualizarCopago);
        total.addEventListener('input', actualizarCopago);
        actualizarCopago();
    })();
</script>
@endpush
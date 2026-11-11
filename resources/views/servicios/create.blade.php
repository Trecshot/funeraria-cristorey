@extends('layouts.app')

@section('title', $servicio->exists ? 'Editar expediente' : 'Nuevo expediente')
@section('heading', $servicio->exists ? 'Editar expediente' : 'Apertura de expediente')

@section('content')
    @php
        $convenio = old('convenio', $servicio->convenio ?? 'particular');
        $ufValue = config('funeraria.uf_value_clp') ?: app(\App\Services\IndicadoresService::class)->getValorUF();
    @endphp
    <form method="POST" action="{{ $servicio->exists ? route('servicios.update', $servicio) : route('servicios.store') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @if($servicio->exists) @method('PUT') @endif

        <section class="panel space-y-4">
            <div class="flex justify-between items-center border-b border-slate-100 pb-3">
                <div>
                    <h2 class="font-serif-title text-xl font-bold text-slate-900">Apertura y Modificación de Expediente Funerario</h2>
                    <p class="text-xs text-slate-500">Datos del contratante, antecedentes para Registro Civil y respaldo documental en Amazon S3.</p>
                </div>
                <span class="bg-indigo-100 text-indigo-900 text-xs font-bold px-3 py-1 rounded-full">
                    {{ $servicio->exists ? 'Expediente #'.$servicio->id : 'Nuevo Expediente' }}
                </span>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <label class="block">
                    <span class="label">Nombre completo del Contratante *</span>
                    <input class="field" name="cliente_nombre" value="{{ old('cliente_nombre', $servicio->cliente_nombre) }}" placeholder="Ej: María González" required>
                </label>
                <label class="block">
                    <span class="label">Teléfono de contacto</span>
                    <input class="field" name="cliente_telefono" value="{{ old('cliente_telefono', $servicio->cliente_telefono) }}" autocomplete="tel" placeholder="+56 9 ...">
                </label>
            </div>
        </section>

        <section class="panel">
            <h2 class="font-serif-title text-lg font-bold text-slate-900"><i class="fa-solid fa-user-check text-indigo-700 mr-1.5"></i>Datos del Difunto y Registro Civil</h2>
            <div class="mt-4 grid gap-4 md:grid-cols-2">
                <label class="block">
                    <span class="label">Nombre completo del Difunto *</span>
                    <input class="field" name="difunto_nombre" value="{{ old('difunto_nombre', $servicio->difunto_nombre) }}" required>
                </label>
                <label class="block">
                    <span class="label">Escolaridad (Registro Civil)</span>
                    <input class="field" name="escolaridad" value="{{ old('escolaridad', $servicio->escolaridad) }}" placeholder="Ej: Media Completa">
                </label>
                <label class="block">
                    <span class="label">Oficio / Profesión</span>
                    <input class="field" name="oficio" value="{{ old('oficio', $servicio->oficio) }}" placeholder="Ej: Pensionado">
                </label>
                <label class="block">
                    <span class="label">Causa SML / Fiscalía (si corresponde)</span>
                    <input class="field" name="causa_sml" value="{{ old('causa_sml', $servicio->causa_sml) }}" placeholder="Dejar en blanco si es fallecimiento natural">
                </label>
            </div>
        </section>

        <section class="panel">
            <h2 class="font-serif-title text-lg font-bold text-slate-900"><i class="fa-solid fa-file-invoice-dollar text-indigo-700 mr-1.5"></i>Convenio Previsional y Liquidación</h2>
            <div class="mt-4 grid gap-4 md:grid-cols-3">
                <label class="block">
                    <span class="label">Convenio previsional</span>
                    <select class="field" id="convenio" name="convenio">
                        @foreach(\App\Models\Servicio::CONVENIOS as $value => $label)
                            <option value="{{ $value }}" @selected($convenio === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block">
                    <span class="label">Total del servicio (CLP) *</span>
                    <input class="field" id="total-servicio" type="number" min="0" step="1" name="monto_servicio" value="{{ old('monto_servicio', $servicio->monto_servicio ?? 0) }}" required>
                </label>
                <div class="rounded-xl bg-indigo-50/70 border border-indigo-100 p-3 text-xs">
                    <p class="font-bold text-indigo-950">Copago familiar estimado</p>
                    <p id="copago-estimado" class="mt-1 text-lg font-extrabold text-indigo-900">—</p>
                    <p id="convenio-ayuda" class="mt-1 text-[11px] text-slate-600" aria-live="polite"></p>
                </div>
            </div>
        </section>

        <section class="panel">
            <h2 class="font-serif-title text-lg font-bold text-slate-900"><i class="fa-solid fa-van-shuttle text-indigo-700 mr-1.5"></i>Cortejo y Traslado Automático</h2>
            <div class="mt-4 grid gap-4 md:grid-cols-3">
                <label class="block">
                    <span class="label">Fecha y hora de cortejo</span>
                    <input class="field" type="datetime-local" name="cortejo_at" value="{{ old('cortejo_at', $servicio->cortejo_at?->format('Y-m-d\TH:i')) }}">
                </label>
                <label class="block">
                    <span class="label">Cementerio / Destino</span>
                    <input class="field" name="destino" value="{{ old('destino', $servicio->destino) }}" placeholder="Ej: Sector Rural Cunco / Cementerio General">
                </label>
                <label class="block">
                    <span class="label">Estado del servicio</span>
                    <select class="field" name="estado">
                        @foreach(\App\Models\Servicio::ESTADOS as $value => $label)
                            <option value="{{ $value }}" @selected(old('estado', $servicio->estado ?? 'abierto') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
            </div>
        </section>

        <section class="panel">
            <h2 class="font-serif-title text-lg font-bold text-slate-900"><i class="fa-solid fa-cloud-arrow-up text-rose-600 mr-1.5"></i>Documentos de Respaldo (Bucket Privado Amazon S3)</h2>
            <p class="mt-1 text-xs text-slate-500">Archivos PDF, JPG o PNG de hasta 15 MB. Alimentan automáticamente el Semáforo Documental (&lt; 3 hrs).</p>
            <div class="mt-4 grid gap-4 md:grid-cols-3">
                @foreach(\App\Models\DocumentoServicio::TIPOS as $index => $label)
                    <label class="block rounded-xl border border-slate-200 bg-slate-50/60 p-3">
                        <span class="label">{{ $label }}</span>
                        <input type="hidden" name="tipos_documentos[{{ $loop->index }}]" value="{{ $index }}">
                        <input class="field bg-white mt-2" type="file" name="documentos[{{ $loop->index }}]" accept=".pdf,.jpg,.jpeg,.png">
                    </label>
                @endforeach
            </div>
        </section>

        <div class="flex flex-wrap justify-end gap-3">
            <a class="button-secondary" href="{{ route('servicios.index') }}">Cancelar</a>
            <button class="button-primary">
                <i class="fa-solid fa-floppy-disk mr-1.5"></i>{{ $servicio->exists ? 'Guardar cambios' : 'Crear expediente' }}
            </button>
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
        const ipsAporte = @json(\App\Models\Servicio::APORTE_IPS_CLP);
        const afpUf = @json(\App\Models\Servicio::APORTE_AFP_UF);
        const pesos = value => new Intl.NumberFormat('es-CL', { style: 'currency', currency: 'CLP', maximumFractionDigits: 0 }).format(value);

        function actualizarCopago() {
            const amount = Number(total.value || 0);
            let aporte = 0;
            if (convenio.value === 'ips') {
                aporte = ipsAporte;
                ayuda.textContent = `Aporte de referencia: ${pesos(aporte)}.`;
            } else if (convenio.value === 'afp' && ufValue) {
                aporte = afpUf * ufValue;
                ayuda.textContent = `${afpUf} UF calculadas con el valor configurado de ${pesos(ufValue)} por UF.`;
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

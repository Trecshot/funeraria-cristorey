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
                    <p class="text-xs text-slate-500">Datos del contratante, antecedentes para Registro Civil y archivo documental legal.</p>
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
            <h2 class="font-serif-title text-lg font-bold text-slate-900"><i class="fa-solid fa-box-archive text-indigo-700 mr-1.5"></i>Logística Interna: Asignación de Urna</h2>
            <p class="mt-1 text-xs text-slate-500">Selecciona el modelo de urna desde el inventario dual (Salón Calle Blanco o Taller de Ensamblado).</p>
            <div class="mt-4 grid gap-4 md:grid-cols-2">
                <label class="block">
                    <span class="label">Urna desde bodega</span>
                    <select class="field" name="urna_id">
                        <option value="">-- Sin urna asignada (Pendiente) --</option>
                        @foreach($urnas ?? [] as $u)
                            <option value="{{ $u->id }}" @selected((string) old('urna_id', $servicio->urna_id) === (string) $u->id)>
                                {{ $u->modelo }} · {{ $u->color }} ({{ $u->categoria_etaria }}) · Stock: {{ $u->stock_salon }} Salón / {{ $u->stock_taller }} Taller
                            </option>
                        @endforeach
                    </select>
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

        <!-- SECCIÓN: DOCUMENTACIÓN LEGAL Y ANEXOS -->
        <section class="panel space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 pb-3">
                <div>
                    <h2 class="font-serif-title text-lg font-bold text-slate-900 flex items-center">
                        <i class="fa-solid fa-file-contract text-indigo-700 mr-2"></i>Documentación Legal y Anexos
                    </h2>
                    <p class="mt-1 text-xs text-slate-500">
                        Formatos admitidos: PDF, JPG o PNG (Máx. 15 MB por archivo). La carga exitosa de estos documentos actualiza en tiempo real el Semáforo de Viabilidad Legal (SLA &lt; 3 hrs).
                    </p>
                </div>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                    <i class="fa-solid fa-shield-halved text-[10px]"></i> Expediente Digital Seguro
                </span>
            </div>

            @php
                $documentosExistentes = $servicio->exists ? $servicio->documentos->keyBy('tipo') : collect();
                $requeridos = $servicio->documentosRequeridos();
            @endphp

            <div class="grid gap-4 md:grid-cols-3">
                @foreach(\App\Models\DocumentoServicio::TIPOS as $tipoKey => $tipoTitulo)
                    @php
                        $doc = $documentosExistentes->get($tipoKey);
                        $esRequerido = in_array($tipoKey, $requeridos, true) || $tipoKey === 'resolucion_seremi';
                        $iconoTipo = match($tipoKey) {
                            'certificado_defuncion' => 'fa-file-lines text-rose-500',
                            'resolucion_seremi' => 'fa-file-shield text-indigo-600',
                            'oficio_fiscalia_sml' => 'fa-building-columns text-amber-600',
                            default => 'fa-file text-slate-500',
                        };
                    @endphp

                    <div id="card-doc-{{ $tipoKey }}" class="rounded-2xl border p-4 transition-all duration-200 flex flex-col justify-between space-y-3 {{ $doc ? 'border-emerald-200 bg-emerald-50/20' : 'border-slate-200 bg-slate-50/50 hover:bg-white' }}">
                        
                        <!-- ENCABEZADO DE TARJETA -->
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="w-8 h-8 rounded-lg flex items-center justify-center bg-white shadow-xs border border-slate-100 flex-shrink-0">
                                    <i class="fa-solid {{ $iconoTipo }} text-base"></i>
                                </span>
                                <div class="min-w-0">
                                    <h3 class="text-xs font-bold text-slate-900 truncate" title="{{ $tipoTitulo }}">{{ $tipoTitulo }}</h3>
                                    <span class="text-[10px] text-slate-400 block">{{ $loop->iteration }} de 3</span>
                                </div>
                            </div>

                            @if($doc)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200 flex-shrink-0">
                                    <i class="fa-solid fa-check text-[9px]"></i> Documento adjunto
                                </span>
                            @elseif($esRequerido)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-900 border border-amber-300 flex-shrink-0" title="Requerido para viabilidad legal">
                                    <i class="fa-solid fa-triangle-exclamation text-amber-600 text-[9px]"></i> Requerido
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-medium bg-slate-100 text-slate-600 border border-slate-200 flex-shrink-0">
                                    <i class="fa-regular fa-circle text-slate-400 text-[8px]"></i> Opcional
                                </span>
                            @endif
                        </div>

                        <!-- CUERPO DE LA TARJETA -->
                        @if($doc)
                            <!-- ESTADO 1: CARGADO / VERIFICADO -->
                            <div class="bg-white rounded-xl p-3 border border-emerald-100 shadow-xs space-y-2">
                                <div class="flex items-center gap-2.5">
                                    <i class="fa-solid {{ str_ends_with(strtolower($doc->nombre_original), '.pdf') ? 'fa-file-pdf text-rose-500' : 'fa-file-image text-emerald-600' }} text-2xl flex-shrink-0"></i>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-xs font-semibold text-slate-800 truncate" title="{{ $doc->nombre_original }}">
                                            {{ $doc->nombre_original }}
                                        </p>
                                        <p class="text-[10px] text-slate-400 mt-0.5">
                                            {{ $doc->pesoFormateado() ?: 'Archivo guardado' }}
                                            @if($doc->usuario)
                                                · Subido por {{ $doc->usuario->name }}
                                            @endif
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-center justify-between gap-1 pt-2 border-t border-slate-100">
                                    <a href="{{ route('servicios.documentos.descargar', [$servicio, $doc, 'inline' => 1]) }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center px-2 py-1 rounded-lg text-[11px] font-semibold text-indigo-700 bg-indigo-50 hover:bg-indigo-100 transition" title="Previsualizar documento en nueva pestaña">
                                        <i class="fa-regular fa-eye mr-1"></i>Ver Archivo
                                    </a>
                                    <div class="flex items-center gap-1">
                                        <button type="button" onclick="toggleReemplazar('{{ $tipoKey }}')" class="inline-flex items-center px-2 py-1 rounded-lg text-[11px] font-medium text-slate-600 bg-slate-100 hover:bg-slate-200 transition" title="Seleccionar un nuevo archivo para sustituir el actual">
                                            <i class="fa-solid fa-arrows-rotate mr-1"></i>Reemplazar
                                        </button>
                                        <button type="button" onclick="confirmarEliminarDoc({{ $doc->id }}, '{{ addslashes($tipoTitulo) }}')" class="inline-flex items-center px-2 py-1 rounded-lg text-[11px] font-medium text-rose-600 bg-rose-50 hover:bg-rose-100 transition" title="Eliminar documento del expediente">
                                            <i class="fa-regular fa-trash-can"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- ÁREA DE REEMPLAZO (OCULTA INICIALMENTE) -->
                            <div id="box-reemplazo-{{ $tipoKey }}" class="hidden pt-2 border-t border-emerald-100 space-y-2">
                                <div class="flex items-center justify-between">
                                    <span class="text-[10px] font-semibold text-indigo-900">Seleccionar nuevo archivo de reemplazo:</span>
                                    <button type="button" onclick="toggleReemplazar('{{ $tipoKey }}')" class="text-[10px] text-slate-400 hover:text-slate-600">Cancelar</button>
                                </div>
                                <div class="dropzone border-2 border-dashed border-indigo-200 hover:border-indigo-400 bg-indigo-50/20 rounded-xl p-3 text-center cursor-pointer transition flex flex-col items-center justify-center relative"
                                     data-tipo="{{ $tipoKey }}"
                                     onclick="document.getElementById('input-doc-{{ $tipoKey }}').click()">
                                    <i class="fa-solid fa-cloud-arrow-up text-lg text-indigo-500"></i>
                                    <p class="text-[11px] text-slate-500 mt-1">Arrastra el archivo aquí o</p>
                                    <span class="mt-1 inline-flex items-center px-2.5 py-1 rounded-lg text-[10px] font-semibold text-indigo-700 bg-white border border-indigo-200 shadow-xs">
                                        <i class="fa-solid fa-arrow-up-from-bracket mr-1"></i>Seleccionar Archivo
                                    </span>
                                </div>
                            </div>
                        @else
                            <!-- ESTADO 2: PENDIENTE (DRAG & DROP) -->
                            <div class="dropzone border-2 border-dashed border-slate-200 hover:border-indigo-400 hover:bg-indigo-50/20 bg-white rounded-xl p-4 text-center cursor-pointer transition flex flex-col items-center justify-center min-h-[110px] relative group"
                                 id="dropzone-{{ $tipoKey }}"
                                 data-tipo="{{ $tipoKey }}"
                                 onclick="document.getElementById('input-doc-{{ $tipoKey }}').click()">
                                <i class="fa-solid fa-cloud-arrow-up text-2xl text-slate-400 group-hover:text-indigo-600 transition"></i>
                                <p class="mt-1.5 text-xs text-slate-600 font-medium">Arrastra el archivo aquí o</p>
                                <span class="mt-1.5 inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-semibold text-indigo-700 bg-indigo-50 group-hover:bg-indigo-100 border border-indigo-200 transition shadow-xs">
                                    <i class="fa-solid fa-arrow-up-from-bracket mr-1.5"></i>Seleccionar Archivo
                                </span>
                            </div>
                        @endif

                        <!-- INPUT REAL (OCULTO) -->
                        <input type="hidden" name="tipos_documentos[{{ $loop->index }}]" value="{{ $tipoKey }}">
                        <input type="file"
                               id="input-doc-{{ $tipoKey }}"
                               name="documentos[{{ $loop->index }}]"
                               accept=".pdf,.jpg,.jpeg,.png"
                               class="hidden input-archivo-documento"
                               data-tipo="{{ $tipoKey }}">

                        <!-- ESTADO DE PREVISUALIZACIÓN DE ARCHIVO SELECCIONADO (JS) -->
                        <div id="preview-box-{{ $tipoKey }}" class="hidden bg-indigo-50/70 border border-indigo-200 rounded-xl p-2.5 text-xs space-y-1.5">
                            <div class="flex items-center justify-between gap-2">
                                <div class="flex items-center gap-2 min-w-0">
                                    <i id="preview-icon-{{ $tipoKey }}" class="fa-solid fa-file-circle-check text-indigo-700 text-base flex-shrink-0"></i>
                                    <span id="preview-nombre-{{ $tipoKey }}" class="font-semibold text-indigo-950 truncate block text-[11px]"></span>
                                </div>
                                <button type="button" onclick="cancelarSeleccionArchivo('{{ $tipoKey }}')" class="text-slate-400 hover:text-rose-600 text-[10px] font-bold px-1" title="Quitar archivo seleccionado">
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                            </div>
                            <div class="flex items-center justify-between text-[10px] text-indigo-800">
                                <span id="preview-peso-{{ $tipoKey }}"></span>
                                <span class="font-semibold text-emerald-700 flex items-center gap-1">
                                    <i class="fa-solid fa-check text-[9px]"></i> Listo para subir
                                </span>
                            </div>
                        </div>

                        <!-- ESTADO 3: MENSAJE DE ERROR DE VALIDACIÓN (JS) -->
                        <div id="error-box-{{ $tipoKey }}" class="hidden bg-rose-50 border border-rose-200 rounded-xl p-2.5 text-[11px] text-rose-700 flex items-start gap-1.5">
                            <i class="fa-solid fa-triangle-exclamation text-rose-500 mt-0.5 flex-shrink-0"></i>
                            <span id="error-texto-{{ $tipoKey }}"></span>
                        </div>
                    </div>
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

    @if($servicio->exists)
        @foreach($servicio->documentos as $documento)
            <form id="delete-doc-form-{{ $documento->id }}" action="{{ route('servicios.documentos.destroy', [$servicio, $documento]) }}" method="POST" class="hidden">
                @csrf
                @method('DELETE')
            </form>
        @endforeach
    @endif
@endsection

@push('scripts')
<script>
    function toggleReemplazar(tipo) {
        const box = document.getElementById(`box-reemplazo-${tipo}`);
        if (box) {
            box.classList.toggle('hidden');
        }
    }

    function confirmarEliminarDoc(id, tipoLabel) {
        if (confirm(`¿Estás seguro de que deseas eliminar el documento "${tipoLabel}"? Esta acción eliminará el archivo del expediente.`)) {
            const form = document.getElementById(`delete-doc-form-${id}`);
            if (form) form.submit();
        }
    }

    function cancelarSeleccionArchivo(tipo) {
        const input = document.getElementById(`input-doc-${tipo}`);
        if (input) input.value = '';

        const previewBox = document.getElementById(`preview-box-${tipo}`);
        if (previewBox) previewBox.classList.add('hidden');

        const errorBox = document.getElementById(`error-box-${tipo}`);
        if (errorBox) errorBox.classList.add('hidden');

        const card = document.getElementById(`card-doc-${tipo}`);
        if (card) {
            card.classList.remove('ring-2', 'ring-indigo-400', 'ring-rose-400');
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        // CÁLCULO DE COPAGOS
        const convenio = document.getElementById('convenio');
        const total = document.getElementById('total-servicio');
        const copago = document.getElementById('copago-estimado');
        const ayuda = document.getElementById('convenio-ayuda');
        const ufValue = @json($ufValue ? (float) $ufValue : null);
        const ipsAporte = @json(\App\Models\Servicio::APORTE_IPS_CLP);
        const afpUf = @json(\App\Models\Servicio::APORTE_AFP_UF);
        const pesos = value => new Intl.NumberFormat('es-CL', { style: 'currency', currency: 'CLP', maximumFractionDigits: 0 }).format(value);

        function actualizarCopago() {
            if (!convenio || !total || !copago || !ayuda) return;
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

        if (convenio && total) {
            convenio.addEventListener('change', actualizarCopago);
            total.addEventListener('input', actualizarCopago);
            actualizarCopago();
        }

        // VALIDACIÓN INTERACTIVA DE DOCUMENTOS (DRAG & DROP + ERROR > 15MB + FORMATO)
        const MAX_SIZE_BYTES = 15 * 1024 * 1024; // 15 MB
        const ALLOWED_EXTS = ['.pdf', '.jpg', '.jpeg', '.png'];
        const ALLOWED_MIMES = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'];

        function validarYPrevisualizar(input, file) {
            const tipo = input.dataset.tipo;
            const previewBox = document.getElementById(`preview-box-${tipo}`);
            const errorBox = document.getElementById(`error-box-${tipo}`);
            const errorTexto = document.getElementById(`error-texto-${tipo}`);
            const previewNombre = document.getElementById(`preview-nombre-${tipo}`);
            const previewPeso = document.getElementById(`preview-peso-${tipo}`);
            const previewIcon = document.getElementById(`preview-icon-${tipo}`);
            const card = document.getElementById(`card-doc-${tipo}`);

            if (!file) {
                if (previewBox) previewBox.classList.add('hidden');
                if (errorBox) errorBox.classList.add('hidden');
                return;
            }

            const ext = '.' + file.name.split('.').pop().toLowerCase();
            const esExtValida = ALLOWED_EXTS.includes(ext);
            const esMimeValido = ALLOWED_MIMES.includes(file.type);

            if (!esExtValida && !esMimeValido) {
                input.value = '';
                if (previewBox) previewBox.classList.add('hidden');
                if (errorBox && errorTexto) {
                    errorTexto.textContent = `Formato no permitido (${ext || 'desconocido'}). Solo se admiten archivos PDF, JPG o PNG.`;
                    errorBox.classList.remove('hidden');
                }
                if (card) {
                    card.classList.add('ring-2', 'ring-rose-400');
                    card.classList.remove('ring-indigo-400');
                }
                return;
            }

            if (file.size > MAX_SIZE_BYTES) {
                const pesoMb = (file.size / (1024 * 1024)).toFixed(1);
                input.value = '';
                if (previewBox) previewBox.classList.add('hidden');
                if (errorBox && errorTexto) {
                    errorTexto.textContent = `El archivo supera los 15 MB permitidos (peso actual: ${pesoMb} MB). Por favor comprímelo antes de subir.`;
                    errorBox.classList.remove('hidden');
                }
                if (card) {
                    card.classList.add('ring-2', 'ring-rose-400');
                    card.classList.remove('ring-indigo-400');
                }
                return;
            }

            if (errorBox) errorBox.classList.add('hidden');
            if (previewBox) {
                if (previewNombre) previewNombre.textContent = file.name;
                const pesoStr = file.size >= 1048576 
                    ? (file.size / (1024 * 1024)).toFixed(1) + ' MB'
                    : Math.round(file.size / 1024) + ' KB';
                if (previewPeso) previewPeso.textContent = `Tamaño: ${pesoStr}`;
                
                if (previewIcon) {
                    previewIcon.className = ext === '.pdf' 
                        ? 'fa-solid fa-file-pdf text-rose-500 text-base flex-shrink-0'
                        : 'fa-solid fa-file-image text-emerald-600 text-base flex-shrink-0';
                }
                previewBox.classList.remove('hidden');
            }

            if (card) {
                card.classList.remove('ring-rose-400');
                card.classList.add('ring-2', 'ring-indigo-400');
            }
        }

        document.querySelectorAll('.input-archivo-documento').forEach(input => {
            input.addEventListener('change', (e) => {
                validarYPrevisualizar(input, e.target.files[0]);
            });
        });

        document.querySelectorAll('.dropzone').forEach(dropzone => {
            const tipo = dropzone.dataset.tipo;
            const input = document.getElementById(`input-doc-${tipo}`);

            ['dragenter', 'dragover'].forEach(eventName => {
                dropzone.addEventListener(eventName, (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    dropzone.classList.add('border-indigo-500', 'bg-indigo-50/50');
                });
            });

            ['dragleave', 'drop'].forEach(eventName => {
                dropzone.addEventListener(eventName, (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    dropzone.classList.remove('border-indigo-500', 'bg-indigo-50/50');
                });
            });

            dropzone.addEventListener('drop', (e) => {
                const files = e.dataTransfer.files;
                if (files && files.length > 0 && input) {
                    input.files = files;
                    validarYPrevisualizar(input, files[0]);
                }
            });
        });
    });
</script>
@endpush

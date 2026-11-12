@extends('layouts.app')

@section('title', 'Reportes y Fiscalización')
@section('heading', 'Reportes Normativos y Fiscalización Sanitaria (Seremi / Registro Civil)')

@section('content')
    <div class="space-y-6">

        <!-- TARJETAS DE RESUMEN RÁPIDO POR CONTEXTO LEGAL -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
            <div class="bg-white rounded-2xl p-4 shadow-sm border border-slate-100 flex items-center justify-between">
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Total Expedientes</p>
                    <p class="text-2xl font-bold text-slate-900 mt-1">{{ $metricas['total'] }}</p>
                    <span class="text-[10px] text-slate-500">Base histórica</span>
                </div>
                <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-700 flex items-center justify-center text-lg">
                    <i class="fa-solid fa-folder-tree"></i>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-4 shadow-sm border border-emerald-100 flex items-center justify-between">
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-wider text-emerald-700">Muerte Natural</p>
                    <p class="text-2xl font-bold text-emerald-900 mt-1">{{ $metricas['naturales'] }}</p>
                    <span class="text-[10px] text-emerald-600">Certificado Médico</span>
                </div>
                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg">
                    <i class="fa-solid fa-notes-medical"></i>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-4 shadow-sm border border-rose-100 flex items-center justify-between">
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-wider text-rose-700">SML / Violenta</p>
                    <p class="text-2xl font-bold text-rose-900 mt-1">{{ $metricas['sml'] }}</p>
                    <span class="text-[10px] text-rose-600">Parte de Fiscalía</span>
                </div>
                <div class="w-10 h-10 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center text-lg">
                    <i class="fa-solid fa-gavel"></i>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-4 shadow-sm border border-amber-100 flex items-center justify-between">
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-wider text-amber-800">Zona Rural</p>
                    <p class="text-2xl font-bold text-amber-900 mt-1">{{ $metricas['rurales'] }}</p>
                    <span class="text-[10px] text-amber-700">Acta 2 Testigos</span>
                </div>
                <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-lg">
                    <i class="fa-solid fa-users-viewfinder"></i>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-4 shadow-sm border border-indigo-100 flex items-center justify-between col-span-2 sm:col-span-1">
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-wider text-indigo-700">Interurbanos</p>
                    <p class="text-2xl font-bold text-indigo-900 mt-1">{{ $metricas['interurbanos'] }}</p>
                    <span class="text-[10px] text-indigo-600">DTO 357 Seremi</span>
                </div>
                <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-lg">
                    <i class="fa-solid fa-road"></i>
                </div>
            </div>
        </div>

        <!-- GENERADOR DE INFORMES NORMATIVOS (MOCKUP ESTRUCTURAL ADAPTADO) -->
        <section class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="bg-gradient-to-r from-indigo-950 via-indigo-900 to-slate-900 px-6 py-4 text-white flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-xl bg-white/10 border border-white/20 flex items-center justify-center text-lg">
                        <i class="fa-solid fa-chart-column text-amber-300"></i>
                    </div>
                    <div>
                        <h3 class="font-serif-title text-lg font-bold text-white">📊 Generador de Informes Normativos (Seremi / Registro Civil)</h3>
                        <p class="text-xs text-indigo-200">Exportación optimizada por bloques (<code class="text-amber-200">StreamedResponse + Chunking</code>) y protección de datos personales.</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 self-start sm:self-center">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-200 border border-emerald-400/30">
                        <i class="fa-solid fa-shield-halved"></i> Ley N° 21.719 Activa
                    </span>
                </div>
            </div>

            <form method="POST" action="{{ route('reportes.exportar') }}" class="p-6 space-y-6">
                @csrf

                <!-- PASO 1: SELECCIONAR RANGO DE FECHAS -->
                <div class="bg-slate-50/70 rounded-2xl p-5 border border-slate-200/80 space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-200 pb-2.5">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-indigo-950 flex items-center">
                            <span class="w-5 h-5 rounded-full bg-indigo-900 text-white inline-flex items-center justify-center text-[10px] mr-2">1</span>
                            Seleccionar Rango de Fechas
                        </h4>
                        <span class="text-[11px] text-slate-500">Acota la consulta para optimizar el rendimiento del servidor</span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label for="rango_predefinido" class="form-label">Rango Predefinido</label>
                            <select id="rango_predefinido" onchange="aplicarRangoPredefinido(this.value)" class="form-input font-medium">
                                <option value="anio_2026" selected>Año Completo 2026 (01/01/2026 - 31/12/2026)</option>
                                <option value="mes_actual">Mes Actual ({{ ucfirst(now()->translatedFormat('F Y')) }})</option>
                                <option value="q1_2026">Primer Trimestre 2026 (Ene - Mar)</option>
                                <option value="q2_2026">Segundo Trimestre 2026 (Abr - Jun)</option>
                                <option value="q3_2026">Tercer Trimestre 2026 (Jul - Sep)</option>
                                <option value="q4_2026">Cuarto Trimestre 2026 (Oct - Dic)</option>
                                <option value="ultimos_30">Últimos 30 días</option>
                                <option value="personalizado">Rango Personalizado</option>
                            </select>
                        </div>

                        <div>
                            <label for="fecha_inicio" class="form-label">Fecha Inicio <span class="text-rose-600">*</span></label>
                            <div class="relative">
                                <input
                                    type="date"
                                    id="fecha_inicio"
                                    name="fecha_inicio"
                                    value="{{ old('fecha_inicio', now()->startOfYear()->format('Y-m-d')) }}"
                                    required
                                    onchange="document.getElementById('rango_predefinido').value = 'personalizado'"
                                    class="form-input"
                                >
                            </div>
                        </div>

                        <div>
                            <label for="fecha_fin" class="form-label">Fecha Fin <span class="text-rose-600">*</span></label>
                            <div class="relative">
                                <input
                                    type="date"
                                    id="fecha_fin"
                                    name="fecha_fin"
                                    value="{{ old('fecha_fin', now()->endOfYear()->format('Y-m-d')) }}"
                                    required
                                    onchange="document.getElementById('rango_predefinido').value = 'personalizado'"
                                    class="form-input"
                                >
                            </div>
                        </div>
                    </div>
                </div>

                <!-- PASO 2: FILTRO POR CAUSAL Y CONTEXTO -->
                <div class="bg-slate-50/70 rounded-2xl p-5 border border-slate-200/80 space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-200 pb-2.5">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-indigo-950 flex items-center">
                            <span class="w-5 h-5 rounded-full bg-indigo-900 text-white inline-flex items-center justify-center text-[10px] mr-2">2</span>
                            Filtro por Causal y Modalidad de Traslado (Opcional)
                        </h4>
                        <span class="text-[11px] text-slate-500">Matriz de Contextos de Deceso</span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-center">
                        <div>
                            <label for="contexto" class="form-label">Contexto del Deceso</label>
                            <select id="contexto" name="contexto" class="form-input font-semibold">
                                <option value="TODOS" @selected(old('contexto') === 'TODOS')>📋 Todos los Contextos (Consolidado General)</option>
                                <option value="NATURAL" @selected(old('contexto') === 'NATURAL')>🟢 Solo Muerte Natural (Certificado Médico de Defunción)</option>
                                <option value="SML_VIOLENTO" @selected(old('contexto') === 'SML_VIOLENTO')>🔴 Solo SML / Violenta (Orden de Autopsia / Fiscalía)</option>
                                <option value="RURAL_TESTIGOS" @selected(old('contexto') === 'RURAL_TESTIGOS')>🌾 Solo Traslados Rurales (Inscripción con 2 Testigos)</option>
                            </select>
                        </div>

                        <div class="pt-2 md:pt-5">
                            <label class="flex items-start space-x-3 p-3 rounded-xl bg-white border border-slate-200 cursor-pointer hover:border-indigo-300 transition">
                                <input
                                    type="checkbox"
                                    name="solo_interurbano"
                                    value="1"
                                    @checked(old('solo_interurbano'))
                                    class="mt-0.5 rounded border-slate-300 text-indigo-700 focus:ring-indigo-500"
                                >
                                <div class="text-xs">
                                    <span class="font-bold text-slate-800 block"><i class="fa-solid fa-road text-indigo-600 mr-1"></i>Solo Traslados Interurbanos (Art. 75 DTO 357)</span>
                                    <span class="text-[11px] text-slate-500">Filtra exclusivamente servicios con Resolución Sanitaria de Seremi de Salud.</span>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- PASO 3: CUMPLIMIENTO LEGAL LEY 21.719 (ANONIMIZACIÓN DE DATOS) -->
                <div class="bg-gradient-to-r from-indigo-50/60 to-emerald-50/40 rounded-2xl p-5 border border-indigo-200/80 space-y-3">
                    <div class="flex items-center justify-between border-b border-indigo-200/60 pb-2.5">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-indigo-950 flex items-center">
                            <span class="w-5 h-5 rounded-full bg-emerald-700 text-white inline-flex items-center justify-center text-[10px] mr-2">3</span>
                            Privacidad y Protección de Datos Personales (Ley N° 21.719)
                        </h4>
                        <span class="text-[10px] font-bold text-emerald-800 bg-emerald-100 px-2 py-0.5 rounded-full">Data Privacy Control</span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <label class="flex items-start space-x-3 p-3.5 rounded-xl bg-white border-2 border-indigo-200 cursor-pointer hover:border-indigo-500 transition">
                            <input
                                type="radio"
                                name="anonimizado"
                                value="0"
                                @checked(!old('anonimizado'))
                                class="mt-1 text-indigo-700 focus:ring-indigo-600"
                            >
                            <div class="text-xs">
                                <span class="font-bold text-slate-900 flex items-center gap-1.5">
                                    <i class="fa-solid fa-building-shield text-indigo-700"></i>
                                    Fiscalización Nominal (Seremi / Registro Civil)
                                </span>
                                <p class="text-[11px] text-slate-600 mt-1">
                                    Incluye <strong>RUT Difunto</strong>, <strong>Nombre Difunto</strong>, <strong>Folio Certificado Médico</strong> y <strong>Pase de Sepultación</strong> (descifrados desde AES-256) para auditorías sanitarias oficiales.
                                </p>
                            </div>
                        </label>

                        <label class="flex items-start space-x-3 p-3.5 rounded-xl bg-white border-2 border-emerald-200 cursor-pointer hover:border-emerald-500 transition">
                            <input
                                type="radio"
                                name="anonimizado"
                                value="1"
                                @checked(old('anonimizado') == '1')
                                class="mt-1 text-emerald-700 focus:ring-emerald-600"
                            >
                            <div class="text-xs">
                                <span class="font-bold text-slate-900 flex items-center gap-1.5">
                                    <i class="fa-solid fa-user-secret text-emerald-700"></i>
                                    Estadística General Anonimizada (Ley N° 21.719)
                                </span>
                                <p class="text-[11px] text-slate-600 mt-1">
                                    <strong>Omite RUT y Nombre del Difunto</strong>. Entrega exclusivamente el Folio del Expediente, Fecha de Registro, Causal/Contexto y Convenio para análisis estadístico preservando la privacidad clínica.
                                </p>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- BOTONES DE ACCIÓN (CSV STREAM & PDF RESUMEN) -->
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-2 border-t border-slate-100">
                    <div class="text-[11px] text-slate-500 flex items-center gap-2">
                        <i class="fa-solid fa-circle-info text-indigo-600"></i>
                        <span>El archivo CSV incluye codificación <strong>UTF-8 con BOM</strong> para apertura directa en Microsoft Excel sin pérdida de tildes ni eñes.</span>
                    </div>

                    <div class="flex flex-wrap items-center gap-3 w-full sm:w-auto justify-end">
                        <button
                            type="submit"
                            name="formato"
                            value="csv"
                            class="button-primary bg-emerald-700 hover:bg-emerald-800 inline-flex items-center justify-center px-5 py-2.5 text-xs font-bold shadow-sm transition cursor-pointer"
                        >
                            <i class="fa-solid fa-file-csv text-sm mr-2"></i>📥 Generar Reporte CSV
                        </button>

                        <button
                            type="submit"
                            name="formato"
                            value="pdf"
                            formtarget="_blank"
                            class="button-secondary border-indigo-200 bg-indigo-50 text-indigo-900 hover:bg-indigo-100 inline-flex items-center justify-center px-5 py-2.5 text-xs font-bold shadow-xs transition cursor-pointer"
                        >
                            <i class="fa-solid fa-file-pdf text-rose-600 text-sm mr-2"></i>📄 Generar PDF Resumen
                        </button>
                    </div>
                </div>
            </form>
        </section>

        <!-- VISTA PREVIA DE EXPEDIENTES RECIENTES PARA VERIFICACIÓN RÁPIDA -->
        <section class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h3 class="font-serif-title text-base font-bold text-slate-900">Vista Previa de Expedientes Registrados</h3>
                    <p class="text-xs text-slate-500">Últimos expedientes disponibles en la base de datos para exportación normativa.</p>
                </div>
                <a href="{{ route('servicios.index') }}" class="text-xs font-bold text-indigo-700 hover:underline">
                    Ir a todos los servicios <i class="fa-solid fa-arrow-right ml-1 text-[10px]"></i>
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-xs">
                    <thead class="bg-slate-50 text-left text-[10px] uppercase tracking-wide text-slate-600 font-bold">
                        <tr>
                            <th class="px-4 py-3">Folio Expediente</th>
                            <th class="px-4 py-3">Fecha Registro</th>
                            <th class="px-4 py-3">Difunto (Causante)</th>
                            <th class="px-4 py-3">Contexto Deceso</th>
                            <th class="px-4 py-3">Resolución / Certificado (Cifrado)</th>
                            <th class="px-4 py-3">Traslado Interurbano</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($expedientesRecientes as $exp)
                            @php $doc = $exp->documentoLegal; @endphp
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="px-4 py-3 font-mono font-bold text-indigo-950">
                                    <a href="{{ route('servicios.show', $exp) }}" class="hover:underline">{{ $exp->folio }}</a>
                                </td>
                                <td class="px-4 py-3 text-slate-600 font-mono">{{ $exp->created_at?->format('d/m/Y H:i') }}</td>
                                <td class="px-4 py-3">
                                    <span class="font-bold text-slate-900">{{ $exp->difunto_nombre }}</span>
                                    <span class="block text-[10px] text-slate-400 font-mono">RUT: {{ $exp->difunto_rut ?: 'S/R' }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    @if($doc?->esNatural())
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">🟢 NATURAL</span>
                                    @elseif($doc?->esSmlViolento())
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-800 border border-rose-200">🔴 SML_VIOLENTO</span>
                                    @elseif($doc?->esRural())
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-900 border border-amber-200">🌾 RURAL_TESTIGOS</span>
                                    @else
                                        <span class="text-slate-400 italic">No registrado</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 font-mono text-[11px]">
                                    @if($doc?->folio_certificado_medico)
                                        <span class="text-emerald-800 font-bold"><i class="fa-solid fa-lock text-[9px] text-slate-400 mr-1"></i>{{ $doc->folio_certificado_medico }}</span>
                                    @elseif($doc?->esSmlViolento())
                                        <span class="text-rose-700 font-sans font-semibold"><i class="fa-solid fa-file-pdf mr-1"></i>Oficio Fiscalía SML</span>
                                    @elseif($doc?->esRural())
                                        <span class="text-amber-800 font-sans font-semibold"><i class="fa-solid fa-users mr-1"></i>Acta {{ is_array($doc->testigos_rurales) ? count($doc->testigos_rurales) : 2 }} Testigos</span>
                                    @else
                                        <span class="text-slate-400">N/A</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if($doc?->traslado_interurbano)
                                        <span class="text-indigo-700 font-bold text-[11px]"><i class="fa-solid fa-road mr-1"></i>Sí (Resolución Seremi)</span>
                                    @else
                                        <span class="text-slate-400 text-[11px]">No (Local)</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-slate-400">No hay expedientes registrados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    @push('scripts')
    <script>
        function aplicarRangoPredefinido(rango) {
            const inputInicio = document.getElementById('fecha_inicio');
            const inputFin = document.getElementById('fecha_fin');
            const hoy = new Date();
            const anio = hoy.getFullYear();

            const pad = (n) => String(n).padStart(2, '0');
            const formatDate = (d) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;

            switch (rango) {
                case 'anio_2026':
                    inputInicio.value = `${anio}-01-01`;
                    inputFin.value = `${anio}-12-31`;
                    break;
                case 'mes_actual':
                    const primerDiaMes = new Date(anio, hoy.getMonth(), 1);
                    const ultimoDiaMes = new Date(anio, hoy.getMonth() + 1, 0);
                    inputInicio.value = formatDate(primerDiaMes);
                    inputFin.value = formatDate(ultimoDiaMes);
                    break;
                case 'q1_2026':
                    inputInicio.value = `${anio}-01-01`;
                    inputFin.value = `${anio}-03-31`;
                    break;
                case 'q2_2026':
                    inputInicio.value = `${anio}-04-01`;
                    inputFin.value = `${anio}-06-30`;
                    break;
                case 'q3_2026':
                    inputInicio.value = `${anio}-07-01`;
                    inputFin.value = `${anio}-09-30`;
                    break;
                case 'q4_2026':
                    inputInicio.value = `${anio}-10-01`;
                    inputFin.value = `${anio}-12-31`;
                    break;
                case 'ultimos_30':
                    const hace30 = new Date(hoy);
                    hace30.setDate(hoy.getDate() - 30);
                    inputInicio.value = formatDate(hace30);
                    inputFin.value = formatDate(hoy);
                    break;
                default:
                    break;
            }
        }
    </script>
    @endpush
@endsection

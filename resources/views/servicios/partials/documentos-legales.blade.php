<!-- COLUMNA DERECHA: HITO 3: DOCUMENTACIÓN LEGAL Y ANEXOS -->
<div class="space-y-6">
    <!-- MATRIZ LEGAL DE DECESO (LEY 21.719 / CÓDIGO SANITARIO) -->
    @php
        $docLegal = $servicio->documentoLegal;
    @endphp
    @if($docLegal)
        <section class="panel space-y-3 bg-gradient-to-br from-white to-slate-50/50 border border-slate-200 shadow-xs">
            <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                <h3 class="font-serif-title text-sm font-bold text-slate-900 flex items-center">
                    <i class="fa-solid fa-scale-balanced text-indigo-700 mr-2"></i>Matriz Legal de Deceso
                </h3>
                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-indigo-50 text-indigo-800 border border-indigo-200">
                    <i class="fa-solid fa-shield-halved text-[9px]"></i> Ley 21.719 Cifrado
                </span>
            </div>

            <dl class="grid grid-cols-2 gap-2.5 text-xs">
                <div class="bg-white p-2.5 rounded-xl border border-slate-100">
                    <dt class="text-[10px] uppercase font-bold text-slate-400">Contexto del Deceso</dt>
                    <dd class="font-semibold text-slate-900 mt-0.5 flex items-center gap-1.5">
                        @if($docLegal->esNatural())
                            <span class="text-emerald-700 font-bold">🟢 Muerte Natural</span>
                        @elseif($docLegal->esSmlViolento())
                            <span class="text-rose-700 font-bold">🔴 Muerte Violenta (SML)</span>
                        @else
                            <span class="text-amber-700 font-bold">🌾 Zona Rural (Testigos)</span>
                        @endif
                    </dd>
                </div>

                <div class="bg-white p-2.5 rounded-xl border border-slate-100">
                    <dt class="text-[10px] uppercase font-bold text-slate-400">Traslado Interurbano</dt>
                    <dd class="font-semibold text-slate-900 mt-0.5">
                        @if($docLegal->traslado_interurbano)
                            <span class="text-indigo-700 font-bold"><i class="fa-solid fa-road mr-1"></i>Sí (Requiere Seremi)</span>
                        @else
                            <span class="text-slate-500">No (Local / Urbano)</span>
                        @endif
                    </dd>
                </div>

                @if($docLegal->folio_certificado_medico)
                    <div class="bg-white p-2.5 rounded-xl border border-slate-100">
                        <dt class="text-[10px] uppercase font-bold text-slate-400">Folio Certificado Médico</dt>
                        <dd class="font-mono font-bold text-emerald-800 mt-0.5 flex items-center gap-1">
                            <i class="fa-solid fa-lock text-[9px] text-slate-400"></i>{{ $docLegal->folio_certificado_medico }}
                        </dd>
                    </div>
                @endif

                @if($docLegal->folio_pase_sepultacion)
                    <div class="bg-white p-2.5 rounded-xl border border-slate-100">
                        <dt class="text-[10px] uppercase font-bold text-slate-400">Folio Pase Sepultación</dt>
                        <dd class="font-mono font-bold text-indigo-900 mt-0.5 flex items-center gap-1">
                            <i class="fa-solid fa-stamp text-[9px] text-indigo-500"></i>{{ $docLegal->folio_pase_sepultacion }}
                        </dd>
                    </div>
                @endif
            </dl>

            @if($docLegal->esRural() && !empty($docLegal->testigos_rurales))
                <div class="bg-emerald-50/70 border border-emerald-200 rounded-xl p-3 text-xs space-y-1.5">
                    <p class="font-bold text-emerald-950 flex items-center text-[11px]">
                        <i class="fa-solid fa-users text-emerald-600 mr-1.5"></i>Testigos Presenciales Rurales (Inscripción Legal)
                    </p>
                    <ul class="divide-y divide-emerald-100 text-[11px] text-emerald-900">
                        @foreach($docLegal->testigos_rurales as $testigo)
                            <li class="py-1 flex justify-between">
                                <span><strong>#{{ $loop->iteration }}:</strong> {{ $testigo['nombre'] ?? 'Sin nombre' }}</span>
                                <span class="font-mono text-emerald-800">RUT: {{ $testigo['rut'] ?? 'S/R' }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if($docLegal->ruta_orden_autopsia_sml || $docLegal->ruta_resolucion_seremi)
                <div class="pt-1 flex flex-wrap gap-2 text-xs">
                    @if($docLegal->ruta_orden_autopsia_sml)
                        <a href="{{ route('servicios.documentos_legales.descargar', [$servicio, 'sml', 'inline' => 1]) }}" target="_blank" class="px-2.5 py-1 bg-rose-50 text-rose-800 border border-rose-200 rounded-lg font-semibold text-[11px] hover:bg-rose-100 transition inline-flex items-center gap-1">
                            <i class="fa-solid fa-file-pdf text-rose-600"></i> Orden Fiscalía SML
                        </a>
                    @endif
                    @if($docLegal->ruta_resolucion_seremi)
                        <a href="{{ route('servicios.documentos_legales.descargar', [$servicio, 'seremi', 'inline' => 1]) }}" target="_blank" class="px-2.5 py-1 bg-indigo-50 text-indigo-800 border border-indigo-200 rounded-lg font-semibold text-[11px] hover:bg-indigo-100 transition inline-flex items-center gap-1">
                            <i class="fa-solid fa-file-shield text-indigo-600"></i> Resolución Seremi
                        </a>
                    @endif
                </div>
            @endif
        </section>
    @endif

    <section id="seccion-documentos" class="panel transition-all duration-300">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h2 class="font-serif-title text-base font-bold text-slate-900"><i class="fa-solid fa-file-contract text-indigo-700 mr-1.5"></i>Hito 3: Documentación Legal y Anexos</h2>
            @php $faltantes = $servicio->calcularDocumentosFaltantes(); @endphp
            @if(count($faltantes) === 0 && $servicio->documentos->isNotEmpty())
                <span class="bg-emerald-100 text-emerald-800 text-[10px] font-bold px-2.5 py-0.5 rounded-full flex items-center">
                    <i class="fa-solid fa-check mr-1"></i>Completo
                </span>
            @else
                <span class="bg-amber-100 text-amber-900 text-[10px] font-bold px-2.5 py-0.5 rounded-full">
                    {{ count($faltantes) }} pendientes
                </span>
            @endif
        </div>

        @if($servicio->documentos->isEmpty())
            <div class="rounded-xl border border-dashed border-slate-300 p-4 mt-4 text-xs text-slate-500 text-center space-y-2">
                <i class="fa-solid fa-file-circle-exclamation text-amber-500 text-2xl block"></i>
                <p class="font-semibold text-slate-700">Aún no se han cargado documentos de respaldo.</p>
                <p class="text-[11px] text-slate-400">Carga la Resolución Seremi y antecedentes legales editando el expediente.</p>
                <a href="{{ route('servicios.edit', $servicio) }}" class="inline-flex items-center button-secondary text-xs mt-1">
                    <i class="fa-solid fa-upload mr-1.5 text-indigo-600"></i>Cargar Documentos
                </a>
            </div>
        @else
            <ul class="mt-3 divide-y divide-slate-100">
                @foreach($servicio->documentos as $documento)
                    <li class="flex flex-wrap items-center justify-between gap-2 py-3 text-xs">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <i class="fa-solid {{ str_ends_with(strtolower($documento->nombre_original), '.pdf') ? 'fa-file-pdf text-rose-500' : 'fa-file-image text-emerald-600' }} text-lg flex-shrink-0"></i>
                            <div class="min-w-0">
                                <p class="font-semibold text-slate-800 truncate" title="{{ $documento->nombre_original }}">{{ $documento->nombre_original }}</p>
                                <span class="text-slate-400 text-[11px]">
                                    {{ $documento->tipoLabel() }}
                                    @if($documento->pesoFormateado())
                                        · {{ $documento->pesoFormateado() }}
                                    @endif
                                </span>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <a class="px-2.5 py-1 text-indigo-700 bg-indigo-50 hover:bg-indigo-100 rounded-lg font-semibold hover:underline text-[11px] inline-flex items-center" href="{{ route('servicios.documentos.descargar', [$servicio, $documento, 'inline' => 1]) }}" target="_blank" rel="noopener noreferrer" title="Previsualizar en nueva pestaña">
                                <i class="fa-regular fa-eye mr-1"></i>Ver
                            </a>
                            <a class="px-2.5 py-1 text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-lg font-medium text-[11px] inline-flex items-center" href="{{ route('servicios.documentos.descargar', [$servicio, $documento]) }}" title="Descargar archivo original">
                                <i class="fa-solid fa-download mr-1"></i>Descargar
                            </a>
                            <form action="{{ route('servicios.documentos.destroy', [$servicio, $documento]) }}" method="POST" onsubmit="return confirm('¿Estás seguro de que deseas eliminar este documento del expediente?')" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="p-1 text-rose-500 hover:text-rose-700 hover:bg-rose-50 rounded" title="Eliminar documento">
                                    <i class="fa-regular fa-trash-can"></i>
                                </button>
                            </form>
                        </div>
                    </li>
                @endforeach
            </ul>
            <div class="pt-2 text-right">
                <a href="{{ route('servicios.edit', $servicio) }}" class="text-[11px] text-indigo-600 hover:underline inline-flex items-center font-medium">
                    <i class="fa-solid fa-plus-circle mr-1"></i>Cargar o reemplazar documentos
                </a>
            </div>
        @endif
    </section>
</div>

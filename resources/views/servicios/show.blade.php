@extends('layouts.app')

@section('title', 'Expediente '.$servicio->id.' · '.$servicio->difunto)
@section('heading', 'Expediente #'.$servicio->id)

@section('content')
    <div class="space-y-6">

        @if(session('status'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs text-emerald-800 flex items-center justify-between shadow-xs">
                <div class="flex items-center space-x-2">
                    <i class="fa-solid fa-circle-check text-emerald-600 text-sm"></i>
                    <span>{{ session('status') }}</span>
                </div>
            </div>
        @endif

        <!-- ENCABEZADO PRINCIPAL -->
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 flex flex-wrap justify-between items-center gap-4">
            <div>
                <div class="flex items-center space-x-2">
                    <span class="bg-indigo-100 text-indigo-900 text-[10px] font-bold px-2.5 py-0.5 rounded-full uppercase tracking-wider">{{ $servicio->folio }}</span>
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold {{ $servicio->estadoBadgeClass() }}">
                        {{ $servicio->estadoLabel() }}
                    </span>
                </div>
                <h2 class="font-serif-title mt-1.5 text-2xl font-bold text-slate-900">{{ $servicio->difunto }}</h2>
                <p class="text-xs text-slate-500 mt-0.5">Expediente creado el {{ $servicio->created_at->format('d/m/Y \a \l\a\s H:i') }} hrs · Sede Calle Blanco</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a class="button-secondary" href="{{ route('servicios.index') }}">
                    <i class="fa-solid fa-arrow-left mr-1.5"></i>Volver a servicios
                </a>
                @if(auth()->user()->canAccessFinances())
                    <a class="button-secondary" href="{{ route('finanzas.pdf', $servicio) }}">
                        <i class="fa-solid fa-file-pdf text-rose-600 mr-1.5"></i>Comprobante PDF
                    </a>
                @endif
                <a class="button-primary" href="{{ route('servicios.edit', $servicio) }}">
                    <i class="fa-solid fa-pen-to-square mr-1.5"></i>Editar expediente
                </a>
            </div>
        </div>

        <!-- COMPONENTE STEPPER HORIZONTAL: TRAZABILIDAD DE 5 HITOS (INTERACTIVO CON BOTONES FUNCIONALES) -->
        <section id="seccion-stepper" class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 space-y-5">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-4">
                <div>
                    <h3 class="font-serif-title text-lg font-bold text-slate-900 flex items-center">
                        <i class="fa-solid fa-route text-indigo-700 mr-2 text-base"></i>Flujo Operativo y Trazabilidad en Tiempo Real
                    </h3>
                    <p class="text-xs text-slate-500 mt-0.5">Haz clic en cualquier hito o botón de acción para gestionar la etapa directamente.</p>
                </div>
                <div class="flex items-center space-x-3">
                    <span class="text-xs font-bold text-slate-700">{{ $porcentajeAvance }}% Completado</span>
                    <div class="w-28 sm:w-36 bg-slate-100 rounded-full h-2.5 overflow-hidden border border-slate-200">
                        <div class="bg-gradient-to-r from-indigo-600 to-emerald-500 h-2.5 rounded-full transition-all duration-500" style="width: {{ $porcentajeAvance }}%;"></div>
                    </div>
                </div>
            </div>

            <!-- ESTRUCTURA HORIZONTAL DEL STEPPER CON BOTONES FUNCIONALES -->
            <div class="overflow-x-auto pb-4 pt-2">
                <div class="min-w-[720px] flex items-start justify-between relative px-2">

                    @foreach($pasos as $index => $paso)
                        <!-- NODO DEL PASO -->
                        <div class="flex flex-col items-center text-center relative z-10 w-36">

                            <!-- Círculo del hito con disparador de acción -->
                            @if($paso['clave'] === 'apertura')
                                <button type="button" onclick="scrollHacia('seccion-contratante')" class="cursor-pointer group focus:outline-none" title="Ver antecedentes del expediente">
                                    <div class="w-10 h-10 rounded-full bg-emerald-600 group-hover:bg-emerald-700 text-white flex items-center justify-center font-bold text-sm shadow-md ring-4 ring-emerald-100 transition transform group-hover:scale-105">
                                        <i class="fa-solid fa-check"></i>
                                    </div>
                                </button>
                            @elseif($paso['clave'] === 'logistica_urna')
                                <button type="button" onclick="abrirModalUrnas()" class="cursor-pointer group focus:outline-none" title="Abrir catálogo y asignar urna">
                                    @if($paso['estado'] === 'completado')
                                        <div class="w-10 h-10 rounded-full bg-emerald-600 group-hover:bg-emerald-700 text-white flex items-center justify-center font-bold text-sm shadow-md ring-4 ring-emerald-100 transition transform group-hover:scale-105">
                                            <i class="fa-solid fa-check"></i>
                                        </div>
                                    @else
                                        <div class="w-10 h-10 rounded-full bg-indigo-600 group-hover:bg-indigo-700 text-white flex items-center justify-center font-bold text-sm shadow-md ring-4 ring-indigo-200 animate-pulse transition transform group-hover:scale-110">
                                            <i class="fa-solid fa-box-open text-xs"></i>
                                        </div>
                                    @endif
                                </button>
                            @elseif($paso['clave'] === 'documental')
                                <button type="button" onclick="scrollHacia('seccion-documentos')" class="cursor-pointer group focus:outline-none" title="Gestionar documentación Seremi en S3">
                                    @if($paso['estado'] === 'completado')
                                        <div class="w-10 h-10 rounded-full bg-emerald-600 group-hover:bg-emerald-700 text-white flex items-center justify-center font-bold text-sm shadow-md ring-4 ring-emerald-100 transition transform group-hover:scale-105">
                                            <i class="fa-solid fa-check"></i>
                                        </div>
                                    @elseif($paso['estado'] === 'activo')
                                        <div class="w-10 h-10 rounded-full bg-indigo-600 group-hover:bg-indigo-700 text-white flex items-center justify-center font-bold text-sm shadow-md ring-4 ring-indigo-100 transition transform group-hover:scale-105">
                                            {{ $paso['numero'] }}
                                        </div>
                                    @else
                                        <div class="w-10 h-10 rounded-full bg-slate-200 group-hover:bg-slate-300 text-slate-500 flex items-center justify-center font-bold text-sm transition">
                                            {{ $paso['numero'] }}
                                        </div>
                                    @endif
                                </button>
                            @elseif($paso['clave'] === 'cortejo_flota')
                                <a href="{{ route('flota.calendario') }}" class="cursor-pointer group focus:outline-none" title="Ver en Calendario de Flota">
                                    @if($paso['estado'] === 'completado')
                                        <div class="w-10 h-10 rounded-full bg-emerald-600 group-hover:bg-emerald-700 text-white flex items-center justify-center font-bold text-sm shadow-md ring-4 ring-emerald-100 transition transform group-hover:scale-105">
                                            <i class="fa-solid fa-check"></i>
                                        </div>
                                    @elseif($paso['estado'] === 'activo')
                                        <div class="w-10 h-10 rounded-full bg-indigo-600 group-hover:bg-indigo-700 text-white flex items-center justify-center font-bold text-sm shadow-md ring-4 ring-indigo-100 transition transform group-hover:scale-105">
                                            {{ $paso['numero'] }}
                                        </div>
                                    @else
                                        <div class="w-10 h-10 rounded-full bg-slate-200 group-hover:bg-slate-300 text-slate-500 flex items-center justify-center font-bold text-sm transition">
                                            {{ $paso['numero'] }}
                                        </div>
                                    @endif
                                </a>
                            @else
                                <button type="button" onclick="scrollHacia('seccion-contratante')" class="cursor-pointer group focus:outline-none">
                                    @if($paso['estado'] === 'completado')
                                        <div class="w-10 h-10 rounded-full bg-emerald-600 text-white flex items-center justify-center font-bold text-sm shadow-md ring-4 ring-emerald-100 transition">
                                            <i class="fa-solid fa-check"></i>
                                        </div>
                                    @else
                                        <div class="w-10 h-10 rounded-full bg-slate-200 group-hover:bg-slate-300 text-slate-500 flex items-center justify-center font-bold text-sm transition">
                                            {{ $paso['numero'] }}
                                        </div>
                                    @endif
                                </button>
                            @endif

                            <!-- Nombre del paso -->
                            <h4 class="mt-2 font-bold text-xs text-slate-900 leading-tight">
                                {{ $paso['nombre'] }}
                            </h4>

                            <!-- Fecha y Responsable -->
                            @if($paso['fecha'])
                                <span class="text-[10px] text-slate-500 mt-0.5 font-mono">
                                    {{ $paso['fecha'] instanceof \Carbon\Carbon ? $paso['fecha']->format('d/m/Y H:i') : $paso['fecha'] }}
                                </span>
                            @else
                                <span class="text-[10px] text-slate-400 mt-0.5 italic">En espera</span>
                            @endif

                            @if($paso['responsable'])
                                <span class="text-[10px] text-indigo-700 font-semibold leading-snug">
                                    Por: {{ Str::limit($paso['responsable'], 16) }}
                                </span>
                            @endif

                            <!-- BOTONES DE ACCIÓN FUNCIONALES DIRECTOS -->
                            <div class="mt-2 w-full px-1">
                                @if($paso['clave'] === 'apertura')
                                    <button type="button" onclick="scrollHacia('seccion-contratante')" class="w-full inline-flex items-center justify-center text-[10px] text-emerald-800 bg-emerald-50 hover:bg-emerald-100 border border-emerald-300 px-2 py-1 rounded-lg font-medium transition cursor-pointer shadow-2xs hover:shadow-xs">
                                        <i class="fa-solid fa-folder-open mr-1 text-emerald-600"></i>Folio: {{ $servicio->folio }}
                                    </button>

                                @elseif($paso['clave'] === 'logistica_urna')
                                    @if($paso['estado'] === 'completado')
                                        <button type="button" onclick="abrirModalUrnas()" class="w-full inline-flex items-center justify-center text-[10px] text-emerald-800 bg-emerald-50 hover:bg-emerald-100 border border-emerald-300 px-2 py-1 rounded-lg font-medium transition cursor-pointer shadow-2xs hover:shadow-xs" title="Ver o cambiar urna asignada">
                                            <i class="fa-solid fa-box-open mr-1 text-emerald-600"></i>{{ $paso['detalle'] }} &rarr;
                                        </button>
                                    @else
                                        <!-- BOTÓN FUNCIONAL SOLICITADO: LLEVA A LAS URNAS -->
                                        <button type="button" onclick="abrirModalUrnas()" class="w-full inline-flex items-center justify-center text-[10px] text-amber-950 bg-amber-200/80 hover:bg-amber-300 border border-amber-400 px-2.5 py-1.5 rounded-lg font-bold shadow-xs hover:shadow transition-all duration-200 cursor-pointer transform hover:scale-105 ring-2 ring-amber-300/50 animate-bounce" title="Presiona para elegir y asignar urna">
                                            <i class="fa-solid fa-boxes-stacked mr-1.5 text-amber-800"></i>Asignar Urna &rarr;
                                        </button>
                                    @endif

                                @elseif($paso['clave'] === 'documental')
                                    @if($paso['estado'] === 'completado')
                                        <button type="button" onclick="scrollHacia('seccion-documentos')" class="w-full inline-flex items-center justify-center text-[10px] text-emerald-800 bg-emerald-50 hover:bg-emerald-100 border border-emerald-300 px-2 py-1 rounded-lg font-medium transition cursor-pointer">
                                            <i class="fa-solid fa-file-check mr-1 text-emerald-600"></i>Resolución Seremi OK &rarr;
                                        </button>
                                    @else
                                        <button type="button" onclick="scrollHacia('seccion-documentos')" class="w-full inline-flex items-center justify-center text-[10px] text-amber-900 bg-amber-100 hover:bg-amber-200 border border-amber-300 px-2 py-1 rounded-lg font-bold transition cursor-pointer" title="Ir a subir documentos">
                                            <i class="fa-solid fa-cloud-arrow-up mr-1 text-amber-700"></i>Subir Seremi &rarr;
                                        </button>
                                    @endif

                                @elseif($paso['clave'] === 'cortejo_flota')
                                    <a href="{{ route('flota.calendario') }}" class="w-full inline-flex items-center justify-center text-[10px] text-emerald-800 bg-emerald-50 hover:bg-emerald-100 border border-emerald-300 px-2 py-1 rounded-lg font-medium transition cursor-pointer" title="Ver en Calendario de Flota">
                                        <i class="fa-solid fa-van-shuttle mr-1 text-emerald-600"></i>{{ Str::limit($paso['detalle'], 18) }} &rarr;
                                    </a>

                                @else
                                    <span class="w-full inline-block text-[10px] text-slate-500 bg-slate-100 border border-slate-200 px-2 py-1 rounded-lg">
                                        {{ $paso['detalle'] }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        <!-- LÍNEA CONECTORA ENTRE NODOS -->
                        @if(! $loop->last)
                            @php
                                $siguientePaso = $pasos[$index + 1];
                                $lineaCompletada = $paso['completado'] && ($siguientePaso['completado'] || $siguientePaso['estado'] === 'activo');
                            @endphp
                            <div class="flex-1 self-start mt-5 mx-1 h-1 rounded-full {{ $lineaCompletada ? 'bg-emerald-500' : 'bg-slate-200' }} transition-colors duration-300"></div>
                        @endif
                    @endforeach

                </div>
            </div>
        </section>

        <!-- PANELES DE DETALLE OPERATIVO: LOGÍSTICA DE URNA Y CORTEJO -->
        <div class="grid gap-6 lg:grid-cols-2">

            <!-- PANEL HITO 2: LOGÍSTICA DE URNA DESDE BODEGA -->
            <section id="seccion-urna" class="panel space-y-4 transition-all duration-300">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h2 class="font-serif-title text-base font-bold text-slate-900 flex items-center">
                        <i class="fa-solid fa-box-archive text-indigo-700 mr-2"></i>Hito 2: Logística y Asignación de Urna
                    </h2>
                    @if($servicio->urna)
                        <span class="bg-emerald-100 text-emerald-800 text-[10px] font-bold px-2.5 py-0.5 rounded-full flex items-center">
                            <i class="fa-solid fa-check mr-1"></i>Asignada
                        </span>
                    @else
                        <span class="bg-amber-100 text-amber-900 text-[10px] font-bold px-2.5 py-0.5 rounded-full">
                            Pendiente
                        </span>
                    @endif
                </div>

                @if($servicio->urna)
                    <div class="rounded-xl border border-indigo-100 bg-gradient-to-br from-indigo-50/50 to-white p-4 text-xs space-y-3">
                        <div class="flex items-center justify-between">
                            <div>
                                <strong class="text-sm font-bold text-indigo-950">{{ $servicio->urna->modelo }}</strong>
                                <span class="block text-[11px] text-slate-500">Código: {{ $servicio->urna->codigo ?: 'UR-'.$servicio->urna->id }}</span>
                            </div>
                            <button type="button" onclick="abrirModalUrnas()" class="px-2.5 py-1 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 text-xs font-semibold transition">
                                <i class="fa-solid fa-arrows-rotate mr-1"></i>Cambiar urna
                            </button>
                        </div>
                        <div class="grid grid-cols-2 gap-2 text-slate-600 text-[11px] pt-1 border-t border-indigo-100/60">
                            <div><span class="text-slate-400">Color:</span> {{ $servicio->urna->color }}</div>
                            <div><span class="text-slate-400">Categoría:</span> {{ $servicio->urna->categoria_etaria }}</div>
                            <div><span class="text-slate-400">Medidas:</span> {{ $servicio->urna->medidas_cm }}</div>
                            <div><span class="text-slate-400">Tallado:</span> {{ $servicio->urna->tallado ?: 'Estándar' }}</div>
                        </div>
                    </div>
                @else
                    <div class="rounded-xl border border-amber-300 bg-amber-50/40 p-4 text-xs space-y-3">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <p class="font-bold text-amber-950">No hay urna asignada a este servicio.</p>
                                <p class="text-[11px] text-amber-900/80 mt-0.5">Asigna una urna desde el inventario disponible de Salón Calle Blanco o Taller:</p>
                            </div>
                            <!-- Botón para abrir el catálogo visual de urnas -->
                            <button type="button" onclick="abrirModalUrnas()" class="px-3 py-1.5 rounded-lg bg-indigo-900 hover:bg-indigo-800 text-white font-bold text-xs shadow-xs transition flex items-center whitespace-nowrap">
                                <i class="fa-solid fa-boxes-stacked mr-1.5"></i>Abrir Catálogo Visual
                            </button>
                        </div>

                        <!-- Selector rápido directo -->
                        <form method="POST" action="{{ route('servicios.urna.asignar', $servicio) }}" class="flex flex-col sm:flex-row gap-2 pt-2 border-t border-amber-200/60">
                            @csrf
                            @method('PATCH')
                            <select id="select-urna-rapida" name="urna_id" class="field text-xs flex-1 bg-white" required>
                                <option value="">-- Seleccionar urna desde bodega --</option>
                                @foreach($urnasDisponibles as $u)
                                    <option value="{{ $u->id }}">
                                        {{ $u->modelo }} · {{ $u->color }} ({{ $u->categoria_etaria }}) · Stock: {{ $u->stock_salon }} Salón / {{ $u->stock_taller }} Taller
                                    </option>
                                @endforeach
                            </select>
                            <button type="submit" class="button-primary text-xs whitespace-nowrap">
                                <i class="fa-solid fa-plus mr-1"></i>Asignar Urna
                            </button>
                        </form>
                    </div>
                @endif

                <div class="flex justify-end pt-1">
                    <a href="{{ route('bodega.index') }}" target="_blank" rel="noopener" class="text-indigo-700 hover:text-indigo-900 font-bold text-xs flex items-center">
                        <i class="fa-solid fa-warehouse mr-1.5"></i>Ir a Gestión de Bodega Dual (/bodega)
                        <i class="fa-solid fa-arrow-up-right-from-square text-[10px] ml-1 opacity-70"></i>
                    </a>
                </div>
            </section>

            <!-- PANEL HITO 4: CORTEJO Y TRASLADO (FLOTA Y CHOFER) -->
            <section id="seccion-cortejo" class="panel space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h2 class="font-serif-title text-base font-bold text-slate-900 flex items-center">
                        <i class="fa-solid fa-van-shuttle text-indigo-700 mr-2"></i>Hito 4: Cortejo y Flota
                    </h2>
                    @php $traslado = $servicio->traslados->sortByDesc('salida_at')->first(); @endphp
                    @if($traslado && $traslado->chofer && $traslado->vehiculo)
                        <span class="bg-emerald-100 text-emerald-800 text-[10px] font-bold px-2.5 py-0.5 rounded-full">
                            <i class="fa-solid fa-check mr-1"></i>Asignado
                        </span>
                    @else
                        <span class="bg-amber-100 text-amber-900 text-[10px] font-bold px-2.5 py-0.5 rounded-full">
                            {{ $servicio->cortejo_at ? 'Falta Asignar Chofer' : 'Sin Programar' }}
                        </span>
                    @endif
                </div>

                @if($traslado)
                    <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-4 text-xs space-y-2">
                        <div class="grid grid-cols-2 gap-2 text-slate-700 text-[11px]">
                            <div><span class="text-slate-400">Fecha y hora:</span> <strong>{{ $servicio->cortejo_at?->format('d/m/Y H:i') ?? 'Sin fecha' }}</strong></div>
                            <div><span class="text-slate-400">Destino:</span> <strong>{{ $servicio->destino ?: 'No informado' }}</strong></div>
                            <div>
                                <span class="text-slate-400">Vehículo:</span>
                                <strong>{{ $traslado->vehiculo ? $traslado->vehiculo->patente.' ('.$traslado->vehiculo->modelo.')' : 'Pendiente de asignar' }}</strong>
                            </div>
                            <div>
                                <span class="text-slate-400">Chofer:</span>
                                <strong>{{ $traslado->chofer ? $traslado->chofer->name : 'Pendiente de asignar' }}</strong>
                            </div>
                        </div>
                        <div class="pt-2 border-t border-slate-200 flex justify-end">
                            <a href="{{ route('flota.calendario') }}" class="text-indigo-700 font-bold hover:underline text-[11px] flex items-center">
                                <i class="fa-solid fa-calendar-days mr-1"></i>Gestionar en Calendario de Flota &rarr;
                            </a>
                        </div>
                    </div>
                @else
                    <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-4 text-xs text-slate-500">
                        <p>No se ha configurado un traslado para este servicio funerario.</p>
                        <p class="text-[11px] mt-1">Puedes programar fecha y destino editando el expediente.</p>
                    </div>
                @endif
            </section>
        </div>

        <!-- PANELES DE ANTECEDENTES Y DOCUMENTACIÓN S3 -->
        <div class="grid gap-6 lg:grid-cols-2">
            <!-- ANTECEDENTES DEL DIFUNTO Y CONTRATANTE (HITO 1) -->
            <section id="seccion-contratante" class="panel transition-all duration-300">
                <h2 class="font-serif-title text-base font-bold text-slate-900"><i class="fa-solid fa-clipboard-list text-indigo-700 mr-1.5"></i>Antecedentes del Contratante y Fallecido</h2>
                <dl class="mt-4 grid grid-cols-2 gap-4 text-xs">
                    <div><dt class="text-slate-400">Contratante</dt><dd class="mt-1 font-bold text-slate-900">{{ $servicio->cliente_contratante }}</dd></div>
                    <div><dt class="text-slate-400">Teléfono</dt><dd class="mt-1 font-semibold">{{ $servicio->telefono_cliente ?: 'No registrado' }}</dd></div>
                    <div><dt class="text-slate-400">Convenio</dt><dd class="mt-1"><span class="bg-indigo-50 text-indigo-900 px-2 py-0.5 rounded font-bold">{{ ucfirst($servicio->convenio) }}</span></dd></div>
                    <div><dt class="text-slate-400">Total del servicio</dt><dd class="mt-1 font-bold text-slate-900">${{ number_format((float) $servicio->total_servicio, 0, ',', '.') }} CLP</dd></div>
                    <div><dt class="text-slate-400">RUT Difunto</dt><dd class="mt-1 font-semibold">{{ $servicio->difunto_rut ?: 'No registrado' }}</dd></div>
                    <div><dt class="text-slate-400">Escolaridad</dt><dd class="mt-1">{{ $servicio->escolaridad ?: 'No registrada' }}</dd></div>
                    <div><dt class="text-slate-400">Oficio</dt><dd class="mt-1">{{ $servicio->oficio ?: 'No registrado' }}</dd></div>
                    <div class="col-span-2"><dt class="text-slate-400">Causa SML / Fiscalía</dt><dd class="mt-1">{{ $servicio->causa_sml ?: 'Fallecimiento por causa natural (sin intervención SML)' }}</dd></div>
                </dl>
            </section>

            <!-- HITO 3: DOCUMENTACIÓN EN AMAZON S3 -->
            <section id="seccion-documentos" class="panel transition-all duration-300">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h2 class="font-serif-title text-base font-bold text-slate-900"><i class="fa-solid fa-cloud-arrow-down text-indigo-700 mr-1.5"></i>Hito 3: Documentación Legal (S3)</h2>
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
                        <p class="font-semibold text-slate-700">Aún no se han cargado documentos en Amazon S3.</p>
                        <p class="text-[11px] text-slate-400">Carga la Resolución Seremi editando el expediente.</p>
                        <a href="{{ route('servicios.edit', $servicio) }}" class="inline-flex items-center button-secondary text-xs mt-1">
                            <i class="fa-solid fa-upload mr-1.5 text-indigo-600"></i>Cargar Documentos
                        </a>
                    </div>
                @else
                    <ul class="mt-3 divide-y divide-slate-100">
                        @foreach($servicio->documentos as $documento)
                            <li class="flex flex-wrap items-center justify-between gap-2 py-2.5 text-xs">
                                <span>
                                    <i class="fa-solid fa-file-shield text-indigo-700 mr-1.5"></i>
                                    <strong class="text-slate-800">{{ $documento->nombre_original }}</strong>
                                    <span class="text-slate-400 text-[11px]">· {{ $documento->tipoLabel() }}</span>
                                </span>
                                <a class="text-indigo-700 font-bold hover:underline text-[11px]" href="{{ route('servicios.documentos.descargar', [$servicio, $documento]) }}">
                                    <i class="fa-solid fa-download mr-1"></i>Descargar
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </div>

        <!-- HISTORIAL DE AUDITORÍA Y TRAZABILIDAD CRONOLÓGICA -->
        <section id="seccion-historial" class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 space-y-4">
            <h3 class="font-serif-title text-base font-bold text-slate-900 flex items-center">
                <i class="fa-solid fa-timeline text-indigo-700 mr-2"></i>Historial Cronológico de Auditoría (Trazabilidad)
            </h3>
            <div class="flow-root mt-3">
                <ul class="-mb-8">
                    @forelse($servicio->historial as $evento)
                        <li>
                            <div class="relative pb-8">
                                @if(! $loop->last)
                                    <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-slate-200" aria-hidden="true"></span>
                                @endif
                                <div class="relative flex space-x-3.5">
                                    <div>
                                        <span class="h-8 w-8 rounded-full bg-indigo-50 border border-indigo-200 text-indigo-700 flex items-center justify-center ring-4 ring-white text-xs">
                                            <i class="fa-solid {{ $evento->hitoDefinicion()['icono'] }}"></i>
                                        </span>
                                    </div>
                                    <div class="flex-1 min-w-0 pt-1">
                                        <div class="flex items-center justify-between">
                                            <p class="text-xs font-bold text-slate-900">{{ $evento->titulo }}</p>
                                            <span class="text-[11px] text-slate-400 font-mono">{{ $evento->completado_at?->format('d/m/Y H:i') }}</span>
                                        </div>
                                        <p class="text-xs text-slate-600 mt-0.5">{{ $evento->descripcion }}</p>
                                        <p class="text-[10px] text-slate-400 mt-1">Registrado por: <strong>{{ $evento->usuario?->name ?? 'Sistema' }}</strong></p>
                                    </div>
                                </div>
                            </div>
                        </li>
                    @empty
                        <li class="text-xs text-slate-400 italic">No se han registrado eventos en el historial.</li>
                    @endforelse
                </ul>
            </div>
        </section>

        <!-- ELIMINAR EXPEDIENTE -->
        <div id="seccion-cierre" class="flex justify-end">
            <form method="POST" action="{{ route('servicios.destroy', $servicio) }}" onsubmit="return confirm('¿Eliminar este expediente?');">
                @csrf @method('DELETE')
                <button class="text-xs font-bold text-rose-700 hover:underline">
                    <i class="fa-solid fa-trash-can mr-1"></i>Eliminar expediente
                </button>
            </form>
        </div>
    </div>

    <!-- MODAL INTERACTIVO DE ASIGNACIÓN Y CATÁLOGO DE URNAS -->
    <div id="modal-catalogo-urnas" class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="relative w-full max-w-4xl max-h-[90vh] rounded-2xl bg-white shadow-2xl border border-slate-100 flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-200">

            <!-- Encabezado del Modal -->
            <div class="bg-gradient-to-r from-indigo-950 via-indigo-900 to-indigo-800 px-6 py-4 text-white flex items-center justify-between flex-shrink-0">
                <div>
                    <div class="flex items-center space-x-2">
                        <span class="bg-white/20 text-white text-[10px] font-bold px-2 py-0.5 rounded uppercase tracking-wider">Hito 2: Logística Interna</span>
                        <span class="text-indigo-200 text-xs font-semibold">Expediente #{{ $servicio->id }}</span>
                    </div>
                    <h3 class="font-serif-title text-xl font-bold mt-0.5 text-white">Catálogo de Urnas y Asignación Operativa</h3>
                    <p class="text-xs text-indigo-200 mt-0.5">Asignar urna para el causante: <strong>{{ $servicio->difunto }}</strong></p>
                </div>
                <button type="button" onclick="cerrarModalUrnas()" class="text-white/70 hover:text-white p-2 rounded-lg hover:bg-white/10 transition">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <!-- Filtros y Búsqueda en Vivo -->
            <div class="p-4 border-b border-slate-100 bg-slate-50/70 flex flex-col sm:flex-row gap-3 items-center justify-between flex-shrink-0 text-xs">
                <div class="relative w-full sm:w-80">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-2.5 text-slate-400"></i>
                    <input type="text" id="filtro-urnas-input" oninput="filtrarUrnasEnModal()" class="field pl-9 w-full text-xs" placeholder="Buscar modelo, madera, color o categoría...">
                </div>

                <!-- Botones de Categoría Rápida -->
                <div class="flex flex-wrap gap-1.5 self-start sm:self-center">
                    <button type="button" onclick="filtrarCategoriaUrna('todos')" class="btn-categoria-urna active px-2.5 py-1 rounded-lg text-[11px] font-bold bg-indigo-900 text-white transition">Todos</button>
                    <button type="button" onclick="filtrarCategoriaUrna('Adulto')" class="btn-categoria-urna px-2.5 py-1 rounded-lg text-[11px] font-semibold bg-white border border-slate-200 text-slate-700 hover:bg-slate-100 transition">Adulto</button>
                    <button type="button" onclick="filtrarCategoriaUrna('Juvenil')" class="btn-categoria-urna px-2.5 py-1 rounded-lg text-[11px] font-semibold bg-white border border-slate-200 text-slate-700 hover:bg-slate-100 transition">Juvenil</button>
                    <button type="button" onclick="filtrarCategoriaUrna('Niño')" class="btn-categoria-urna px-2.5 py-1 rounded-lg text-[11px] font-semibold bg-white border border-slate-200 text-slate-700 hover:bg-slate-100 transition">Niño</button>
                    <button type="button" onclick="filtrarCategoriaUrna('Especial')" class="btn-categoria-urna px-2.5 py-1 rounded-lg text-[11px] font-semibold bg-white border border-slate-200 text-slate-700 hover:bg-slate-100 transition">Especial</button>
                </div>
            </div>

            <!-- Catálogo de Tarjetas de Urnas (Scrollable) -->
            <div class="p-6 overflow-y-auto flex-1 text-xs">
                <div id="grid-urnas-modal" class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($urnasDisponibles as $urna)
                        @php
                            $esActual = $servicio->urna_id === $urna->id;
                            $stockTotal = $urna->stock_salon + $urna->stock_taller;
                        @endphp
                        <div class="card-urna-item rounded-xl border {{ $esActual ? 'border-emerald-400 bg-emerald-50/30 ring-2 ring-emerald-200' : 'border-slate-200 bg-white hover:border-indigo-300' }} p-4 flex flex-col justify-between shadow-2xs hover:shadow-sm transition"
                             data-modelo="{{ strtolower($urna->modelo) }}"
                             data-color="{{ strtolower($urna->color) }}"
                             data-categoria="{{ strtolower($urna->categoria_etaria) }}">

                            <div>
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <h4 class="font-bold text-slate-900 text-xs">{{ $urna->modelo }}</h4>
                                        <span class="text-[10px] text-slate-400 font-mono">{{ $urna->codigo ?: 'UR-'.$urna->id }}</span>
                                    </div>
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-700 border border-indigo-100">
                                        {{ $urna->categoria_etaria }}
                                    </span>
                                </div>

                                <div class="mt-2.5 space-y-1 text-[11px] text-slate-600">
                                    <p><strong class="text-slate-700">Color:</strong> {{ $urna->color }}</p>
                                    <p><strong class="text-slate-700">Medidas:</strong> {{ $urna->medidas_cm }}</p>
                                    <p><strong class="text-slate-700">Tallado:</strong> {{ $urna->tallado ?: 'Estándar' }}</p>
                                </div>

                                <!-- Disponibilidad Dual de Stock -->
                                <div class="mt-3 p-2 rounded-lg bg-slate-50 border border-slate-100 grid grid-cols-2 gap-1 text-[10px]">
                                    <div>
                                        <span class="text-slate-500 block">Salón Blanco:</span>
                                        <strong class="{{ $urna->stock_salon > 0 ? 'text-emerald-700' : 'text-slate-400' }} font-bold">
                                            {{ $urna->stock_salon }} unid.
                                        </strong>
                                    </div>
                                    <div>
                                        <span class="text-slate-500 block">Taller:</span>
                                        <strong class="{{ $urna->stock_taller > 0 ? 'text-indigo-700' : 'text-slate-400' }} font-bold">
                                            {{ $urna->stock_taller }} unid.
                                        </strong>
                                    </div>
                                </div>
                            </div>

                            <!-- Botón de Asignación -->
                            <form method="POST" action="{{ route('servicios.urna.asignar', $servicio) }}" class="mt-4 pt-3 border-t border-slate-100">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="urna_id" value="{{ $urna->id }}">
                                @if($esActual)
                                    <button type="button" disabled class="w-full py-1.5 rounded-lg bg-emerald-600 text-white font-bold text-xs flex items-center justify-center cursor-default">
                                        <i class="fa-solid fa-check mr-1.5"></i>Asignada Actualmente
                                    </button>
                                @else
                                    <button type="submit" class="w-full py-1.5 rounded-lg bg-indigo-900 hover:bg-indigo-800 text-white font-bold text-xs transition flex items-center justify-center shadow-xs">
                                        <i class="fa-solid fa-plus mr-1.5"></i>Asignar a este Servicio
                                    </button>
                                @endif
                            </form>
                        </div>
                    @endforeach
                </div>
                <div id="no-urnas-msg" class="hidden text-center py-10 text-slate-500 text-xs">
                    No se encontraron urnas que coincidan con la búsqueda.
                </div>
            </div>

            <!-- Pie de Modal -->
            <div class="px-6 py-3.5 bg-slate-50 border-t border-slate-100 flex items-center justify-between flex-shrink-0 text-xs">
                <a href="{{ route('bodega.index') }}" target="_blank" rel="noopener" class="text-indigo-700 hover:text-indigo-900 font-bold flex items-center">
                    <i class="fa-solid fa-warehouse mr-1.5"></i>Ir al Módulo de Urnas y Bodega Dual (/bodega)
                    <i class="fa-solid fa-arrow-up-right-from-square text-[10px] ml-1 opacity-70"></i>
                </a>
                <button type="button" onclick="cerrarModalUrnas()" class="px-4 py-2 rounded-lg border border-slate-200 text-slate-700 hover:bg-white font-semibold transition">
                    Cerrar
                </button>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
    // Smooth scroll y resaltado de la sección destino
    function scrollHacia(idElemento) {
        const elemento = document.getElementById(idElemento);
        if (elemento) {
            elemento.scrollIntoView({ behavior: 'smooth', block: 'center' });
            elemento.classList.add('ring-4', 'ring-indigo-300', 'transition-all');
            setTimeout(() => {
                elemento.classList.remove('ring-4', 'ring-indigo-300');
            }, 1800);
        }
    }

    // Modal de Urnas
    function abrirModalUrnas() {
        const modal = document.getElementById('modal-catalogo-urnas');
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        setTimeout(() => {
            const input = document.getElementById('filtro-urnas-input');
            if (input) input.focus();
        }, 100);
    }

    function cerrarModalUrnas() {
        const modal = document.getElementById('modal-catalogo-urnas');
        modal.classList.add('hidden');
        document.body.style.overflow = '';
    }

    // Cerrar modal al pulsar Escape o fondo
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') cerrarModalUrnas();
    });
    document.getElementById('modal-catalogo-urnas').addEventListener('click', function(e) {
        if (e.target === this) cerrarModalUrnas();
    });

    // Filtro de Urnas en vivo en el modal
    let categoriaActiva = 'todos';
    function filtrarCategoriaUrna(categoria) {
        categoriaActiva = categoria.toLowerCase();
        document.querySelectorAll('.btn-categoria-urna').forEach(btn => {
            if (btn.textContent.trim().toLowerCase() === categoriaActiva) {
                btn.className = 'btn-categoria-urna px-2.5 py-1 rounded-lg text-[11px] font-bold bg-indigo-900 text-white transition';
            } else {
                btn.className = 'btn-categoria-urna px-2.5 py-1 rounded-lg text-[11px] font-semibold bg-white border border-slate-200 text-slate-700 hover:bg-slate-100 transition';
            }
        });
        filtrarUrnasEnModal();
    }

    function filtrarUrnasEnModal() {
        const query = (document.getElementById('filtro-urnas-input').value || '').toLowerCase().trim();
        const items = document.querySelectorAll('.card-urna-item');
        let visibles = 0;

        items.forEach(card => {
            const modelo = card.getAttribute('data-modelo') || '';
            const color = card.getAttribute('data-color') || '';
            const categoria = card.getAttribute('data-categoria') || '';

            const coincideQuery = !query || modelo.includes(query) || color.includes(query) || categoria.includes(query);
            const coincideCat = categoriaActiva === 'todos' || categoria === categoriaActiva;

            if (coincideQuery && coincideCat) {
                card.classList.remove('hidden');
                visibles++;
            } else {
                card.classList.add('hidden');
            }
        });

        const noMsg = document.getElementById('no-urnas-msg');
        if (visibles === 0) {
            noMsg.classList.remove('hidden');
        } else {
            noMsg.classList.add('hidden');
        }
    }
</script>
@endpush

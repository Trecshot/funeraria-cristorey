@extends('layouts.app')

@section('title', 'Expediente '.$servicio->id.' · '.$servicio->difunto)
@section('heading', 'Expediente #'.$servicio->id)

@section('content')
    <div class="space-y-6">

        @if(session('status'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs text-emerald-800 flex items-center justify-between">
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

        <!-- COMPONENTE STEPPER HORIZONTAL: TRAZABILIDAD DE 5 HITOS (ESTILO E-COMMERCE) -->
        <section class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 space-y-5">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-4">
                <div>
                    <h3 class="font-serif-title text-lg font-bold text-slate-900 flex items-center">
                        <i class="fa-solid fa-route text-indigo-700 mr-2 text-base"></i>Flujo Operativo y Trazabilidad en Tiempo Real
                    </h3>
                    <p class="text-xs text-slate-500 mt-0.5">Línea de tiempo de los 5 hitos fundamentales del servicio funerario.</p>
                </div>
                <div class="flex items-center space-x-3">
                    <span class="text-xs font-bold text-slate-700">{{ $porcentajeAvance }}% Completado</span>
                    <div class="w-28 sm:w-36 bg-slate-100 rounded-full h-2.5 overflow-hidden border border-slate-200">
                        <div class="bg-gradient-to-r from-indigo-600 to-emerald-500 h-2.5 rounded-full transition-all duration-500" style="width: {{ $porcentajeAvance }}%;"></div>
                    </div>
                </div>
            </div>

            <!-- ESTRUCTURA HORIZONTAL DEL STEPPER -->
            <div class="overflow-x-auto pb-4 pt-2">
                <div class="min-w-[700px] flex items-start justify-between relative px-4">

                    @foreach($pasos as $index => $paso)
                        <!-- NODO DEL PASO -->
                        <div class="flex flex-col items-center text-center relative z-10 w-36">
                            <!-- Círculo del hito -->
                            @if($paso['estado'] === 'completado')
                                <div class="w-10 h-10 rounded-full bg-emerald-600 text-white flex items-center justify-center font-bold text-sm shadow-md ring-4 ring-emerald-100 transition">
                                    <i class="fa-solid fa-check"></i>
                                </div>
                            @elseif($paso['estado'] === 'activo')
                                <div class="w-10 h-10 rounded-full bg-indigo-600 text-white flex items-center justify-center font-bold text-sm shadow-md ring-4 ring-indigo-100 animate-pulse transition">
                                    {{ $paso['numero'] }}
                                </div>
                            @else
                                <div class="w-10 h-10 rounded-full bg-slate-200 text-slate-500 flex items-center justify-center font-bold text-sm transition">
                                    {{ $paso['numero'] }}
                                </div>
                            @endif

                            <!-- Nombre del paso -->
                            <h4 class="mt-2.5 font-bold text-xs text-slate-900 leading-tight">
                                {{ $paso['nombre'] }}
                            </h4>

                            <!-- Fecha y Responsable -->
                            @if($paso['fecha'])
                                <span class="text-[10px] text-slate-500 mt-1 font-mono">
                                    {{ $paso['fecha'] instanceof \Carbon\Carbon ? $paso['fecha']->format('d/m/Y H:i') : $paso['fecha'] }}
                                </span>
                            @else
                                <span class="text-[10px] text-slate-400 mt-1 italic">En espera</span>
                            @endif

                            @if($paso['responsable'])
                                <span class="text-[10px] text-indigo-700 font-semibold mt-0.5 leading-snug">
                                    Por: {{ Str::limit($paso['responsable'], 16) }}
                                </span>
                            @endif

                            <!-- Detalle del paso -->
                            <div class="mt-1.5 w-full">
                                @if($paso['estado'] === 'completado')
                                    <span class="inline-block text-[10px] text-emerald-800 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded font-medium leading-snug">
                                        {{ $paso['detalle'] }}
                                    </span>
                                @elseif($paso['estado'] === 'activo')
                                    <span class="inline-block text-[10px] text-amber-900 bg-amber-50 border border-amber-200 px-2 py-0.5 rounded font-semibold leading-snug">
                                        {{ $paso['detalle'] }}
                                    </span>
                                @else
                                    <span class="inline-block text-[10px] text-slate-400 bg-slate-50 px-2 py-0.5 rounded leading-snug">
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
            <section class="panel space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h2 class="font-serif-title text-base font-bold text-slate-900 flex items-center">
                        <i class="fa-solid fa-box-archive text-indigo-700 mr-2"></i>Hito 2: Logística y Asignación de Urna
                    </h2>
                    @if($servicio->urna)
                        <span class="bg-emerald-100 text-emerald-800 text-[10px] font-bold px-2.5 py-0.5 rounded-full">
                            <i class="fa-solid fa-check mr-1"></i>Asignada
                        </span>
                    @else
                        <span class="bg-amber-100 text-amber-900 text-[10px] font-bold px-2.5 py-0.5 rounded-full">
                            Pendiente
                        </span>
                    @endif
                </div>

                @if($servicio->urna)
                    <div class="rounded-xl border border-indigo-100 bg-gradient-to-br from-indigo-50/50 to-white p-4 text-xs space-y-2">
                        <div class="flex items-center justify-between">
                            <strong class="text-sm font-bold text-indigo-950">{{ $servicio->urna->modelo }}</strong>
                            <span class="text-[11px] font-bold text-slate-500">{{ $servicio->urna->codigo ?: 'UR-'.$servicio->urna->id }}</span>
                        </div>
                        <div class="grid grid-cols-2 gap-2 text-slate-600 text-[11px] pt-1">
                            <div><span class="text-slate-400">Color:</span> {{ $servicio->urna->color }}</div>
                            <div><span class="text-slate-400">Categoría:</span> {{ $servicio->urna->categoria_etaria }}</div>
                            <div><span class="text-slate-400">Medidas:</span> {{ $servicio->urna->medidas_cm }}</div>
                            <div><span class="text-slate-400">Tallado:</span> {{ $servicio->urna->tallado ?: 'Estándar' }}</div>
                        </div>
                    </div>
                @else
                    <div class="rounded-xl border border-dashed border-amber-300 bg-amber-50/40 p-4 text-xs">
                        <p class="font-bold text-amber-950">No hay urna asignada a este servicio.</p>
                        <p class="text-[11px] text-amber-900/80 mt-0.5">Asigna una urna desde el inventario disponible de Salón Calle Blanco o Taller:</p>

                        <form method="POST" action="{{ route('servicios.urna.asignar', $servicio) }}" class="mt-3 flex flex-col sm:flex-row gap-2">
                            @csrf
                            @method('PATCH')
                            <select name="urna_id" class="field text-xs flex-1 bg-white" required>
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
            </section>

            <!-- PANEL HITO 4: CORTEJO Y TRASLADO (FLOTA Y CHOFER) -->
            <section class="panel space-y-4">
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
            <!-- ANTECEDENTES DEL DIFUNTO Y CONTRATANTE -->
            <section class="panel">
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
            <section class="panel">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h2 class="font-serif-title text-base font-bold text-slate-900"><i class="fa-solid fa-cloud-arrow-down text-indigo-700 mr-1.5"></i>Hito 3: Documentación Legal (S3)</h2>
                    @php $faltantes = $servicio->calcularDocumentosFaltantes(); @endphp
                    @if(count($faltantes) === 0 && $servicio->documentos->isNotEmpty())
                        <span class="bg-emerald-100 text-emerald-800 text-[10px] font-bold px-2.5 py-0.5 rounded-full">
                            <i class="fa-solid fa-check mr-1"></i>Completo
                        </span>
                    @else
                        <span class="bg-amber-100 text-amber-900 text-[10px] font-bold px-2.5 py-0.5 rounded-full">
                            {{ count($faltantes) }} pendientes
                        </span>
                    @endif
                </div>

                @if($servicio->documentos->isEmpty())
                    <div class="rounded-xl border border-dashed border-slate-300 p-4 mt-4 text-xs text-slate-500 text-center">
                        <i class="fa-solid fa-file-circle-exclamation text-amber-500 text-2xl mb-1 block"></i>
                        Aún no se han cargado documentos en S3.
                        <p class="text-[11px] text-slate-400 mt-1">Carga la Resolución Seremi editando el expediente.</p>
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
        <section class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 space-y-4">
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
        <form method="POST" action="{{ route('servicios.destroy', $servicio) }}" onsubmit="return confirm('¿Eliminar este expediente?');" class="text-right">
            @csrf @method('DELETE')
            <button class="text-xs font-bold text-rose-700 hover:underline">
                <i class="fa-solid fa-trash-can mr-1"></i>Eliminar expediente
            </button>
        </form>
    </div>
@endsection

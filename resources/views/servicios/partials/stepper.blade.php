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
                        <button type="button" onclick="scrollHacia('seccion-documentos')" class="cursor-pointer group focus:outline-none" title="Gestionar documentación legal y anexos">
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
                        <button type="button" onclick="scrollHacia('seccion-estado-cuenta')" class="cursor-pointer group focus:outline-none" title="Ver Estado de Cuenta y Cierre Financiero">
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

                        @elseif($paso['clave'] === 'finalizado')
                            <button type="button" onclick="scrollHacia('seccion-estado-cuenta')" class="w-full inline-flex items-center justify-center text-[10px] {{ $servicio->liquidacion?->estado_general === 'moroso' ? 'text-rose-950 bg-rose-100 hover:bg-rose-200 border border-rose-300 font-bold' : 'text-slate-700 bg-slate-100 hover:bg-slate-200 border border-slate-200 font-medium' }} px-2 py-1 rounded-lg transition cursor-pointer" title="Ver Estado de Cuenta y Copagos">
                                <i class="fa-solid fa-coins mr-1 {{ $servicio->liquidacion?->estado_general === 'moroso' ? 'text-rose-600' : 'text-slate-500' }}"></i>{{ $servicio->liquidacion?->estado_general === 'moroso' ? 'Alerta Moroso' : 'Estado Cuenta' }} &rarr;
                            </button>
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

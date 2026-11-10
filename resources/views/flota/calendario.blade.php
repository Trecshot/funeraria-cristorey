@extends('layouts.app')

@section('title', 'Flota y Conductores')
@section('heading', 'Calendario de Flota, Conductores y Prevención de Solapamientos')

@section('content')
    <div class="space-y-6">
        @if(session('status'))
            <div class="rounded-2xl border border-emerald-300 bg-emerald-50 p-4 text-xs text-emerald-950 flex items-center space-x-3 shadow-sm" role="alert">
                <i class="fa-solid fa-circle-check text-emerald-600 text-base"></i>
                <span>{{ session('status') }}</span>
            </div>
        @endif

        @if($errors->any())
            <div class="rounded-2xl border border-rose-300 bg-rose-50 p-4 text-xs text-rose-950 space-y-1 shadow-sm" role="alert">
                <div class="font-bold flex items-center space-x-2">
                    <i class="fa-solid fa-triangle-exclamation text-rose-600 text-sm"></i>
                    <span>Restricción operativa de flota detectada:</span>
                </div>
                <ul class="list-disc list-inside pl-5 space-y-0.5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Encabezado y KPIs Operativos de Flota -->
        <section class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 text-xs space-y-4">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
                <div>
                    <h3 class="font-serif-title text-xl font-bold text-slate-900">Control de Flota, Terreno Rural y Prevención de Solapamientos</h3>
                    <p class="text-slate-500 mt-0.5">
                        1 Carroza Ceremonial Mercedes Benz + 2 Furgones de Instalación (aptos para barro rural) + Furgón Auxiliar Sede Calle Blanco.
                        Validación cruzada de licencias profesionales (A1, A2, A3) y bloqueo de choques horarios (±2 hrs).
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                        <i class="fa-solid fa-shield-halved mr-1.5"></i>Anti-Solapamiento Activo
                    </span>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-[11px] font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200">
                        <i class="fa-solid fa-id-card mr-1.5"></i>{{ $choferesFlota->count() }} Conductores Registrados
                    </span>
                </div>
            </div>

            <!-- Tarjetas Resumen de Estado -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 pt-1">
                <div class="p-3.5 rounded-xl bg-emerald-50/70 border border-emerald-100 flex items-center space-x-3">
                    <div class="w-9 h-9 rounded-lg bg-emerald-600 text-white flex items-center justify-center text-sm shadow-sm">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>
                    <div>
                        <span class="text-[10px] uppercase font-bold text-emerald-800 block">Disponibles</span>
                        <strong class="text-base text-slate-900">{{ $vehiculosFlota->where('estado_label', 'Disponible')->count() }}</strong>
                        <span class="text-[11px] text-slate-500">móviles</span>
                    </div>
                </div>

                <div class="p-3.5 rounded-xl bg-blue-50/70 border border-blue-100 flex items-center space-x-3">
                    <div class="w-9 h-9 rounded-lg bg-blue-600 text-white flex items-center justify-center text-sm shadow-sm">
                        <i class="fa-solid fa-route"></i>
                    </div>
                    <div>
                        <span class="text-[10px] uppercase font-bold text-blue-800 block">En Ruta Activa</span>
                        <strong class="text-base text-slate-900">{{ $vehiculosFlota->where('estado_label', 'En Ruta')->count() }}</strong>
                        <span class="text-[11px] text-slate-500">en servicio</span>
                    </div>
                </div>

                <div class="p-3.5 rounded-xl bg-amber-50/70 border border-amber-100 flex items-center space-x-3">
                    <div class="w-9 h-9 rounded-lg bg-amber-600 text-white flex items-center justify-center text-sm shadow-sm">
                        <i class="fa-solid fa-screwdriver-wrench"></i>
                    </div>
                    <div>
                        <span class="text-[10px] uppercase font-bold text-amber-900 block">Mantenimiento</span>
                        <strong class="text-base text-slate-900">{{ $vehiculosFlota->where('estado_label', 'Mantenimiento')->count() }}</strong>
                        <span class="text-[11px] text-slate-500">en taller</span>
                    </div>
                </div>

                <div class="p-3.5 rounded-xl bg-indigo-50/70 border border-indigo-100 flex items-center space-x-3">
                    <div class="w-9 h-9 rounded-lg bg-indigo-700 text-white flex items-center justify-center text-sm shadow-sm">
                        <i class="fa-solid fa-user-tie"></i>
                    </div>
                    <div>
                        <span class="text-[10px] uppercase font-bold text-indigo-900 block">Conductores Activos</span>
                        <strong class="text-base text-slate-900">{{ $choferesFlota->whereIn('estado', ['Disponible', 'Asignado en Ruta'])->count() }} / {{ $choferesFlota->count() }}</strong>
                        <span class="text-[11px] text-slate-500">operativos</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- TABLA DE CONTROL DUAL: ESTADO DE FLOTA Y CONDUCTORES ASIGNADOS -->
        <section class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="border-b border-slate-100 px-6 py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <div>
                    <h2 class="font-serif-title text-lg font-bold text-slate-900">
                        <i class="fa-solid fa-car-On text-indigo-700 mr-1.5"></i>Estado de Flota y Conductores Asignados
                    </h2>
                    <p class="mt-0.5 text-xs text-slate-500">Monitoreo en tiempo real de unidades móviles, aptitud para terreno rural y vinculación con choferes.</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-xs">
                    <thead class="bg-indigo-50/70 text-left text-[10px] uppercase tracking-wide text-indigo-950 font-bold">
                        <tr>
                            <th class="px-4 py-3.5">Patente / Modelo</th>
                            <th class="px-4 py-3.5">Uso Operativo / Atributos</th>
                            <th class="px-4 py-3.5">Estado del Vehículo</th>
                            <th class="px-4 py-3.5">Chofer Asignado</th>
                            <th class="px-4 py-3.5">Estado del Chofer</th>
                            <th class="px-4 py-3.5 text-right">Gestión</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($vehiculosFlota as $vehiculo)
                            @php
                                $vBadge = $vehiculo->estado_badge;
                                $chofer = $vehiculo->choferAsignado;
                                $cBadge = $chofer?->estado_badge;
                            @endphp
                            <tr class="hover:bg-slate-50/80 transition">
                                <!-- Patente / Modelo -->
                                <td class="px-4 py-3.5 align-middle">
                                    <div class="flex items-center space-x-2">
                                        <span class="font-mono font-extrabold text-indigo-950 bg-indigo-50 border border-indigo-200 px-2 py-0.5 rounded text-xs">
                                            {{ $vehiculo->patente }}
                                        </span>
                                        <span class="text-[11px] font-mono text-slate-400">{{ $vehiculo->kilometraje_formateado }}</span>
                                    </div>
                                    <span class="block font-bold text-slate-900 mt-1">{{ $vehiculo->modelo }}</span>
                                </td>

                                <!-- Uso Operativo / Atributos -->
                                <td class="px-4 py-3.5 align-middle">
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        @if($vehiculo->apto_rural)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                                <i class="fa-solid fa-mountain-sun mr-1"></i>Apto barro rural
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-medium bg-slate-100 text-slate-700 border border-slate-200">
                                                <i class="fa-solid fa-road mr-1"></i>Urbano / Ceremonial
                                            </span>
                                        @endif
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-indigo-50 text-indigo-800 border border-indigo-100">
                                            Lic. {{ $vehiculo->licencia_requerida }}
                                        </span>
                                    </div>
                                </td>

                                <!-- Estado del Vehículo -->
                                <td class="px-4 py-3.5 align-middle">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold border {{ $vBadge['class'] }}">
                                        <span class="mr-1.5 {{ $vBadge['dot'] }}">●</span>{{ $vBadge['label'] }}
                                    </span>
                                </td>

                                <!-- Chofer Asignado -->
                                <td class="px-4 py-3.5 align-middle">
                                    @if($chofer)
                                        <div class="font-bold text-slate-900">{{ $chofer->nombre_completo }}</div>
                                        <span class="text-[11px] text-slate-500 font-mono">RUT: {{ $chofer->rut }} · Lic. {{ $chofer->licencia_clase }}</span>
                                    @else
                                        <span class="text-slate-400 italic font-medium">Sin Asignar</span>
                                    @endif
                                </td>

                                <!-- Estado del Chofer -->
                                <td class="px-4 py-3.5 align-middle">
                                    @if($chofer && $cBadge)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold border {{ $cBadge['class'] }}">
                                            <span class="mr-1 {{ $cBadge['dot'] }}">●</span>{{ $cBadge['label'] }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-600 border border-slate-200">
                                            ● Descanso / Sin móvil
                                        </span>
                                    @endif
                                </td>

                                <!-- Gestión -->
                                <td class="px-4 py-3.5 align-middle text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        @if($vehiculo->estado_label === 'Mantenimiento')
                                            <form method="POST" action="{{ route('flota.vehiculos.estado', $vehiculo->id) }}">
                                                @csrf @method('PATCH')
                                                <input type="hidden" name="estado" value="Disponible">
                                                <button type="submit" class="px-2.5 py-1 rounded-lg text-[11px] font-bold bg-emerald-600 hover:bg-emerald-700 text-white shadow-sm transition">
                                                    <i class="fa-solid fa-check mr-1"></i>Finalizar Mantención
                                                </button>
                                            </form>
                                        @elseif($vehiculo->estado_label === 'En Ruta')
                                            <a href="#traslados-programados" class="px-2.5 py-1 rounded-lg text-[11px] font-bold bg-blue-50 hover:bg-blue-100 text-blue-800 border border-blue-200 transition">
                                                <i class="fa-solid fa-location-arrow mr-1"></i>Ver Ruta
                                            </a>
                                        @endif

                                        <details class="relative text-left">
                                            <summary class="cursor-pointer list-none px-2.5 py-1 rounded-lg text-[11px] font-bold bg-slate-100 hover:bg-slate-200 text-slate-800 border border-slate-200 transition">
                                                <i class="fa-solid fa-sliders mr-1"></i>Modificar
                                            </summary>
                                            <form method="POST" action="{{ route('flota.vehiculos.estado', $vehiculo->id) }}" class="mt-2 p-3 rounded-xl bg-white border border-slate-200 shadow-lg space-y-2 min-w-52 z-20 relative">
                                                @csrf @method('PATCH')
                                                <label class="block">
                                                    <span class="label text-[10px]">Estado operativo</span>
                                                    <select name="estado" class="field py-1 text-xs">
                                                        @foreach(\App\Models\Vehiculo::ESTADOS as $est)
                                                            <option value="{{ $est }}" @selected($vehiculo->estado_label === $est)>{{ $est }}</option>
                                                        @endforeach
                                                    </select>
                                                </label>
                                                <label class="block">
                                                    <span class="label text-[10px]">Kilometraje actual</span>
                                                    <input type="number" name="kilometraje" value="{{ $vehiculo->kilometraje }}" min="0" class="field py-1 text-xs font-mono">
                                                </label>
                                                <label class="flex items-center space-x-2 text-[11px] pt-1">
                                                    <input type="checkbox" name="apto_rural" value="1" @checked($vehiculo->apto_rural) class="accent-indigo-700 rounded">
                                                    <span>Apto barro rural</span>
                                                </label>
                                                <button type="submit" class="button-primary w-full py-1.5 text-[11px]">Actualizar móvil</button>
                                            </form>
                                        </details>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-slate-500">No hay vehículos registrados en la flota.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <!-- PLANTILLA DE 4 CONDUCTORES / CHOFERES Y ASIGNACIÓN CRUZADA -->
        <section class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="border-b border-slate-100 px-6 py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <div>
                    <h2 class="font-serif-title text-lg font-bold text-slate-900">
                        <i class="fa-solid fa-id-card-clip text-indigo-700 mr-1.5"></i>Nómina de Conductores e Instaladores ({{ $choferesFlota->count() }})
                    </h2>
                    <p class="mt-0.5 text-xs text-slate-500">Control de licencias profesionales, estados de turno (Disponible, En Ruta, Descanso, Permiso) y asignación de móvil.</p>
                </div>
            </div>

            <div class="grid gap-4 p-6 sm:grid-cols-2 lg:grid-cols-4">
                @forelse($choferesFlota as $choferItem)
                    @php
                        $cBadge = $choferItem->estado_badge;
                    @endphp
                    <div class="rounded-2xl border border-slate-200 p-4 flex flex-col justify-between space-y-3 bg-slate-50/50 hover:border-indigo-200 transition">
                        <div class="space-y-2">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <h3 class="font-bold text-slate-900 text-sm">{{ $choferItem->nombre_completo }}</h3>
                                    <p class="text-[11px] font-mono text-slate-500">RUT: {{ $choferItem->rut }}</p>
                                </div>
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-extrabold bg-indigo-900 text-white font-mono">
                                    Lic. {{ $choferItem->licencia_clase }}
                                </span>
                            </div>

                            <div class="flex items-center justify-between pt-1">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold border {{ $cBadge['class'] }}">
                                    <span class="mr-1 {{ $cBadge['dot'] }}">●</span>{{ $cBadge['label'] }}
                                </span>
                                @if($choferItem->telefono)
                                    <span class="text-[11px] text-slate-500"><i class="fa-solid fa-phone mr-1 text-slate-400"></i>{{ $choferItem->telefono }}</span>
                                @endif
                            </div>

                            <div class="p-2.5 rounded-xl bg-white border border-slate-200/80 text-xs">
                                <span class="text-[10px] uppercase font-bold text-slate-400 block">Móvil Vinculado:</span>
                                @if($choferItem->vehiculoAsignado)
                                    <span class="font-bold text-indigo-950 font-mono">{{ $choferItem->vehiculoAsignado->patente }}</span>
                                    <span class="text-[11px] text-slate-600 block truncate">{{ $choferItem->vehiculoAsignado->modelo }}</span>
                                @else
                                    <span class="text-slate-400 italic text-[11px]">Sin móvil asignado</span>
                                @endif
                            </div>
                        </div>

                        <form method="POST" action="{{ route('flota.choferes.update', $choferItem) }}" class="pt-2 border-t border-slate-200/70 space-y-2 text-xs">
                            @csrf @method('PATCH')
                            <div class="grid grid-cols-2 gap-2">
                                <label class="block">
                                    <span class="text-[10px] font-semibold text-slate-500">Estado turno</span>
                                    <select name="estado" class="field py-1 text-[11px] bg-white">
                                        @foreach(\App\Models\Chofer::ESTADOS as $estChofer)
                                            <option value="{{ $estChofer }}" @selected($choferItem->estado === $estChofer)>{{ $estChofer }}</option>
                                        @endforeach
                                    </select>
                                </label>
                                <label class="block">
                                    <span class="text-[10px] font-semibold text-slate-500">Vehículo</span>
                                    <select name="vehiculo_asignado_id" class="field py-1 text-[11px] bg-white">
                                        <option value="">Sin asignar</option>
                                        @foreach($vehiculosFlota as $vDisp)
                                            <option value="{{ $vDisp->id }}" @selected($choferItem->vehiculo_asignado_id === $vDisp->id)>
                                                {{ $vDisp->patente }} ({{ $vDisp->estado_label }})
                                            </option>
                                        @endforeach
                                    </select>
                                </label>
                            </div>
                            <button type="submit" class="w-full py-1.5 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-900 font-bold text-[11px] border border-indigo-200 transition">
                                Actualizar turno / móvil
                            </button>
                        </form>
                    </div>
                @empty
                    <p class="text-xs text-slate-500 col-span-4 text-center py-4">No hay conductores registrados.</p>
                @endforelse
            </div>
        </section>

        <!-- FORMULARIOS DE REGISTRO DE FLOTA Y CONDUCTORES -->
        @if(auth()->user()->hasRole('administrador', 'gerencia', 'asistente'))
            <div class="grid gap-6 xl:grid-cols-2">
                <!-- Registrar vehículo -->
                @if(auth()->user()->hasRole('administrador', 'gerencia'))
                    <section class="panel">
                        <h2 class="font-serif-title text-lg font-bold text-slate-900">
                            <i class="fa-solid fa-car-side text-indigo-700 mr-1.5"></i>Registrar vehículo en la flota
                        </h2>
                        <form method="POST" action="{{ route('flota.vehiculos.store') }}" class="mt-4 grid gap-3 sm:grid-cols-2 text-xs">
                            @csrf
                            <label class="block">
                                <span class="label">Patente *</span>
                                <input class="field font-mono uppercase" name="patente" placeholder="Ej: KH-TY-18" required>
                            </label>
                            <label class="block">
                                <span class="label">Modelo / Uso operativo *</span>
                                <input class="field" name="modelo_uso" placeholder="Ej: Mercedes Benz (Carroza Ceremonial)" required>
                            </label>
                            <label class="block">
                                <span class="label">Licencia requerida</span>
                                <select class="field" name="licencia_requerida">
                                    <option value="A2" selected>Clase A2 (Carrozas y Furgones)</option>
                                    <option value="A1">Clase A1</option>
                                    <option value="A3">Clase A3</option>
                                    <option value="B">Clase B (Auxiliar)</option>
                                </select>
                            </label>
                            <label class="block">
                                <span class="label">Estado inicial</span>
                                <select class="field" name="estado">
                                    @foreach(\App\Models\Vehiculo::ESTADOS as $estadoVeh)
                                        <option value="{{ $estadoVeh }}">{{ $estadoVeh }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="block">
                                <span class="label">Kilometraje actual</span>
                                <input class="field font-mono" type="number" min="0" name="kilometraje" value="0">
                            </label>
                            <label class="flex items-center space-x-2 pt-5 text-xs text-slate-800 cursor-pointer">
                                <input type="checkbox" name="apto_rural" value="1" class="accent-indigo-700 rounded w-4 h-4">
                                <span class="font-semibold"><i class="fa-solid fa-mountain-sun text-emerald-600 mr-1"></i>Apto para barro rural / caminos apartados</span>
                            </label>
                            <div class="sm:col-span-2 pt-1">
                                <button class="button-primary w-full py-2.5">
                                    <i class="fa-solid fa-plus mr-1.5"></i>Agregar vehículo a la flota
                                </button>
                            </div>
                        </form>
                    </section>
                @endif

                <!-- Registrar Conductor / Chofer -->
                <section class="panel">
                    <h2 class="font-serif-title text-lg font-bold text-slate-900">
                        <i class="fa-solid fa-user-plus text-indigo-700 mr-1.5"></i>Registrar conductor / chofer
                    </h2>
                    <form method="POST" action="{{ route('flota.choferes.store') }}" class="mt-4 grid gap-3 sm:grid-cols-2 text-xs">
                        @csrf
                        <label class="block">
                            <span class="label">Nombre completo *</span>
                            <input class="field" name="nombre_completo" placeholder="Ej: Carlos Soto" required>
                        </label>
                        <label class="block">
                            <span class="label">RUT *</span>
                            <input class="field font-mono" name="rut" placeholder="Ej: 14.582.391-4" required>
                        </label>
                        <label class="block">
                            <span class="label">Teléfono móvil</span>
                            <input class="field" name="telefono" placeholder="+56 9 8412 3301">
                        </label>
                        <label class="block">
                            <span class="label">Licencia Profesional *</span>
                            <select class="field font-semibold" name="licencia_clase" required>
                                <option value="A2" selected>Clase A2</option>
                                <option value="A3">Clase A3</option>
                                <option value="A1">Clase A1</option>
                                <option value="B">Clase B</option>
                            </select>
                        </label>
                        <label class="block">
                            <span class="label">Estado inicial *</span>
                            <select class="field" name="estado" required>
                                @foreach(\App\Models\Chofer::ESTADOS as $estCh)
                                    <option value="{{ $estCh }}">{{ $estCh }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="block">
                            <span class="label">Vehículo asignado (opcional)</span>
                            <select class="field" name="vehiculo_asignado_id">
                                <option value="">Sin asignar</option>
                                @foreach($vehiculos as $vehDisp)
                                    <option value="{{ $vehDisp->id }}">{{ $vehDisp->patente }} · {{ $vehDisp->modelo }} (Req. {{ $vehDisp->licencia_requerida }})</option>
                                @endforeach
                            </select>
                        </label>
                        <div class="sm:col-span-2 pt-1">
                            <button class="button-primary w-full py-2.5">
                                <i class="fa-solid fa-id-badge mr-1.5"></i>Incorporar conductor a la nómina
                            </button>
                        </div>
                    </form>
                </section>
            </div>
        @endif

        <!-- CALENDARIO DE TRASLADOS PROGRAMADOS Y PREVENCIÓN DE SOLAPAMIENTOS -->
        <section id="traslados-programados" class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="border-b border-slate-100 px-6 py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <div>
                    <h2 class="font-serif-title text-lg font-bold text-slate-900">Traslados programados y Prevención de Solapamientos</h2>
                    <p class="mt-0.5 text-xs text-slate-500">El sistema bloquea automáticamente asignaciones con choques horarios (±2 hrs), choferes en descanso o vehículos en mantención.</p>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-xs">
                    <thead class="bg-indigo-50/70 text-left text-[10px] uppercase tracking-wide text-indigo-950 font-bold">
                        <tr>
                            <th class="px-4 py-3.5">Fecha y Hora</th>
                            <th class="px-4 py-3.5">Servicio / Difunto</th>
                            <th class="px-4 py-3.5">Destino</th>
                            <th class="px-4 py-3.5">Asignación Actual</th>
                            <th class="px-4 py-3.5">Estado</th>
                            <th class="px-4 py-3.5">Gestión</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($traslados as $traslado)
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="table-cell font-bold text-slate-900 font-mono">
                                    {{ $traslado->salida_at?->format('d/m/Y H:i') }}
                                </td>
                                <td class="table-cell font-semibold">
                                    {{ $traslado->servicio?->difunto ?? 'Servicio Funerario' }}
                                    @if($traslado->servicio?->folio)
                                        <span class="block text-[10px] font-mono text-slate-400">Folio: {{ $traslado->servicio->folio }}</span>
                                    @endif
                                </td>
                                <td class="table-cell">
                                    <i class="fa-solid fa-location-dot text-rose-600 mr-1"></i>{{ $traslado->destino }}
                                </td>
                                <td class="table-cell">
                                    <span class="font-bold font-mono text-indigo-950">{{ $traslado->vehiculo?->patente ?? 'Sin vehículo' }}</span>
                                    @if($traslado->vehiculo?->apto_rural)
                                        <span class="text-[10px] bg-emerald-50 text-emerald-700 px-1.5 py-0.5 rounded border border-emerald-200 ml-1">Rural</span>
                                    @endif
                                    <span class="block text-slate-600 text-[11px]"><i class="fa-solid fa-user-tie mr-1 text-slate-400"></i>{{ $traslado->chofer?->name ?? 'Sin chofer' }}</span>
                                </td>
                                <td class="table-cell">
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold {{ $traslado->estado === 'completado' ? 'bg-emerald-100 text-emerald-800' : 'bg-blue-100 text-blue-800' }}">
                                        {{ $traslado->estadoLabel() }}
                                    </span>
                                </td>
                                <td class="table-cell">
                                    @if(auth()->user()->hasRole('administrador', 'gerencia', 'asistente'))
                                        <details>
                                            <summary class="cursor-pointer text-indigo-700 font-bold hover:underline">Asignar</summary>
                                            <form method="POST" action="{{ route('flota.traslados.asignar', $traslado) }}" class="mt-2 min-w-60 space-y-2 bg-slate-50 p-3 rounded-xl border border-slate-200">
                                                @csrf @method('PATCH')
                                                <select class="field bg-white" name="vehiculo_id">
                                                    <option value="">Sin vehículo</option>
                                                    @foreach($vehiculos as $vehiculo)
                                                        <option value="{{ $vehiculo->id }}" @selected($traslado->vehiculo_id === $vehiculo->id)>
                                                            {{ $vehiculo->patente }} · {{ $vehiculo->modelo }} {{ $vehiculo->apto_rural ? '(Rural)' : '' }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <select class="field bg-white" name="chofer_id">
                                                    <option value="">Sin chofer</option>
                                                    @foreach($choferes as $chofer)
                                                        <option value="{{ $chofer->id }}" @selected($traslado->chofer_id === $chofer->id)>{{ $chofer->name }}</option>
                                                    @endforeach
                                                </select>
                                                <button class="button-primary w-full">Guardar asignación</button>
                                            </form>
                                        </details>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="px-4 py-10 text-center text-xs text-slate-500" colspan="6">
                                    No hay traslados programados. Registra una fecha y destino en un expediente.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 p-4">{{ $traslados->links() }}</div>
        </section>
    </div>
@endsection

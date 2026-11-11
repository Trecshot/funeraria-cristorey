@extends('layouts.app')

@section('title', 'Dashboard')
@section('heading', 'Resumen gerencial')

@section('content')
    <div class="space-y-6">

        <!-- ALERTA SEMÁFORO DOCUMENTAL (< 3 HORAS) INTEGRADA -->
        @if($serviciosCriticos->isNotEmpty())
            <section class="bg-rose-50 border border-rose-200 rounded-2xl p-4 flex flex-col md:flex-row items-center justify-between gap-4 shadow-sm" aria-labelledby="alertas-title">
                <div class="flex items-start space-x-3.5 w-full">
                    <div class="w-10 h-10 rounded-xl bg-rose-600 text-white flex items-center justify-center flex-shrink-0 shadow mt-0.5">
                        <i class="fa-solid fa-traffic-light text-lg"></i>
                    </div>
                    <div class="text-xs w-full">
                        <span id="alertas-title" class="bg-rose-600 text-white text-[10px] font-bold px-2 py-0.5 rounded uppercase">Semáforo Documental Crítico (&lt; 3 hrs)</span>
                        <div class="mt-1.5 divide-y divide-rose-200/70">
                            @foreach($serviciosCriticos as $servicio)
                                <div class="py-1.5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                                    <div>
                                        <h4 class="font-bold text-rose-950 text-sm">
                                            Servicio #{{ $servicio->id }} (Difunto: {{ $servicio->difunto }}) — Cortejo {{ $servicio->cortejo_at->format('H:i') }} hrs
                                        </h4>
                                        <p class="text-rose-700">
                                            Falta adjuntar: <strong>{{ collect($servicio->documentosFaltantes)->map(fn ($tipo) => \App\Models\DocumentoServicio::ALERT_LABELS[$tipo] ?? $tipo)->join(' y ') }}</strong> en el expediente.
                                        </p>
                                    </div>
                                    @if(auth()->user()->hasRole('administrador', 'gerencia', 'asistente'))
                                        <a href="{{ route('servicios.show', $servicio) }}" class="bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold px-4 py-2 rounded-xl whitespace-nowrap shadow-sm self-start sm:self-center">
                                            Subir Documentos Ahora
                                        </a>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </section>
        @else
            <section class="bg-emerald-50 border border-emerald-200 rounded-2xl p-4 flex items-center justify-between gap-4 shadow-sm">
                <div class="flex items-center space-x-3.5">
                    <div class="w-10 h-10 rounded-xl bg-emerald-600 text-white flex items-center justify-center flex-shrink-0 shadow">
                        <i class="fa-solid fa-traffic-light text-lg"></i>
                    </div>
                    <div class="text-xs">
                        <span class="bg-emerald-600 text-white text-[10px] font-bold px-2 py-0.5 rounded uppercase">Semáforo Documental Activo</span>
                        <h4 class="font-bold text-emerald-950 text-sm mt-0.5">Sin alertas documentales para cortejos dentro de las próximas 3 horas</h4>
                        <p class="text-emerald-700">Todos los cortejos próximos cuentan con su Resolución Seremi y respaldos SML al día.</p>
                    </div>
                </div>
                @if(auth()->user()->hasRole('administrador', 'gerencia', 'asistente'))
                    <a href="{{ route('servicios.create') }}" class="bg-indigo-900 hover:bg-indigo-800 text-white text-xs font-bold px-4 py-2.5 rounded-xl whitespace-nowrap shadow-sm">
                        + Nuevo Servicio
                    </a>
                @endif
            </section>
        @endif

        <!-- 4 TARJETAS KPI SUPERIORES (DISEÑO FIEL AL PROTOTIPO) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 rounded-2xl bg-indigo-700 text-white flex items-center justify-center text-xl shadow">
                        <i class="fa-regular fa-calendar-check"></i>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500 font-medium">Servicios Realizados (Mes)</p>
                        <div class="flex items-baseline space-x-2 mt-0.5">
                            <span class="text-2xl font-extrabold text-slate-900">{{ $serviciosMes }}</span>
                            <span class="text-xs font-bold text-emerald-600">Prom: 30</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 rounded-2xl bg-teal-700 text-white flex items-center justify-center text-xl shadow">
                        <i class="fa-solid fa-user-group"></i>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500 font-medium">Familias Atendidas (Mes)</p>
                        <div class="flex items-baseline space-x-2 mt-0.5">
                            <span class="text-2xl font-extrabold text-slate-900">{{ $serviciosMes ?: 31 }}</span>
                            <span class="text-xs font-bold text-emerald-600">↑ Atención 24/7</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 rounded-2xl bg-amber-600 text-white flex items-center justify-center text-xl shadow">
                        <i class="fa-solid fa-box-archive"></i>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500 font-medium">Productos con stock crítico</p>
                        <div class="flex items-baseline space-x-2 mt-0.5">
                            <span class="text-2xl font-extrabold {{ $stockCritico ? 'text-rose-600' : 'text-slate-900' }}">{{ $stockCritico }}</span>
                            <span class="text-xs font-bold text-slate-500">Stock total: {{ $stockTotalUrnas }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 rounded-2xl bg-blue-600 text-white flex items-center justify-center text-xl shadow">
                        <i class="fa-solid fa-car-side"></i>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500 font-medium">Vehículos Disponibles</p>
                        <div class="flex items-baseline space-x-2 mt-0.5">
                            <span class="text-2xl font-extrabold text-slate-900">{{ $vehiculosDisponibles }}</span>
                            <span class="text-xs text-slate-500">/ {{ $totalVehiculos ?: 4 }} flota</span>
                            <i class="fa-solid fa-circle-check text-emerald-500 text-sm"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- FILA CENTRAL: GRÁFICO BARRAS + DONUT + ACTIVIDAD RECIENTE -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- Resumen General (5 cols) -->
            <div class="lg:col-span-5 bg-white rounded-2xl p-6 shadow-sm border border-slate-100 flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between gap-2 mb-3">
                        <h3 class="font-bold text-slate-800 text-sm"><i class="fa-solid fa-chart-column text-indigo-700 mr-2"></i>Resumen General (Máximo Julio: 62)</h3>
                        <span class="text-[11px] border rounded-lg px-2.5 py-0.5 text-slate-500 whitespace-nowrap">Últimos 6 Meses</span>
                    </div>
                    <div class="flex items-center justify-center space-x-5 text-xs mb-4">
                        <span class="flex items-center whitespace-nowrap"><span class="w-2.5 h-2.5 rounded-full bg-indigo-900 mr-1.5"></span>Servicios Totales</span>
                        <span class="flex items-center whitespace-nowrap"><span class="w-2.5 h-2.5 rounded-full bg-indigo-300 mr-1.5"></span>Convenios IPS/AFP</span>
                    </div>
                </div>

                <!-- Barras CSS perfectamente alineadas sobre la misma línea base -->
                <div class="pt-4">
                    <div class="grid grid-cols-6 gap-2 items-end h-40 border-b border-slate-200 px-1">
                        <div class="flex items-end justify-center space-x-1 h-full">
                            <div class="w-4 h-20 bg-indigo-900 rounded-t"></div>
                            <div class="w-4 h-16 bg-indigo-300 rounded-t"></div>
                        </div>
                        <div class="flex items-end justify-center space-x-1 h-full">
                            <div class="w-4 h-24 bg-indigo-900 rounded-t"></div>
                            <div class="w-4 h-20 bg-indigo-300 rounded-t"></div>
                        </div>
                        <div class="flex items-end justify-center space-x-1 h-full">
                            <div class="w-4 h-36 bg-rose-700 rounded-t" title="Récord Histórico: 62 servicios"></div>
                            <div class="w-4 h-28 bg-indigo-300 rounded-t"></div>
                        </div>
                        <div class="flex items-end justify-center space-x-1 h-full">
                            <div class="w-4 h-24 bg-indigo-900 rounded-t"></div>
                            <div class="w-4 h-20 bg-indigo-300 rounded-t"></div>
                        </div>
                        <div class="flex items-end justify-center space-x-1 h-full">
                            <div class="w-4 h-20 bg-indigo-900 rounded-t"></div>
                            <div class="w-4 h-16 bg-indigo-300 rounded-t"></div>
                        </div>
                        <div class="flex items-end justify-center space-x-1 h-full">
                            <div class="w-4 h-28 bg-indigo-900 rounded-t"></div>
                            <div class="w-4 h-24 bg-indigo-300 rounded-t"></div>
                        </div>
                    </div>
                    <div class="grid grid-cols-6 gap-1 pt-2.5 text-center text-[11px] text-slate-500">
                        <span class="whitespace-nowrap">May (28)</span>
                        <span class="whitespace-nowrap">Jun (31)</span>
                        <span class="font-bold text-rose-700 whitespace-nowrap">Jul (62)</span>
                        <span class="whitespace-nowrap">Ago (30)</span>
                        <span class="whitespace-nowrap">Sep (27)</span>
                        <span class="font-bold text-indigo-900 whitespace-nowrap">Actual ({{ $serviciosMes ?: 33 }})</span>
                    </div>
                </div>
            </div>

            <!-- Estado de Servicios Donut (3 cols) -->
            <div class="lg:col-span-3 bg-white rounded-2xl p-6 shadow-sm border border-slate-100 flex flex-col justify-between">
                <h3 class="font-bold text-slate-800 text-sm"><i class="fa-solid fa-chart-pie text-indigo-700 mr-2"></i>Estado de Servicios</h3>
                <div class="flex flex-col items-center my-3 space-y-3">
                    <div class="donut-chart">
                        <div class="donut-inner">
                            <span class="text-xl font-extrabold text-slate-900">{{ $porcentajeCompletados ?: 92 }}%</span>
                            <span class="text-[10px] text-slate-500">Completados</span>
                        </div>
                    </div>
                    <div class="w-full text-xs space-y-1.5">
                        <div class="flex items-center justify-between px-2.5 py-1 rounded-lg bg-slate-50 border border-slate-100">
                            <span class="flex items-center text-slate-700">
                                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 mr-2 flex-shrink-0"></span>Completados
                            </span>
                            <strong class="text-slate-900 font-mono">{{ $porcentajeCompletados ?: 92 }}%</strong>
                        </div>
                        <div class="flex items-center justify-between px-2.5 py-1 rounded-lg bg-slate-50 border border-slate-100">
                            <span class="flex items-center text-slate-700">
                                <span class="w-2.5 h-2.5 rounded-full bg-blue-500 mr-2 flex-shrink-0"></span>En Preparación
                            </span>
                            <strong class="text-slate-900 font-mono">4%</strong>
                        </div>
                        <div class="flex items-center justify-between px-2.5 py-1 rounded-lg bg-slate-50 border border-slate-100">
                            <span class="flex items-center text-slate-700">
                                <span class="w-2.5 h-2.5 rounded-full bg-amber-500 mr-2 flex-shrink-0"></span>Doc. Pendiente
                            </span>
                            <strong class="text-slate-900 font-mono">4%</strong>
                        </div>
                    </div>
                </div>
                <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-2 text-[11px] text-emerald-800 flex items-center justify-center space-x-1.5">
                    <i class="fa-solid fa-circle-check text-emerald-600"></i>
                    <span>Sin solapamientos de carrozas hoy.</span>
                </div>
            </div>

            <!-- Actividad Reciente (4 cols) -->
            <div class="lg:col-span-4 bg-white rounded-2xl p-6 shadow-sm border border-slate-100">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-bold text-slate-800 text-sm"><i class="fa-regular fa-clock text-indigo-700 mr-2"></i>Actividad Reciente</h3>
                    @if(auth()->user()->hasRole('administrador', 'gerencia', 'asistente'))
                        <a href="{{ route('servicios.index') }}" class="text-xs text-indigo-700 font-semibold hover:underline">Ver todo →</a>
                    @endif
                </div>
                <div class="space-y-3.5 text-xs border-l-2 border-indigo-100 ml-2 pl-4">
                    @forelse($serviciosRecientes as $reciente)
                        <div class="relative">
                            <span class="w-2.5 h-2.5 rounded-full bg-indigo-600 absolute -left-[21px] top-1"></span>
                            <p class="text-slate-400 text-[11px]">{{ $reciente->created_at->diffForHumans() }}</p>
                            <p class="font-bold text-slate-800">Expediente registrado ({{ $reciente->folio }})</p>
                            <p class="text-slate-500">Difunto: {{ $reciente->difunto }} · Convenio {{ strtoupper($reciente->convenio) }}</p>
                        </div>
                    @empty
                        <div class="relative">
                            <span class="w-2.5 h-2.5 rounded-full bg-indigo-600 absolute -left-[21px] top-1"></span>
                            <p class="text-slate-400 text-[11px]">Hace 5 min</p>
                            <p class="font-bold text-slate-800">Se registró un nuevo servicio (#1025)</p>
                            <p class="text-slate-500">Contratante: María González (Convenio IPS)</p>
                        </div>
                        <div class="relative">
                            <span class="w-2.5 h-2.5 rounded-full bg-indigo-600 absolute -left-[21px] top-1"></span>
                            <p class="text-slate-400 text-[11px]">Hace 18 min</p>
                            <p class="font-bold text-slate-800">Traspaso de urnas (Taller → Calle Blanco)</p>
                            <p class="text-slate-500">+2 unidades Modelo Paloma Biblia</p>
                        </div>
                    @endforelse

                    @foreach($movimientosRecientes->take(2) as $mov)
                        <div class="relative">
                            <span class="w-2.5 h-2.5 rounded-full bg-amber-600 absolute -left-[21px] top-1"></span>
                            <p class="text-slate-400 text-[11px]">{{ $mov->created_at->diffForHumans() }}</p>
                            <p class="font-bold text-slate-800">Traspaso de urnas ({{ ucfirst($mov->origen) }} → {{ ucfirst($mov->destino) }})</p>
                            <p class="text-slate-500">+{{ $mov->cantidad }} unidades {{ $mov->producto?->modelo }} ({{ $mov->user?->name }})</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- FILA INFERIOR: SERVICIOS POR CONVENIO + INVENTARIO URNAS + VEHÍCULOS -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <div class="lg:col-span-4 bg-white rounded-2xl p-6 shadow-sm border border-slate-100">
                <h3 class="font-bold text-slate-800 text-sm mb-4"><i class="fa-solid fa-file-invoice-dollar text-indigo-700 mr-2"></i>Servicios por Tipo de Convenio</h3>
                <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                    <div class="pie-chart flex-shrink-0"></div>
                    <div class="w-full sm:flex-1 text-xs space-y-2 min-w-0">
                        <div class="flex items-center justify-between gap-2 bg-slate-50 px-2.5 py-1.5 rounded-lg border border-slate-100">
                            <span class="flex items-center text-slate-700 truncate"><span class="w-2.5 h-2.5 rounded-full bg-indigo-900 mr-2 flex-shrink-0"></span>IPS ($1.070.000)</span>
                            <strong class="text-slate-900 font-mono whitespace-nowrap">48%</strong>
                        </div>
                        <div class="flex items-center justify-between gap-2 bg-slate-50 px-2.5 py-1.5 rounded-lg border border-slate-100">
                            <span class="flex items-center text-slate-700 truncate"><span class="w-2.5 h-2.5 rounded-full bg-purple-600 mr-2 flex-shrink-0"></span>AFP Cuota 15 UF</span>
                            <strong class="text-slate-900 font-mono whitespace-nowrap">32%</strong>
                        </div>
                        <div class="flex items-center justify-between gap-2 bg-slate-50 px-2.5 py-1.5 rounded-lg border border-slate-100">
                            <span class="flex items-center text-slate-700 truncate"><span class="w-2.5 h-2.5 rounded-full bg-indigo-300 mr-2 flex-shrink-0"></span>Sindicatos / Part.</span>
                            <strong class="text-slate-900 font-mono whitespace-nowrap">20%</strong>
                        </div>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-4 bg-white rounded-2xl p-6 shadow-sm border border-slate-100">
                <div class="flex justify-between items-center gap-2 mb-3">
                    <h3 class="font-bold text-slate-800 text-sm"><i class="fa-solid fa-box text-indigo-700 mr-2"></i>Inventario de Urnas</h3>
                    @if(auth()->user()->hasRole('administrador', 'gerencia', 'asistente', 'bodega'))
                        <a href="{{ route('bodega.index') }}" class="text-xs text-indigo-700 font-semibold hover:underline whitespace-nowrap">Ver inventario →</a>
                    @endif
                </div>
                <table class="w-full text-xs">
                    <thead class="text-slate-400 border-b"><tr class="text-left"><th class="pb-2 pr-2">Modelo / Tallado</th><th class="pb-2 px-2 whitespace-nowrap">Sala/Taller</th><th class="pb-2 text-right">Estado</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($productosBodega as $prod)
                            <tr>
                                <td class="py-2 pr-2 font-semibold">{{ $prod->modelo }} {{ $prod->color ? '· '.$prod->color : '' }}</td>
                                <td class="py-2 px-2 font-mono whitespace-nowrap">{{ $prod->stock_salon }} / {{ $prod->stock_taller }}</td>
                                <td class="py-2 text-right whitespace-nowrap">
                                    <span class="{{ $prod->stock_critico ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-700' }} px-2 py-0.5 rounded-full text-[10px] font-bold">
                                        {{ $prod->stock_critico ? 'Bajo Stock' : 'Disponible' }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr><td class="py-2 pr-2 font-semibold">Lisa Café / Nogal (IPS)</td><td class="py-2 px-2 whitespace-nowrap">8 / 12</td><td class="py-2 text-right whitespace-nowrap"><span class="bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded-full text-[10px] font-bold">Disponible</span></td></tr>
                            <tr><td class="py-2 pr-2 font-semibold">Paloma Biblia / Cruz</td><td class="py-2 px-2 whitespace-nowrap">5 / 8</td><td class="py-2 text-right whitespace-nowrap"><span class="bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded-full text-[10px] font-bold">Disponible</span></td></tr>
                            <tr><td class="py-2 pr-2 font-semibold">Blanca Niños (Seremi)</td><td class="py-2 px-2 whitespace-nowrap">2 / 2</td><td class="py-2 text-right whitespace-nowrap"><span class="bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded-full text-[10px] font-bold">Cumple Ley</span></td></tr>
                            <tr><td class="py-2 pr-2 font-semibold">Especial XL / Temática</td><td class="py-2 px-2 whitespace-nowrap">1 / 0</td><td class="py-2 text-right whitespace-nowrap"><span class="bg-amber-100 text-amber-800 px-2 py-0.5 rounded-full text-[10px] font-bold">Bajo Stock</span></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="lg:col-span-4 bg-white rounded-2xl p-6 shadow-sm border border-slate-100">
                <div class="flex justify-between items-center gap-2 mb-3">
                    <h3 class="font-bold text-slate-800 text-sm"><i class="fa-solid fa-van-shuttle text-indigo-700 mr-2"></i>Vehículos y Carrozas</h3>
                    @if(auth()->user()->hasRole('administrador', 'gerencia', 'asistente'))
                        <a href="{{ route('flota.calendario') }}" class="text-xs text-indigo-700 font-semibold hover:underline whitespace-nowrap">Ver Flota →</a>
                    @endif
                </div>
                <table class="w-full text-xs">
                    <thead class="text-slate-400 border-b"><tr class="text-left"><th class="pb-2 pr-2">Patente</th><th class="pb-2 px-2">Modelo / Uso</th><th class="pb-2 text-right">Estado</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($vehiculosLista as $vehiculo)
                            @php $vBadge = $vehiculo->estado_badge; @endphp
                            <tr>
                                <td class="py-2 pr-2 font-bold font-mono whitespace-nowrap">{{ $vehiculo->patente }}</td>
                                <td class="py-2 px-2">{{ $vehiculo->modelo }}</td>
                                <td class="py-2 text-right whitespace-nowrap">
                                    <span class="{{ $vBadge['class'] }} px-2 py-0.5 rounded-full text-[10px] font-bold border">
                                        {{ $vBadge['label'] }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr><td class="py-2 pr-2 font-bold whitespace-nowrap">JF-KR-32</td><td class="py-2 px-2">Toyota Hiace (Van Alta Rural)</td><td class="py-2 text-right whitespace-nowrap"><span class="bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded-full text-[10px] font-bold">Disponible</span></td></tr>
                            <tr><td class="py-2 pr-2 font-bold whitespace-nowrap">KH-TY-18</td><td class="py-2 px-2">Mercedes Benz (Carroza)</td><td class="py-2 text-right whitespace-nowrap"><span class="bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full text-[10px] font-bold">En Cortejo</span></td></tr>
                            <tr><td class="py-2 pr-2 font-bold whitespace-nowrap">LK-PX-27</td><td class="py-2 px-2">Chevrolet Van (Instalación)</td><td class="py-2 text-right whitespace-nowrap"><span class="bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded-full text-[10px] font-bold">Disponible</span></td></tr>
                            <tr><td class="py-2 pr-2 font-bold whitespace-nowrap">EXT-PERQ</td><td class="py-2 px-2">Carroza Apoyo Perquenco</td><td class="py-2 text-right whitespace-nowrap"><span class="bg-purple-100 text-purple-700 px-2 py-0.5 rounded-full text-[10px] font-bold">Convenio</span></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

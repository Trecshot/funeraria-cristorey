@extends('layouts.app')

@section('title', 'Hoja de ruta')
@section('heading', 'Mis Servicios de Transporte (Hoja de Ruta)')

@section('content')
    <div class="mx-auto max-w-3xl space-y-6">
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="font-serif-title text-lg font-bold text-slate-900">Mis Servicios de Transporte</h3>
                <p class="text-xs text-slate-500">Traslados asignados. Esta vista no incluye precios ni información contable.</p>
            </div>
            <span class="bg-indigo-100 text-indigo-800 text-[10px] px-3 py-1 rounded-full font-bold">
                <i class="fa-solid fa-shield-halved mr-1"></i>Valores Ocultos (RBAC)
            </span>
        </div>

        <div class="space-y-4">
            @forelse($traslados as $traslado)
                <article class="rounded-2xl border border-slate-200 bg-white overflow-hidden shadow-sm">
                    <div class="p-5 space-y-3">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wide text-indigo-800">
                                    <i class="fa-regular fa-clock mr-1"></i>{{ $traslado->salida_at?->format('D d/m · H:i') ?? 'Horario por confirmar' }}
                                </p>
                                <h2 class="font-serif-title mt-1 text-xl font-bold text-slate-900">{{ $traslado->destino }}</h2>
                            </div>
                            <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-800">
                                ● {{ $traslado->estadoLabel() }}
                            </span>
                        </div>

                        <dl class="space-y-1.5 text-xs text-slate-600">
                            <div><dt class="inline font-bold text-slate-800"><i class="fa-solid fa-user mr-1.5 text-indigo-700"></i>Servicio: </dt><dd class="inline font-semibold text-slate-900">{{ $traslado->servicio->difunto }}</dd></div>
                            <div><dt class="inline font-bold text-slate-800"><i class="fa-solid fa-car-side mr-1.5 text-indigo-700"></i>Vehículo: </dt><dd class="inline">{{ $traslado->vehiculo?->patente ?? 'Por asignar' }}</dd></div>
                        </dl>

                        <div class="bg-amber-50 border border-amber-200 p-3 rounded-xl text-xs text-amber-950">
                            <strong><i class="fa-solid fa-box-open mr-1"></i>Implementos a cargar:</strong>
                            {{ collect($traslado->implementos ?? \App\Models\Traslado::IMPLEMENTOS_POR_DEFECTO)->join(', ') }}
                        </div>

                        <div class="pt-2 flex flex-wrap gap-2">
                            @if($traslado->estado === 'programado')
                                <form method="POST" action="{{ route('movil.chofer.estado', $traslado) }}">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="estado" value="en_camino">
                                    <button class="button-primary min-h-10"><i class="fa-solid fa-play mr-1.5"></i>Iniciar traslado</button>
                                </form>
                            @endif
                            @if($traslado->estado === 'en_camino')
                                <form method="POST" action="{{ route('movil.chofer.estado', $traslado) }}">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="estado" value="completado">
                                    <button class="button-primary min-h-10 bg-emerald-700 hover:bg-emerald-800"><i class="fa-solid fa-check mr-1.5"></i>Marcar completado</button>
                                </form>
                            @endif
                        </div>
                    </div>
                </article>
            @empty
                <div class="panel text-center">
                    <i class="fa-solid fa-route text-2xl text-indigo-300 mb-2"></i>
                    <h2 class="font-serif-title text-lg font-bold">No tienes traslados asignados</h2>
                    <p class="mt-1 text-xs text-slate-500">Cuando se te asigne un servicio aparecerá aquí su hoja de ruta.</p>
                </div>
            @endforelse
        </div>

        <!-- Reposición Taller a Salón desde ruta de chofer -->
        @if(isset($productos) && $productos->isNotEmpty())
            <section class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm text-xs">
                <h3 class="font-bold text-slate-800 mb-3">
                    <i class="fa-solid fa-dolly text-indigo-700 mr-1.5"></i>Traspaso Rápido de Bodega (Taller → Salón Calle Blanco)
                </h3>
                <form method="POST" action="{{ route('bodega.traspaso') }}" class="flex flex-col sm:flex-row gap-3">
                    @csrf
                    <input type="hidden" name="origen" value="taller">
                    <input type="hidden" name="destino" value="salon">
                    <select name="producto_id" class="field flex-1 mt-0" required>
                        @foreach($productos as $producto)
                            <option value="{{ $producto->id }}">{{ $producto->codigo }} {{ $producto->modelo }} (Taller: {{ $producto->stock_taller }})</option>
                        @endforeach
                    </select>
                    <input type="number" name="cantidad" min="1" value="1" class="field sm:w-24 mt-0" required>
                    <button type="submit" class="button-primary">Reponer Salón</button>
                </form>
            </section>
        @endif
    </div>
@endsection

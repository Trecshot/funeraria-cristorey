@extends('layouts.app')

@section('title', 'Hoja de ruta')
@section('heading', 'Hoja de ruta')

@section('content')
    <p class="text-sm text-stone-600">Traslados asignados. Esta vista no incluye precios ni información contable.</p>
    <div class="mx-auto max-w-xl space-y-4">
        @forelse($traslados as $traslado)
            <article class="rounded-2xl border border-stone-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div><p class="text-xs font-semibold uppercase tracking-wide text-amber-800">{{ $traslado->salida_at?->format('D d/m · H:i') ?? 'Horario por confirmar' }}</p><h2 class="mt-2 text-xl">{{ $traslado->destino }}</h2></div>
                    <span class="rounded-full bg-stone-100 px-3 py-1 text-xs font-medium text-stone-700">{{ str_replace('_', ' ', ucfirst($traslado->estado)) }}</span>
                </div>
                <dl class="mt-4 space-y-2 text-sm">
                    <div><dt class="inline text-stone-500">Servicio: </dt><dd class="inline font-medium">{{ $traslado->servicio->difunto }}</dd></div>
                    <div><dt class="inline text-stone-500">Vehículo: </dt><dd class="inline">{{ $traslado->vehiculo?->patente ?? 'Por asignar' }}</dd></div>
                    <div><dt class="inline text-stone-500">Implementos: </dt><dd class="inline">{{ collect($traslado->implementos ?? ['Cruz', 'Tarjetas', 'Nailon rural'])->join(', ') }}</dd></div>
                </dl>
                <div class="mt-5 flex flex-wrap gap-2">
                    @if($traslado->estado === 'programado')
                        <form method="POST" action="{{ route('movil.chofer.estado', $traslado) }}">@csrf @method('PATCH')<input type="hidden" name="estado" value="en_camino"><button class="button-primary min-h-11">Iniciar traslado</button></form>
                    @endif
                    @if($traslado->estado === 'en_camino')
                        <form method="POST" action="{{ route('movil.chofer.estado', $traslado) }}">@csrf @method('PATCH')<input type="hidden" name="estado" value="completado"><button class="button-primary min-h-11">Marcar completado</button></form>
                    @endif
                </div>
            </article>
        @empty
            <div class="panel text-center">
                <h2>No tienes traslados asignados</h2>
                <p class="mt-2 text-sm text-stone-500">Cuando se te asigne un servicio aparecerá aquí su hoja de ruta.</p>
            </div>
        @endforelse
    </div>
@endsection
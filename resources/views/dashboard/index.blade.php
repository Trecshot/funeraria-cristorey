@extends('layouts.app')

@section('title', 'Dashboard')
@section('heading', 'Resumen gerencial')

@section('content')
    @if($serviciosCriticos->isNotEmpty())
        <section class="rounded-xl border border-red-200 bg-red-50 p-5" aria-labelledby="alertas-title">
            <div class="flex items-start gap-3">
                <span class="mt-1 h-3 w-3 shrink-0 rounded-full bg-red-600" aria-hidden="true"></span>
                <div class="w-full">
                    <h2 id="alertas-title" class="text-lg text-red-900">Atención documental · cortejos en menos de 3 horas</h2>
                    <div class="mt-3 divide-y divide-red-200">
                        @foreach($serviciosCriticos as $servicio)
                            <div class="py-3 text-sm text-red-900">
                                <p class="font-semibold">{{ $servicio->difunto }} · {{ $servicio->cortejo_at->format('H:i') }}</p>
                                <p>Falta: {{ collect($servicio->documentosFaltantes)->map(fn ($tipo) => $tipo === 'resolucion_seremi' ? 'Resolución Seremi' : 'Oficio de Fiscalía / SML')->join(', ') }}</p>
                                @if(in_array(auth()->user()->role, ['administrador', 'gerencia', 'asistente'], true))
                                    <a class="mt-1 inline-block underline" href="{{ route('servicios.show', $servicio) }}">Abrir expediente</a>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
    @else
        <section class="panel flex items-center gap-3">
            <span class="h-3 w-3 rounded-full bg-emerald-600" aria-hidden="true"></span>
            <p class="text-sm text-stone-700">Sin alertas documentales para cortejos dentro de las próximas 3 horas.</p>
        </section>
    @endif

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <article class="panel">
            <p class="text-sm text-stone-500">Servicios registrados este mes</p>
            <p class="mt-3 text-3xl font-semibold text-stone-900">{{ $serviciosMes }}</p>
        </article>
        <article class="panel">
            <p class="text-sm text-stone-500">Productos con stock crítico</p>
            <p class="mt-3 text-3xl font-semibold {{ $stockCritico ? 'text-amber-800' : 'text-stone-900' }}">{{ $stockCritico }}</p>
            @if(in_array(auth()->user()->role, ['administrador', 'gerencia', 'asistente', 'bodega'], true))
                <a class="mt-3 inline-block text-sm text-amber-900 underline" href="{{ route('bodega.index') }}">Ver inventario</a>
            @endif
        </article>
        <article class="panel">
            <p class="text-sm text-stone-500">Vehículos disponibles</p>
            <p class="mt-3 text-3xl font-semibold text-stone-900">{{ $vehiculosDisponibles }}</p>
        </article>
    </section>
@endsection
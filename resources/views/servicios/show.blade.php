@extends('layouts.app')

@section('title', 'Expediente '.$servicio->id)
@section('heading', 'Expediente #'.$servicio->id)

@section('content')
    <div class="flex flex-wrap justify-between gap-3">
        <div><p class="text-sm text-stone-500">Servicio funerario</p><h2 class="mt-1 text-2xl">{{ $servicio->difunto }}</h2></div>
        <a class="button-secondary self-start" href="{{ route('servicios.edit', $servicio) }}">Editar expediente</a>
    </div>

    <div class="grid gap-5 lg:grid-cols-2">
        <section class="panel">
            <h2 class="text-lg">Contratante y servicio</h2>
            <dl class="mt-4 grid grid-cols-2 gap-4 text-sm">
                <div><dt class="text-stone-500">Contratante</dt><dd class="mt-1 font-medium">{{ $servicio->cliente_contratante }}</dd></div>
                <div><dt class="text-stone-500">Teléfono</dt><dd class="mt-1">{{ $servicio->telefono_cliente ?: 'No registrado' }}</dd></div>
                <div><dt class="text-stone-500">Convenio</dt><dd class="mt-1">{{ ucfirst($servicio->convenio) }}</dd></div>
                <div><dt class="text-stone-500">Estado</dt><dd class="mt-1">{{ str_replace('_', ' ', ucfirst($servicio->estado)) }}</dd></div>
                <div><dt class="text-stone-500">Total</dt><dd class="mt-1">${{ number_format((float) $servicio->total_servicio, 0, ',', '.') }} CLP</dd></div>
                <div><dt class="text-stone-500">Cortejo</dt><dd class="mt-1">{{ $servicio->cortejo_at?->format('d/m/Y H:i') ?? 'Sin programar' }}</dd></div>
                <div class="col-span-2"><dt class="text-stone-500">Destino</dt><dd class="mt-1">{{ $servicio->destino ?: 'Sin registrar' }}</dd></div>
                <div><dt class="text-stone-500">Escolaridad</dt><dd class="mt-1">{{ $servicio->escolaridad ?: 'No registrada' }}</dd></div>
                <div><dt class="text-stone-500">Oficio</dt><dd class="mt-1">{{ $servicio->oficio ?: 'No registrado' }}</dd></div>
                <div class="col-span-2"><dt class="text-stone-500">Causa SML</dt><dd class="mt-1">{{ $servicio->causa_sml ?: 'No registrada' }}</dd></div>
            </dl>
        </section>

        <section class="panel">
            <h2 class="text-lg">Documentos de respaldo</h2>
            @if($servicio->documentos->isEmpty())
                <p class="mt-3 text-sm text-stone-500">No se han cargado documentos.</p>
            @else
                <ul class="mt-3 divide-y divide-stone-100">
                    @foreach($servicio->documentos as $documento)
                        <li class="flex flex-wrap items-center justify-between gap-2 py-3 text-sm">
                            <span>{{ $documento->nombre_original }} <span class="text-stone-500">· {{ $documento->tipo }}</span></span>
                            <a class="text-amber-900 underline" href="{{ route('servicios.documentos.descargar', [$servicio, $documento]) }}">Descargar</a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>
    <form method="POST" action="{{ route('servicios.destroy', $servicio) }}" onsubmit="return confirm('¿Eliminar este expediente?');" class="text-right">
        @csrf @method('DELETE')
        <button class="text-sm text-red-700 underline">Eliminar expediente</button>
    </form>
@endsection
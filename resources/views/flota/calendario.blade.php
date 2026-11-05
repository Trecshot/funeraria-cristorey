@extends('layouts.app')

@section('title', 'Flota')
@section('heading', 'Flota y calendario')

@section('content')
    @if(in_array(auth()->user()->role, ['administrador', 'gerencia'], true))
        <section class="panel">
            <h2 class="text-lg">Registrar vehículo</h2>
            <form method="POST" action="{{ route('flota.vehiculos.store') }}" class="mt-3 flex flex-col gap-3 sm:flex-row">
                @csrf
                <label class="flex-1"><span class="label">Patente</span><input class="field" name="patente" required></label>
                <label class="flex-1"><span class="label">Modelo</span><input class="field" name="modelo" required></label>
                <button class="button-primary self-end">Agregar vehículo</button>
            </form>
        </section>
    @endif

    <section class="panel overflow-hidden p-0">
        <div class="border-b border-stone-100 px-5 py-4"><h2 class="text-lg">Traslados programados</h2><p class="mt-1 text-sm text-stone-500">Asignación de vehículos y choferes a partir de los expedientes.</p></div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-stone-200">
                <thead class="bg-stone-50 text-left text-xs uppercase tracking-wide text-stone-500"><tr><th class="px-4 py-3">Fecha</th><th class="px-4 py-3">Servicio</th><th class="px-4 py-3">Destino</th><th class="px-4 py-3">Asignación</th><th class="px-4 py-3">Estado</th><th class="px-4 py-3"></th></tr></thead>
                <tbody class="divide-y divide-stone-100">
                    @forelse($traslados as $traslado)
                        <tr>
                            <td class="table-cell">{{ $traslado->salida_at?->format('d/m/Y H:i') }}</td>
                            <td class="table-cell">{{ $traslado->servicio->difunto }}</td>
                            <td class="table-cell">{{ $traslado->destino }}</td>
                            <td class="table-cell">{{ $traslado->vehiculo?->patente ?? 'Sin vehículo' }} · {{ $traslado->chofer?->name ?? 'Sin chofer' }}</td>
                            <td class="table-cell">{{ str_replace('_', ' ', ucfirst($traslado->estado)) }}</td>
                            <td class="table-cell">
                                @if(in_array(auth()->user()->role, ['administrador', 'gerencia', 'asistente'], true))
                                    <details>
                                        <summary class="cursor-pointer text-amber-900 underline">Asignar</summary>
                                        <form method="POST" action="{{ route('flota.traslados.asignar', $traslado) }}" class="mt-2 min-w-56 space-y-2">
                                            @csrf @method('PATCH')
                                            <select class="field" name="vehiculo_id"><option value="">Sin vehículo</option>@foreach($vehiculos as $vehiculo)<option value="{{ $vehiculo->id }}" @selected($traslado->vehiculo_id === $vehiculo->id)>{{ $vehiculo->patente }} · {{ $vehiculo->modelo }}</option>@endforeach</select>
                                            <select class="field" name="chofer_id"><option value="">Sin chofer</option>@foreach($choferes as $chofer)<option value="{{ $chofer->id }}" @selected($traslado->chofer_id === $chofer->id)>{{ $chofer->name }}</option>@endforeach</select>
                                            <button class="button-primary">Guardar asignación</button>
                                        </form>
                                    </details>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td class="px-4 py-10 text-center text-sm text-stone-500" colspan="6">No hay traslados programados. Registra una fecha y destino en un expediente.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-stone-100 p-4">{{ $traslados->links() }}</div>
    </section>
@endsection
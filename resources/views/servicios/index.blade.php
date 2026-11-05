@extends('layouts.app')

@section('title', 'Servicios')
@section('heading', 'Expedientes y servicios')

@section('content')
    <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
        <p class="text-sm text-stone-600">Registro y seguimiento de los servicios funerarios.</p>
        <a class="button-primary" href="{{ route('servicios.create') }}">Abrir expediente</a>
    </div>
    <section class="panel overflow-hidden p-0">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-stone-200">
                <thead class="bg-stone-50 text-left text-xs uppercase tracking-wide text-stone-500">
                    <tr><th class="px-4 py-3">Expediente</th><th class="px-4 py-3">Contratante</th><th class="px-4 py-3">Convenio</th><th class="px-4 py-3">Cortejo</th><th class="px-4 py-3">Estado</th><th class="px-4 py-3"></th></tr>
                </thead>
                <tbody class="divide-y divide-stone-100">
                    @forelse($servicios as $servicio)
                        <tr>
                            <td class="table-cell"><span class="font-medium">{{ $servicio->difunto }}</span><span class="block text-xs text-stone-500">{{ $servicio->folio }}</span></td>
                            <td class="table-cell">{{ $servicio->cliente_contratante }}</td>
                            <td class="table-cell">{{ ucfirst($servicio->convenio) }}</td>
                            <td class="table-cell">{{ $servicio->cortejo_at?->format('d/m/Y H:i') ?? 'Sin programar' }}</td>
                            <td class="table-cell">{{ str_replace('_', ' ', ucfirst($servicio->estado)) }}</td>
                            <td class="table-cell"><a class="text-amber-900 underline" href="{{ route('servicios.show', $servicio) }}">Ver expediente</a></td>
                        </tr>
                    @empty
                        <tr><td class="px-4 py-10 text-center text-sm text-stone-500" colspan="6">Todavía no hay expedientes.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-stone-100 p-4">{{ $servicios->links() }}</div>
    </section>
@endsection
@extends('layouts.app')

@section('title', 'Servicios')
@section('heading', 'Expedientes y servicios')

@section('content')
    <div class="space-y-6">
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <div>
                <h3 class="font-serif-title text-xl font-bold text-slate-900">Registro y Seguimiento de Expedientes Funerarios</h3>
                <p class="text-xs text-slate-500 mt-1">Control de contratos, convenios previsionales (IPS / AFP 15 UF), programación de cortejos y archivo documental legal.</p>
            </div>
            <a class="button-primary self-start sm:self-center" href="{{ route('servicios.create') }}">
                <i class="fa-solid fa-folder-plus mr-1.5"></i>Abrir expediente
            </a>
        </div>

        <section class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-xs">
                    <thead class="bg-indigo-50/70 text-left text-[10px] uppercase tracking-wide text-indigo-950 font-bold">
                        <tr>
                            <th class="px-4 py-3.5">Expediente / Difunto</th>
                            <th class="px-4 py-3.5">Contratante</th>
                            <th class="px-4 py-3.5">Convenio</th>
                            <th class="px-4 py-3.5">Cortejo</th>
                            <th class="px-4 py-3.5">Estado</th>
                            <th class="px-4 py-3.5 text-right">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($servicios as $servicio)
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="table-cell">
                                    <span class="font-bold text-slate-900">{{ $servicio->difunto }}</span>
                                    <span class="block text-[10px] text-slate-400">{{ $servicio->folio }}</span>
                                </td>
                                <td class="table-cell">{{ $servicio->cliente_contratante }}</td>
                                <td class="table-cell">
                                    <span class="bg-indigo-50 text-indigo-900 border border-indigo-100 px-2.5 py-0.5 rounded-full text-[10px] font-bold">
                                        {{ strtoupper($servicio->convenio) }}
                                    </span>
                                </td>
                                <td class="table-cell">{{ $servicio->cortejo_at?->format('d/m/Y H:i') ?? 'Sin programar' }}</td>
                                <td class="table-cell">
                                    @php $porcentaje = $servicio->calcularPorcentajeAvance(); @endphp
                                    <div class="flex items-center space-x-1.5">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $servicio->estadoBadgeClass() }}">
                                            {{ $servicio->estadoLabel() }}
                                        </span>
                                        <span class="text-[10px] text-slate-500 font-semibold">{{ $porcentaje }}%</span>
                                    </div>
                                    <div class="w-24 bg-slate-100 rounded-full h-1.5 mt-1 overflow-hidden border border-slate-200">
                                        <div class="bg-gradient-to-r from-indigo-600 to-emerald-500 h-1.5 rounded-full" style="width: {{ $porcentaje }}%;"></div>
                                    </div>
                                </td>
                                <td class="table-cell text-right">
                                    <a class="text-indigo-700 font-bold hover:underline" href="{{ route('servicios.show', $servicio) }}">
                                        Ver expediente <i class="fa-solid fa-chevron-right text-[10px] ml-0.5"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="px-4 py-10 text-center text-xs text-slate-500" colspan="6">Todavía no hay expedientes registrados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 p-4">{{ $servicios->links() }}</div>
        </section>
    </div>
@endsection

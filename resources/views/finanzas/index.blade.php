@extends('layouts.app')

@section('title', 'Finanzas')
@section('heading', 'Liquidación de Convenios (IPS / AFP 15 UF) y Comprobantes Foliados')

@section('content')
    <div class="space-y-6">
        <section class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 text-xs space-y-3">
            <h3 class="font-serif-title text-xl font-bold text-slate-900">Liquidación de Convenios (IPS / AFP 15 UF) y Comprobantes Foliados</h3>
            <p class="text-slate-500">Descuento automático de Asignación por Muerte IPS ($1.070.000), Cuota Mortuoria AFP (15 UF), alerta de Call Center 600 cerrado tras las 17:00 hrs y emisión de comprobantes digitales en PDF.</p>
            <div class="mt-3 grid gap-3 text-xs sm:grid-cols-3">
                <div class="rounded-xl bg-indigo-50/70 border border-indigo-100 p-3.5">
                    <span class="block text-slate-500">IPS / PGU (Asignación Muerte)</span>
                    <strong class="text-base text-indigo-950">$1.070.000 CLP</strong>
                </div>
                <div class="rounded-xl bg-indigo-50/70 border border-indigo-100 p-3.5">
                    <span class="block text-slate-500">AFP (Cuota Mortuoria)</span>
                    <strong class="text-base text-indigo-950">15 UF</strong>
                    <span class="block text-[11px] text-slate-500">{{ $ufValue ? 'UF configurada: $'.number_format((float) $ufValue, 0, ',', '.') : 'Conversión CLP no configurada' }}</span>
                </div>
                <div class="rounded-xl bg-indigo-50/70 border border-indigo-100 p-3.5">
                    <span class="block text-slate-500">Dipreca / Sindicatos</span>
                    <strong class="text-base text-indigo-950">Aporte por confirmar</strong>
                </div>
            </div>
        </section>

        <section class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="border-b border-slate-100 px-6 py-4">
                <h2 class="font-serif-title text-lg font-bold text-slate-900">Servicios y copagos estimados</h2>
                <p class="mt-0.5 text-xs text-slate-500">Estimaciones referenciales; confirmar aportes específicos del convenio y emitir comprobante PDF.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-xs">
                    <thead class="bg-indigo-50/70 text-left text-[10px] uppercase tracking-wide text-indigo-950 font-bold">
                        <tr>
                            <th class="px-4 py-3.5">Difunto</th>
                            <th class="px-4 py-3.5">Contratante</th>
                            <th class="px-4 py-3.5">Convenio</th>
                            <th class="px-4 py-3.5">Total servicio</th>
                            <th class="px-4 py-3.5">Copago estimado</th>
                            <th class="px-4 py-3.5 text-right">Comprobante</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($servicios as $servicio)
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="table-cell font-bold text-slate-900">{{ $servicio->difunto }}</td>
                                <td class="table-cell">{{ $servicio->cliente_contratante }}</td>
                                <td class="table-cell">
                                    <span class="bg-indigo-50 text-indigo-900 px-2.5 py-0.5 rounded-full text-[10px] font-bold">{{ ucfirst($servicio->convenio) }}</span>
                                </td>
                                <td class="table-cell font-semibold">${{ number_format((float) $servicio->monto_servicio, 0, ',', '.') }}</td>
                                <td class="table-cell font-bold text-indigo-950">
                                    {{ $servicio->copagoEstimado() === null ? 'Por confirmar (15 UF)' : '$'.number_format($servicio->copagoEstimado(), 0, ',', '.') }}
                                </td>
                                <td class="table-cell text-right">
                                    <a class="text-indigo-700 font-bold hover:underline" href="{{ route('finanzas.pdf', $servicio) }}">
                                        <i class="fa-solid fa-file-pdf text-rose-600 mr-1"></i>Comprobante PDF
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr><td class="px-4 py-10 text-center text-xs text-slate-500" colspan="6">Aún no hay servicios registrados.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 p-4">{{ $servicios->links() }}</div>
        </section>
    </div>
@endsection

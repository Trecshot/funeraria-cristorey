@extends('layouts.app')

@section('title', 'Finanzas')
@section('heading', 'Finanzas y convenios')

@section('content')
    <section class="panel">
        <h2 class="text-lg">Referencias de convenio</h2>
        <div class="mt-3 grid gap-3 text-sm sm:grid-cols-3">
            <p class="rounded-lg bg-stone-50 p-3"><span class="block text-stone-500">IPS</span><strong>$1.070.000 CLP</strong></p>
            <p class="rounded-lg bg-stone-50 p-3"><span class="block text-stone-500">AFP</span><strong>15 UF</strong><span class="block text-xs text-stone-500">{{ $ufValue ? 'UF configurada: $'.number_format((float) $ufValue, 0, ',', '.') : 'Conversión CLP no configurada' }}</span></p>
            <p class="rounded-lg bg-stone-50 p-3"><span class="block text-stone-500">Dipreca / Sindicatos</span><strong>Aporte por confirmar</strong></p>
        </div>
    </section>

    <section class="panel overflow-hidden p-0">
        <div class="border-b border-stone-100 px-5 py-4"><h2 class="text-lg">Servicios y copagos estimados</h2><p class="mt-1 text-xs text-stone-500">Estimaciones referenciales; confirmar aportes específicos del convenio.</p></div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-stone-200">
                <thead class="bg-stone-50 text-left text-xs uppercase tracking-wide text-stone-500"><tr><th class="px-4 py-3">Difunto</th><th class="px-4 py-3">Contratante</th><th class="px-4 py-3">Convenio</th><th class="px-4 py-3">Total servicio</th><th class="px-4 py-3">Copago estimado</th><th class="px-4 py-3"></th></tr></thead>
                <tbody class="divide-y divide-stone-100">
                    @forelse($servicios as $servicio)
                        <tr>
                            <td class="table-cell">{{ $servicio->difunto }}</td><td class="table-cell">{{ $servicio->cliente_contratante }}</td><td class="table-cell">{{ ucfirst($servicio->convenio) }}</td>
                            <td class="table-cell">${{ number_format((float) $servicio->monto_servicio, 0, ',', '.') }}</td>
                            <td class="table-cell">{{ $servicio->copagoEstimado() === null ? 'Por confirmar (15 UF)' : '$'.number_format($servicio->copagoEstimado(), 0, ',', '.') }}</td>
                            <td class="table-cell"><a class="text-amber-900 underline" href="{{ route('finanzas.pdf', $servicio) }}">Comprobante PDF</a></td>
                        </tr>
                    @empty
                        <tr><td class="px-4 py-10 text-center text-sm text-stone-500" colspan="6">Aún no hay servicios registrados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-stone-100 p-4">{{ $servicios->links() }}</div>
    </section>
@endsection
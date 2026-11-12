<!-- HISTORIAL DE AUDITORÍA Y TRAZABILIDAD CRONOLÓGICA -->
<section id="seccion-historial" class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 space-y-4">
    <h3 class="font-serif-title text-base font-bold text-slate-900 flex items-center">
        <i class="fa-solid fa-timeline text-indigo-700 mr-2"></i>Historial Cronológico de Auditoría (Trazabilidad)
    </h3>
    <div class="flow-root mt-3">
        <ul class="-mb-8">
            @forelse($servicio->historial as $evento)
                <li>
                    <div class="relative pb-8">
                        @if(! $loop->last)
                            <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-slate-200" aria-hidden="true"></span>
                        @endif
                        <div class="relative flex space-x-3.5">
                            <div>
                                <span class="h-8 w-8 rounded-full bg-indigo-50 border border-indigo-200 text-indigo-700 flex items-center justify-center ring-4 ring-white text-xs">
                                    <i class="fa-solid {{ $evento->hitoDefinicion()['icono'] }}"></i>
                                </span>
                            </div>
                            <div class="flex-1 min-w-0 pt-1">
                                <div class="flex items-center justify-between">
                                    <p class="text-xs font-bold text-slate-900">{{ $evento->titulo }}</p>
                                    <span class="text-[11px] text-slate-400 font-mono">{{ $evento->completado_at?->format('d/m/Y H:i') }}</span>
                                </div>
                                <p class="text-xs text-slate-600 mt-0.5">{{ $evento->descripcion }}</p>
                                <p class="text-[10px] text-slate-400 mt-1">Registrado por: <strong>{{ $evento->usuario?->name ?? 'Sistema' }}</strong></p>
                            </div>
                        </div>
                    </div>
                </li>
            @empty
                <li class="text-xs text-slate-400 italic">No se han registrado eventos en el historial.</li>
            @endforelse
        </ul>
    </div>
</section>

@if($servicio->auditorias->isNotEmpty())
    <!-- REGISTRO DE AUDITORÍA INMUTABLE (LEY N° 21.663 DE CIBERSEGURIDAD) -->
    <section class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between border-b border-slate-100 pb-3 gap-2">
            <h3 class="font-serif-title text-base font-bold text-slate-900 flex items-center">
                <i class="fa-solid fa-shield-halved text-emerald-600 mr-2"></i>Pistas de Auditoría Inmutables (Ley N° 21.663)
            </h3>
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200 self-start sm:self-auto">
                <i class="fa-solid fa-fingerprint text-[9px]"></i> SHA-256 Verificado & IP Trazada
            </span>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-xs">
                <thead class="bg-slate-50 text-left text-[10px] uppercase font-bold text-slate-500">
                    <tr>
                        <th class="px-3 py-2.5">Fecha / Hora</th>
                        <th class="px-3 py-2.5">Acción</th>
                        <th class="px-3 py-2.5">Entidad</th>
                        <th class="px-3 py-2.5">Descripción de Auditoría</th>
                        <th class="px-3 py-2.5">Operador & IP</th>
                        <th class="px-3 py-2.5">Hash Criptográfico SHA-256</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-[11px]">
                    @foreach($servicio->auditorias as $auditoria)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="px-3 py-2 text-slate-500 font-mono whitespace-nowrap">{{ $auditoria->created_at->format('d/m/Y H:i:s') }}</td>
                            <td class="px-3 py-2 whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded text-[9px] font-bold {{ $auditoria->action_type === 'CREATE' ? 'bg-blue-100 text-blue-800' : 'bg-emerald-100 text-emerald-800' }}">
                                    {{ $auditoria->action_type }}
                                </span>
                            </td>
                            <td class="px-3 py-2 font-mono text-[10px] text-slate-500">{{ $auditoria->target_entity }}</td>
                            <td class="px-3 py-2 font-medium text-slate-800">{{ $auditoria->descripcion }}</td>
                            <td class="px-3 py-2 text-slate-600 whitespace-nowrap">
                                <span class="font-semibold">{{ $auditoria->usuario?->name ?? 'Sistema' }}</span>
                                <span class="block text-[10px] text-slate-400 font-mono">{{ $auditoria->ip_address }}</span>
                            </td>
                            <td class="px-3 py-2 font-mono text-[10px] text-slate-400 truncate max-w-[140px]" title="{{ $auditoria->transaction_hash }}">
                                {{ Str::limit($auditoria->transaction_hash, 16) }}...
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
@endif

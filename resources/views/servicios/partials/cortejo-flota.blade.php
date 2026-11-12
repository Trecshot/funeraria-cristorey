<!-- PANEL HITO 4: CORTEJO Y TRASLADO (FLOTA Y CHOFER) -->
<section id="seccion-cortejo" class="panel space-y-4">
    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
        <h2 class="font-serif-title text-base font-bold text-slate-900 flex items-center">
            <i class="fa-solid fa-van-shuttle text-indigo-700 mr-2"></i>Hito 4: Cortejo y Flota
        </h2>
        @php $traslado = $servicio->traslados->sortByDesc('salida_at')->first(); @endphp
        @if($traslado && $traslado->chofer && $traslado->vehiculo)
            <span class="bg-emerald-100 text-emerald-800 text-[10px] font-bold px-2.5 py-0.5 rounded-full">
                <i class="fa-solid fa-check mr-1"></i>Asignado
            </span>
        @else
            <span class="bg-amber-100 text-amber-900 text-[10px] font-bold px-2.5 py-0.5 rounded-full">
                {{ $servicio->cortejo_at ? 'Falta Asignar Chofer' : 'Sin Programar' }}
            </span>
        @endif
    </div>

    @if($traslado)
        <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-4 text-xs space-y-2">
            <div class="grid grid-cols-2 gap-2 text-slate-700 text-[11px]">
                <div><span class="text-slate-400">Fecha y hora:</span> <strong>{{ $servicio->cortejo_at?->format('d/m/Y H:i') ?? 'Sin fecha' }}</strong></div>
                <div><span class="text-slate-400">Destino:</span> <strong>{{ $servicio->destino ?: 'No informado' }}</strong></div>
                <div>
                    <span class="text-slate-400">Vehículo:</span>
                    <strong>{{ $traslado->vehiculo ? $traslado->vehiculo->patente.' ('.$traslado->vehiculo->modelo.')' : 'Pendiente de asignar' }}</strong>
                </div>
                <div>
                    <span class="text-slate-400">Chofer:</span>
                    <strong>{{ $traslado->chofer ? $traslado->chofer->name : 'Pendiente de asignar' }}</strong>
                </div>
            </div>
            <div class="pt-2 border-t border-slate-200 flex justify-end">
                <a href="{{ route('flota.calendario') }}" class="text-indigo-700 font-bold hover:underline text-[11px] flex items-center">
                    <i class="fa-solid fa-calendar-days mr-1"></i>Gestionar en Calendario de Flota &rarr;
                </a>
            </div>
        </div>
    @else
        <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-4 text-xs text-slate-500">
            <p>No se ha configurado un traslado para este servicio funerario.</p>
            <p class="text-[11px] mt-1">Puedes programar fecha y destino editando el expediente.</p>
        </div>
    @endif
</section>

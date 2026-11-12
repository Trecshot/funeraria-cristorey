<!-- PANEL HITO 2: LOGÍSTICA DE URNA DESDE BODEGA -->
<section id="seccion-urna" class="panel space-y-4 transition-all duration-300">
    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
        <h2 class="font-serif-title text-base font-bold text-slate-900 flex items-center">
            <i class="fa-solid fa-box-archive text-indigo-700 mr-2"></i>Hito 2: Logística y Asignación de Urna
        </h2>
        @if($servicio->urna)
            <span class="bg-emerald-100 text-emerald-800 text-[10px] font-bold px-2.5 py-0.5 rounded-full flex items-center">
                <i class="fa-solid fa-check mr-1"></i>Asignada
            </span>
        @else
            <span class="bg-amber-100 text-amber-900 text-[10px] font-bold px-2.5 py-0.5 rounded-full">
                Pendiente
            </span>
        @endif
    </div>

    @if($servicio->urna)
        <div class="rounded-xl border border-indigo-100 bg-gradient-to-br from-indigo-50/50 to-white p-4 text-xs space-y-3">
            <div class="flex items-center justify-between">
                <div>
                    <strong class="text-sm font-bold text-indigo-950">{{ $servicio->urna->modelo }}</strong>
                    <span class="block text-[11px] text-slate-500">Código: {{ $servicio->urna->codigo ?: 'UR-'.$servicio->urna->id }}</span>
                </div>
                <button type="button" onclick="abrirModalUrnas()" class="px-2.5 py-1 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 text-xs font-semibold transition">
                    <i class="fa-solid fa-arrows-rotate mr-1"></i>Cambiar urna
                </button>
            </div>
            <div class="grid grid-cols-2 gap-2 text-slate-600 text-[11px] pt-1 border-t border-indigo-100/60">
                <div><span class="text-slate-400">Color:</span> {{ $servicio->urna->color }}</div>
                <div><span class="text-slate-400">Categoría:</span> {{ $servicio->urna->categoria_etaria }}</div>
                <div><span class="text-slate-400">Medidas:</span> {{ $servicio->urna->medidas_cm }}</div>
                <div><span class="text-slate-400">Tallado:</span> {{ $servicio->urna->tallado ?: 'Estándar' }}</div>
            </div>
        </div>
    @else
        <div class="rounded-xl border border-amber-300 bg-amber-50/40 p-4 text-xs space-y-3">
            <div class="flex items-start justify-between gap-2">
                <div>
                    <p class="font-bold text-amber-950">No hay urna asignada a este servicio.</p>
                    <p class="text-[11px] text-amber-900/80 mt-0.5">Asigna una urna desde el inventario disponible de Salón Calle Blanco o Taller:</p>
                </div>
                <!-- Botón para abrir el catálogo visual de urnas -->
                <button type="button" onclick="abrirModalUrnas()" class="px-3 py-1.5 rounded-lg bg-indigo-900 hover:bg-indigo-800 text-white font-bold text-xs shadow-xs transition flex items-center whitespace-nowrap">
                    <i class="fa-solid fa-boxes-stacked mr-1.5"></i>Abrir Catálogo Visual
                </button>
            </div>

            <!-- Selector rápido directo -->
            <form method="POST" action="{{ route('servicios.urna.asignar', $servicio) }}" class="flex flex-col sm:flex-row gap-2 pt-2 border-t border-amber-200/60">
                @csrf
                @method('PATCH')
                <select id="select-urna-rapida" name="urna_id" class="field text-xs flex-1 bg-white" required>
                    <option value="">-- Seleccionar urna desde bodega --</option>
                    @foreach($urnasDisponibles as $u)
                        <option value="{{ $u->id }}">
                            {{ $u->modelo }} · {{ $u->color }} ({{ $u->categoria_etaria }}) · Stock: {{ $u->stock_salon }} Salón / {{ $u->stock_taller }} Taller
                        </option>
                    @endforeach
                </select>
                <button type="submit" class="button-primary text-xs whitespace-nowrap">
                    <i class="fa-solid fa-plus mr-1"></i>Asignar Urna
                </button>
            </form>
        </div>
    @endif

    <div class="flex justify-end pt-1">
        <a href="{{ route('bodega.index') }}" target="_blank" rel="noopener" class="text-indigo-700 hover:text-indigo-900 font-bold text-xs flex items-center">
            <i class="fa-solid fa-warehouse mr-1.5"></i>Ir a Gestión de Bodega Dual (/bodega)
            <i class="fa-solid fa-arrow-up-right-from-square text-[10px] ml-1 opacity-70"></i>
        </a>
    </div>
</section>

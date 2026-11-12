<!-- MODAL INTERACTIVO DE ASIGNACIÓN Y CATÁLOGO DE URNAS -->
<div id="modal-catalogo-urnas" class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="relative w-full max-w-4xl max-h-[90vh] rounded-2xl bg-white shadow-2xl border border-slate-100 flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-200">

        <!-- Encabezado del Modal -->
        <div class="bg-gradient-to-r from-indigo-950 via-indigo-900 to-indigo-800 px-6 py-4 text-white flex items-center justify-between flex-shrink-0">
            <div>
                <div class="flex items-center space-x-2">
                    <span class="bg-white/20 text-white text-[10px] font-bold px-2 py-0.5 rounded uppercase tracking-wider">Hito 2: Logística Interna</span>
                    <span class="text-indigo-200 text-xs font-semibold">Expediente #{{ $servicio->id }}</span>
                </div>
                <h3 class="font-serif-title text-xl font-bold mt-0.5 text-white">Catálogo de Urnas y Asignación Operativa</h3>
                <p class="text-xs text-indigo-200 mt-0.5">Asignar urna para el causante: <strong>{{ $servicio->difunto }}</strong></p>
            </div>
            <button type="button" onclick="cerrarModalUrnas()" class="text-white/70 hover:text-white p-2 rounded-lg hover:bg-white/10 transition">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <!-- Filtros y Búsqueda en Vivo -->
        <div class="p-4 border-b border-slate-100 bg-slate-50/70 flex flex-col sm:flex-row gap-3 items-center justify-between flex-shrink-0 text-xs">
            <div class="relative w-full sm:w-80">
                <i class="fa-solid fa-magnifying-glass absolute left-3 top-2.5 text-slate-400"></i>
                <input type="text" id="filtro-urnas-input" oninput="filtrarUrnasEnModal()" class="field pl-9 w-full text-xs" placeholder="Buscar modelo, madera, color o categoría...">
            </div>

            <!-- Botones de Categoría Rápida -->
            <div class="flex flex-wrap gap-1.5 self-start sm:self-center">
                <button type="button" onclick="filtrarCategoriaUrna('todos')" class="btn-categoria-urna active px-2.5 py-1 rounded-lg text-[11px] font-bold bg-indigo-900 text-white transition">Todos</button>
                <button type="button" onclick="filtrarCategoriaUrna('Adulto')" class="btn-categoria-urna px-2.5 py-1 rounded-lg text-[11px] font-semibold bg-white border border-slate-200 text-slate-700 hover:bg-slate-100 transition">Adulto</button>
                <button type="button" onclick="filtrarCategoriaUrna('Juvenil')" class="btn-categoria-urna px-2.5 py-1 rounded-lg text-[11px] font-semibold bg-white border border-slate-200 text-slate-700 hover:bg-slate-100 transition">Juvenil</button>
                <button type="button" onclick="filtrarCategoriaUrna('Niño')" class="btn-categoria-urna px-2.5 py-1 rounded-lg text-[11px] font-semibold bg-white border border-slate-200 text-slate-700 hover:bg-slate-100 transition">Niño</button>
                <button type="button" onclick="filtrarCategoriaUrna('Especial')" class="btn-categoria-urna px-2.5 py-1 rounded-lg text-[11px] font-semibold bg-white border border-slate-200 text-slate-700 hover:bg-slate-100 transition">Especial</button>
            </div>
        </div>

        <!-- Catálogo de Tarjetas de Urnas (Scrollable) -->
        <div class="p-6 overflow-y-auto flex-1 text-xs">
            <div id="grid-urnas-modal" class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($urnasDisponibles as $urna)
                    @php
                        $esActual = $servicio->urna_id === $urna->id;
                        $stockTotal = $urna->stock_salon + $urna->stock_taller;
                    @endphp
                    <div class="card-urna-item rounded-xl border {{ $esActual ? 'border-emerald-400 bg-emerald-50/30 ring-2 ring-emerald-200' : 'border-slate-200 bg-white hover:border-indigo-300' }} p-4 flex flex-col justify-between shadow-2xs hover:shadow-sm transition"
                         data-modelo="{{ strtolower($urna->modelo) }}"
                         data-color="{{ strtolower($urna->color) }}"
                         data-categoria="{{ strtolower($urna->categoria_etaria) }}">

                        <div>
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <h4 class="font-bold text-slate-900 text-xs">{{ $urna->modelo }}</h4>
                                    <span class="text-[10px] text-slate-400 font-mono">{{ $urna->codigo ?: 'UR-'.$urna->id }}</span>
                                </div>
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-700 border border-indigo-100">
                                    {{ $urna->categoria_etaria }}
                                </span>
                            </div>

                            <div class="mt-2.5 space-y-1 text-[11px] text-slate-600">
                                <p><strong class="text-slate-700">Color:</strong> {{ $urna->color }}</p>
                                <p><strong class="text-slate-700">Medidas:</strong> {{ $urna->medidas_cm }}</p>
                                <p><strong class="text-slate-700">Tallado:</strong> {{ $urna->tallado ?: 'Estándar' }}</p>
                            </div>

                            <!-- Disponibilidad Dual de Stock -->
                            <div class="mt-3 p-2 rounded-lg bg-slate-50 border border-slate-100 grid grid-cols-2 gap-1 text-[10px]">
                                <div>
                                    <span class="text-slate-500 block">Salón Blanco:</span>
                                    <strong class="{{ $urna->stock_salon > 0 ? 'text-emerald-700' : 'text-slate-400' }} font-bold">
                                        {{ $urna->stock_salon }} unid.
                                    </strong>
                                </div>
                                <div>
                                    <span class="text-slate-500 block">Taller:</span>
                                    <strong class="{{ $urna->stock_taller > 0 ? 'text-indigo-700' : 'text-slate-400' }} font-bold">
                                        {{ $urna->stock_taller }} unid.
                                    </strong>
                                </div>
                            </div>
                        </div>

                        <!-- Botón de Asignación -->
                        <form method="POST" action="{{ route('servicios.urna.asignar', $servicio) }}" class="mt-4 pt-3 border-t border-slate-100">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="urna_id" value="{{ $urna->id }}">
                            @if($esActual)
                                <button type="button" disabled class="w-full py-1.5 rounded-lg bg-emerald-600 text-white font-bold text-xs flex items-center justify-center cursor-default">
                                    <i class="fa-solid fa-check mr-1.5"></i>Asignada Actualmente
                                </button>
                            @else
                                <button type="submit" class="w-full py-1.5 rounded-lg bg-indigo-900 hover:bg-indigo-800 text-white font-bold text-xs transition flex items-center justify-center shadow-xs">
                                    <i class="fa-solid fa-plus mr-1.5"></i>Asignar a este Servicio
                                </button>
                            @endif
                        </form>
                    </div>
                @endforeach
            </div>
            <div id="no-urnas-msg" class="hidden text-center py-10 text-slate-500 text-xs">
                No se encontraron urnas que coincidan con la búsqueda.
            </div>
        </div>

        <!-- Pie de Modal -->
        <div class="px-6 py-3.5 bg-slate-50 border-t border-slate-100 flex items-center justify-between flex-shrink-0 text-xs">
            <a href="{{ route('bodega.index') }}" target="_blank" rel="noopener" class="text-indigo-700 hover:text-indigo-900 font-bold flex items-center">
                <i class="fa-solid fa-warehouse mr-1.5"></i>Ir al Módulo de Urnas y Bodega Dual (/bodega)
                <i class="fa-solid fa-arrow-up-right-from-square text-[10px] ml-1 opacity-70"></i>
            </a>
            <button type="button" onclick="cerrarModalUrnas()" class="px-4 py-2 rounded-lg border border-slate-200 text-slate-700 hover:bg-white font-semibold transition">
                Cerrar
            </button>
        </div>
    </div>
</div>

<!-- MODAL REGISTRO RÁPIDO DE PAGO DE CUOTA -->
<div id="modal-pago-cuota" class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="relative w-full max-w-md bg-white rounded-2xl shadow-2xl border border-slate-100 p-6 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <div class="flex items-center gap-2">
                <span class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold">
                    <i class="fa-solid fa-receipt"></i>
                </span>
                <div>
                    <h3 class="font-serif-title text-base font-bold text-slate-900">Registrar Pago de Cuota</h3>
                    <p class="text-[11px] text-slate-400">Expediente #{{ $servicio->id }} · {{ $servicio->cliente_contratante }}</p>
                </div>
            </div>
            <button type="button" onclick="cerrarModalPagoRapido()" class="text-slate-400 hover:text-slate-600 p-1">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <form id="form-pago-cuota" method="POST" action="" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Cuota a pagar</label>
                <select id="modal-select-cuota" name="cuota_id" class="field w-full text-xs" onchange="actualizarMontoModal(this)">
                    @foreach($servicio->liquidacion?->cuotas ?? [] as $cuota)
                        <option value="{{ $cuota->id }}"
                                data-url="{{ route('servicios.cuotas.pagar', [$servicio, $cuota]) }}"
                                @disabled($cuota->estado === 'pagada')>
                            Cuota {{ $cuota->numero_cuota }} — {{ $cuota->montoFormateado() }} ({{ $cuota->estadoLabel() }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Método de pago *</label>
                <select name="metodo_pago" class="field w-full text-xs" required>
                    @foreach(\App\Models\Liquidacion::METODOS_PAGO as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Comprobante / N° Transacción (Opcional)</label>
                <input type="text" name="comprobante_referencia" class="field w-full text-xs" placeholder="Ej: Transf. 984723 o Transbank OP #5432">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Observaciones</label>
                <textarea name="observaciones" rows="2" class="field w-full text-xs" placeholder="Detalles adicionales del abono..."></textarea>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                <button type="button" onclick="cerrarModalPagoRapido()" class="button-secondary text-xs">Cancelar</button>
                <button type="submit" class="button-primary text-xs">
                    <i class="fa-solid fa-check mr-1.5"></i>Confirmar Pago
                </button>
            </div>
        </form>
    </div>
</div>

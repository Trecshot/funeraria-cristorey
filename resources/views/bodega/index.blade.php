@extends('layouts.app')

@section('title', 'Urnas y Bodega')
@section('heading', 'Control de Bodega Dual (Salón Calle Blanco vs. Taller)')

@section('content')
    <div class="space-y-6">
        @if(session('status'))
            <div class="rounded-2xl border border-emerald-300 bg-emerald-50 p-4 text-xs text-emerald-950 flex items-center space-x-3 shadow-sm" role="alert">
                <i class="fa-solid fa-circle-check text-emerald-600 text-base"></i>
                <span>{{ session('status') }}</span>
            </div>
        @endif

        @php
            $urnasCriticas = $urnas->filter(fn ($u) => $u->stock_critico || ($u->stock_salon <= 0 && $u->stock_taller > 0));
        @endphp

        @if($urnasCriticas->isNotEmpty())
            <div class="rounded-2xl border border-amber-300 bg-amber-50 p-4 text-xs text-amber-950 flex items-center justify-between shadow-sm" role="alert">
                <div class="flex items-center space-x-3">
                    <i class="fa-solid fa-triangle-exclamation text-amber-600 text-base"></i>
                    <div>
                        <strong>Alertas activas en bodega ({{ $urnasCriticas->count() }} modelos):</strong>
                        <span>Existen modelos con existencias críticas o sin reposición en el Salón Calle Blanco.</span>
                    </div>
                </div>
                <a href="#tabla-inventario" class="font-bold underline hover:text-amber-900 text-[11px]">Ver modelos →</a>
            </div>
        @endif

        <!-- Banner de Control Dual y Normativa Seremi -->
        <section class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 text-xs space-y-4">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
                <div>
                    <h3 class="font-serif-title text-xl font-bold text-slate-900">Control de Bodega Dual (Salón Calle Blanco vs. Taller)</h3>
                    <p class="text-slate-500 mt-0.5">
                        Proveedor San Javier (reposición semanal los lunes) vs. Fábrica Santiago (camión de 24 urnas).
                        Cumple fiscalización Seremi de Salud (categoría infantil 30-60 cm y formatos XL/Bariátricos).
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-[11px] font-semibold bg-indigo-50 text-indigo-700 border border-indigo-100">
                        <i class="fa-solid fa-clipboard-check mr-1.5"></i>Normativa Seremi al día
                    </span>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-100">
                        <i class="fa-solid fa-boxes-packing mr-1.5"></i>30 modelos registrados
                    </span>
                </div>
            </div>

            <!-- Métricas globales duales -->
            <div class="p-4 bg-gradient-to-r from-indigo-50 via-slate-50 to-indigo-50/60 rounded-xl font-semibold text-indigo-950 grid grid-cols-1 sm:grid-cols-3 gap-4 border border-indigo-100/70">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-xl bg-indigo-600 text-white flex items-center justify-center text-base shadow-sm">
                        <i class="fa-solid fa-store"></i>
                    </div>
                    <div>
                        <span class="text-[11px] text-slate-500 font-medium block">Salón Calle Blanco (Exhibición)</span>
                        <strong class="text-lg text-indigo-950">{{ $urnas->sum('stock_salon') }}</strong> <span class="text-xs text-slate-500">urnas disponibles</span>
                    </div>
                </div>

                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-xl bg-slate-700 text-white flex items-center justify-center text-base shadow-sm">
                        <i class="fa-solid fa-warehouse"></i>
                    </div>
                    <div>
                        <span class="text-[11px] text-slate-500 font-medium block">Bodega Taller (Almacenamiento)</span>
                        <strong class="text-lg text-slate-900">{{ $urnas->sum('stock_taller') }}</strong> <span class="text-xs text-slate-500">urnas en reserva</span>
                    </div>
                </div>

                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-600 text-white flex items-center justify-center text-base shadow-sm">
                        <i class="fa-solid fa-boxes-stacked"></i>
                    </div>
                    <div>
                        <span class="text-[11px] text-slate-500 font-medium block">Inventario General Dual</span>
                        <strong class="text-lg text-emerald-950">{{ $urnas->sum('stock_total') }}</strong> <span class="text-xs text-slate-500">unidades totales</span>
                    </div>
                </div>
            </div>

            <!-- Filtros rápidos por rango etario y tipo -->
            <div class="flex flex-wrap items-center gap-2 pt-1 border-t border-slate-100">
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mr-1">Filtrar tabla:</span>
                <button type="button" class="filtro-btn px-3 py-1 rounded-lg text-xs font-semibold bg-indigo-700 text-white shadow-sm" data-categoria="todos">
                    Todos ({{ $urnas->count() }})
                </button>
                <button type="button" class="filtro-btn px-3 py-1 rounded-lg text-xs font-semibold bg-slate-100 text-slate-700 hover:bg-slate-200" data-categoria="Adulto">
                    <i class="fa-solid fa-user mr-1 text-slate-500"></i>Adulto ({{ $urnas->where('categoria_etaria', 'Adulto')->count() }})
                </button>
                <button type="button" class="filtro-btn px-3 py-1 rounded-lg text-xs font-semibold bg-slate-100 text-slate-700 hover:bg-slate-200" data-categoria="Juvenil">
                    <i class="fa-solid fa-user-graduate mr-1 text-indigo-500"></i>Juvenil ({{ $urnas->where('categoria_etaria', 'Juvenil')->count() }})
                </button>
                <button type="button" class="filtro-btn px-3 py-1 rounded-lg text-xs font-semibold bg-slate-100 text-slate-700 hover:bg-slate-200" data-categoria="Niño">
                    <i class="fa-solid fa-child mr-1 text-sky-500"></i>Infantil / Niño ({{ $urnas->where('categoria_etaria', 'Niño')->count() }})
                </button>
                <button type="button" class="filtro-btn px-3 py-1 rounded-lg text-xs font-semibold bg-slate-100 text-slate-700 hover:bg-slate-200" data-categoria="Especial">
                    <i class="fa-solid fa-maximize mr-1 text-amber-500"></i>Especial XL ({{ $urnas->where('categoria_etaria', 'Especial')->count() }})
                </button>
                <button type="button" class="filtro-btn px-3 py-1 rounded-lg text-xs font-semibold bg-slate-100 text-slate-700 hover:bg-slate-200" data-categoria="Personalizada">
                    <i class="fa-solid fa-wand-magic-sparkles mr-1 text-violet-500"></i>Personalizadas ({{ $urnas->where('personalizada', true)->count() }})
                </button>
            </div>
        </section>

        <!-- TABLA MEJORADA DE INVENTARIO DUAL -->
        <section id="tabla-inventario" class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="border-b border-slate-100 px-6 py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <h2 class="font-serif-title text-lg font-bold text-slate-900">Urnas · Salón (Calle Blanco) y Taller</h2>
                    <p class="mt-0.5 text-xs text-slate-500">Control de existencias en tiempo real, medidas oficiales Seremi, personalización y precios comerciales.</p>
                </div>
                <div class="flex items-center gap-2">
                    <div class="relative">
                        <i class="fa-solid fa-magnifying-glass absolute left-3 top-2.5 text-slate-400 text-xs"></i>
                        <input id="buscador-urnas" type="text" placeholder="Buscar código, modelo o color..." class="field pl-8 py-1.5 text-xs w-64">
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-xs">
                    <thead class="bg-indigo-50/70 text-left text-[10px] uppercase tracking-wide text-indigo-950 font-bold">
                        <tr>
                            <th class="px-4 py-3.5">Código / Modelo</th>
                            <th class="px-4 py-3.5">Categoría & Medidas</th>
                            <th class="px-4 py-3.5">Color / Tallado</th>
                            <th class="px-4 py-3.5 text-right">Precio (CLP)</th>
                            <th class="px-4 py-3.5 text-center">Salón (Blanco)</th>
                            <th class="px-4 py-3.5 text-center">Taller</th>
                            <th class="px-4 py-3.5 text-center">Total</th>
                            <th class="px-4 py-3.5 text-center">Mín.</th>
                            <th class="px-4 py-3.5">Estado</th>
                            <th class="px-4 py-3.5 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tabla-urnas-body" class="divide-y divide-slate-100">
                        @forelse($urnas as $urna)
                            @php
                                $estado = $urna->estado_badge;
                                $catBadgeColor = match ($urna->categoria_etaria) {
                                    'Niño' => 'bg-sky-50 text-sky-800 border-sky-200',
                                    'Juvenil' => 'bg-purple-50 text-purple-800 border-purple-200',
                                    'Especial' => 'bg-amber-50 text-amber-900 border-amber-200',
                                    default => 'bg-slate-100 text-slate-800 border-slate-200',
                                };
                            @endphp
                            <tr class="urna-fila hover:bg-slate-50/80 transition"
                                data-categoria="{{ $urna->categoria_etaria }}"
                                data-personalizada="{{ $urna->personalizada ? '1' : '0' }}"
                                data-busqueda="{{ strtolower($urna->codigo.' '.$urna->modelo.' '.$urna->color.' '.$urna->tallado.' '.$urna->categoria_etaria) }}">
                                
                                <!-- Código / Modelo -->
                                <td class="px-4 py-3 align-middle font-bold text-slate-900">
                                    <div class="flex items-center space-x-1.5">
                                        <span class="font-mono text-[11px] font-bold text-indigo-700 bg-indigo-50 px-1.5 py-0.5 rounded border border-indigo-100">
                                            {{ $urna->codigo }}
                                        </span>
                                        @if($urna->personalizada)
                                            <span class="bg-violet-100 text-violet-800 text-[10px] font-extrabold px-1.5 py-0.5 rounded border border-violet-200" title="Urna personalizada con grabados láser o herrajes a pedido">
                                                <i class="fa-solid fa-wand-magic-sparkles mr-0.5"></i>Personalizada
                                            </span>
                                        @endif
                                    </div>
                                    <span class="block text-slate-900 font-semibold mt-1">{{ $urna->modelo }}</span>
                                </td>

                                <!-- Categoría & Medidas -->
                                <td class="px-4 py-3 align-middle">
                                    <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $catBadgeColor }}">
                                        {{ $urna->categoria_etaria }}
                                    </span>
                                    <span class="block text-slate-500 font-mono text-[11px] mt-0.5">
                                        {{ $urna->medidas_cm }}
                                    </span>
                                </td>

                                <!-- Color / Tallado -->
                                <td class="px-4 py-3 align-middle text-slate-700">
                                    <span class="font-medium text-slate-900">{{ $urna->color ?: '—' }}</span>
                                    @if($urna->tallado)
                                        <span class="block text-[11px] text-slate-500">/ {{ $urna->tallado }}</span>
                                    @endif
                                </td>

                                <!-- Precio CLP -->
                                <td class="px-4 py-3 align-middle text-right font-mono font-bold text-slate-900">
                                    {{ $urna->precio_formateado }}
                                </td>

                                <!-- Salón (Calle Blanco) -->
                                <td class="px-4 py-3 align-middle text-center">
                                    <span class="inline-block px-2 py-0.5 rounded font-bold {{ $urna->stock_salon <= 0 ? 'bg-rose-100 text-rose-800' : 'bg-slate-100 text-slate-900' }}">
                                        {{ $urna->stock_salon }}
                                    </span>
                                </td>

                                <!-- Bodega Taller -->
                                <td class="px-4 py-3 align-middle text-center">
                                    <span class="inline-block px-2 py-0.5 rounded font-semibold bg-slate-100 text-slate-700">
                                        {{ $urna->stock_taller }}
                                    </span>
                                </td>

                                <!-- Total -->
                                <td class="px-4 py-3 align-middle text-center">
                                    <span class="font-extrabold text-indigo-950 text-sm">
                                        {{ $urna->stock_total }}
                                    </span>
                                </td>

                                <!-- Mínimo -->
                                <td class="px-4 py-3 align-middle text-center text-slate-500">
                                    {{ $urna->stock_minimo }}
                                </td>

                                <!-- Estado -->
                                <td class="px-4 py-3 align-middle">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold {{ $estado['class'] }}">
                                        <i class="fa-solid {{ $estado['icon'] }} mr-1"></i>{{ $estado['label'] }}
                                    </span>
                                </td>

                                <!-- Acciones / Traspaso rápido -->
                                <td class="px-4 py-3 align-middle text-center">
                                    <button type="button"
                                        class="btn-rapido-traspaso text-[11px] font-semibold text-indigo-700 hover:text-indigo-950 bg-indigo-50 hover:bg-indigo-100 px-2 py-1 rounded-lg border border-indigo-200 transition"
                                        data-urna-id="{{ $urna->id }}"
                                        data-modelo="{{ $urna->modelo }}"
                                        data-stock-salon="{{ $urna->stock_salon }}"
                                        data-stock-taller="{{ $urna->stock_taller }}">
                                        <i class="fa-solid fa-right-left mr-1"></i>Traspaso
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="px-4 py-8 text-center text-xs text-slate-500" colspan="10">
                                    No hay urnas registradas en el inventario. Agrega modelos desde el formulario inferior.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <!-- FORMULARIOS: AGREGAR URNA DUAL + TRASPASO AUDITADO -->
        <div class="grid gap-6 xl:grid-cols-2">
            <!-- Formulario Ampliado de Registro / Repoblamiento -->
            <section class="panel">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h2 class="font-serif-title text-lg font-bold text-slate-900">
                        <i class="fa-solid fa-plus-circle text-indigo-700 mr-1.5"></i>Agregar urna al inventario dual
                    </h2>
                    <span class="text-[10px] uppercase font-bold text-slate-400 bg-slate-100 px-2 py-0.5 rounded">Formulario Seremi & Comercial</span>
                </div>

                <form method="POST" action="{{ route('bodega.productos.store') }}" class="mt-4 grid gap-3 sm:grid-cols-2 text-xs">
                    @csrf

                    <!-- Código -->
                    <label class="block">
                        <span class="label">Código Único (opcional, auto si vacío)</span>
                        <input class="field font-mono" name="codigo" placeholder="Ej: URN-AD-15, URN-NN-07">
                    </label>

                    <!-- Modelo -->
                    <label class="block">
                        <span class="label">Modelo *</span>
                        <input class="field" name="modelo" placeholder="Marquetina, Lisa, Paloma-biblia" list="modelos-sugeridos" required>
                        <datalist id="modelos-sugeridos">
                            <option value="Marquetina Imperial">
                            <option value="Modelo Lisa">
                            <option value="Paloma-Biblia">
                            <option value="Cuna Angelical">
                            <option value="Italiana Catedral">
                            <option value="Cristo Redentor">
                            <option value="Señor de la Misericordia">
                            <option value="Presidencial Real">
                            <option value="Especial XL Reforzada">
                        </datalist>
                    </label>

                    <!-- Categoría Etaria -->
                    <label class="block">
                        <span class="label">Categoría Etaria *</span>
                        <select class="field font-semibold" name="categoria_etaria" id="form-categoria-etaria" required>
                            <option value="Adulto" selected>Adulto (1.85m - 1.95m)</option>
                            <option value="Juvenil">Juvenil (1.20m - 1.60m)</option>
                            <option value="Niño">Infantil / Niño (30cm - 60cm Seremi)</option>
                            <option value="Especial">Especial / XL / Bariátrica (> 2.05m)</option>
                        </select>
                    </label>

                    <!-- Medidas Exactas -->
                    <label class="block">
                        <span class="label">Medidas exactas (cm / metros) *</span>
                        <input class="field font-mono" name="medidas_cm" id="form-medidas" placeholder='Ej: "1.90m x 65cm", "45 x 25 cm"' value="1.90m x 65cm" required>
                    </label>

                    <!-- Color -->
                    <label class="block">
                        <span class="label">Color *</span>
                        <input class="field" name="color" placeholder="Caoba, Nogal, Natural, Lacado Blanco" list="colores-sugeridos" required>
                        <datalist id="colores-sugeridos">
                            <option value="Caoba">
                            <option value="Nogal">
                            <option value="Natural">
                            <option value="Lacado Blanco">
                            <option value="Roble Claro">
                            <option value="Castaño Rústico">
                            <option value="Cerezo">
                        </datalist>
                    </label>

                    <!-- Tallado / Aplicaciones -->
                    <label class="block">
                        <span class="label">Tallado / Detalles</span>
                        <input class="field" name="tallado" placeholder="Cruz, Paloma, Liso, Biblia, Ángeles" list="tallados-sugeridos">
                        <datalist id="tallados-sugeridos">
                            <option value="Cruz">
                            <option value="Paloma">
                            <option value="Liso">
                            <option value="Paloma y Sagrada Biblia">
                            <option value="Cruz y Ángeles Dorados">
                            <option value="Grabado Láser Personalizado">
                        </datalist>
                    </label>

                    <!-- Precio Comercial CLP -->
                    <label class="block">
                        <span class="label">Precio Comercial (CLP) *</span>
                        <input class="field font-mono" type="number" step="1000" min="0" name="precio" placeholder="Ej: 450000" required>
                    </label>

                    <!-- Stock Mínimo para Alerta -->
                    <label class="block">
                        <span class="label">Mínimo para alerta de stock *</span>
                        <input class="field font-mono" type="number" min="0" name="stock_minimo" value="1" required>
                    </label>

                    <!-- Distribución Dual de Stock Inicial -->
                    <div class="sm:col-span-2 p-3 bg-slate-50 rounded-xl border border-slate-200/70 space-y-2">
                        <span class="block text-[11px] font-bold uppercase tracking-wider text-slate-700">
                            <i class="fa-solid fa-boxes-packing text-indigo-700 mr-1"></i>Distribución inicial de existencias (Control Dual):
                        </span>
                        <div class="grid grid-cols-2 gap-3">
                            <label class="block">
                                <span class="label">Stock Salón (Calle Blanco)</span>
                                <input class="field font-mono" type="number" min="0" name="stock_salon" value="1" required>
                            </label>
                            <label class="block">
                                <span class="label">Stock Bodega Taller</span>
                                <input class="field font-mono" type="number" min="0" name="stock_taller" value="2" required>
                            </label>
                        </div>
                    </div>

                    <!-- Atributo Personalizada -->
                    <div class="sm:col-span-2 pt-1 flex flex-col gap-2">
                        <label class="flex items-center space-x-2 text-xs text-slate-700 cursor-pointer">
                            <input type="checkbox" name="personalizada" value="1" class="accent-indigo-700 rounded w-4 h-4">
                            <span class="font-semibold text-slate-900">
                                <i class="fa-solid fa-wand-magic-sparkles text-violet-600 mr-1"></i>Urna Personalizada (Grabados láser, herrajes especiales o aplicaciones a pedido)
                            </span>
                        </label>

                        <label class="flex items-center space-x-2 text-xs text-slate-700 cursor-pointer">
                            <input type="checkbox" name="mostrar_catalogo" value="1" checked class="accent-indigo-700 rounded w-4 h-4">
                            <span>Mostrar en el Catálogo Público Web (sin exhibición de precios internos)</span>
                        </label>
                    </div>

                    <div class="sm:col-span-2 pt-2">
                        <button class="button-primary w-full py-2.5">
                            <i class="fa-solid fa-box-open mr-1.5"></i>Registrar urna en inventario dual
                        </button>
                    </div>
                </form>
            </section>

            <!-- Formulario de Traspaso entre Salón y Taller -->
            <section class="panel">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h2 class="font-serif-title text-lg font-bold text-slate-900">
                        <i class="fa-solid fa-dolly text-indigo-700 mr-1.5"></i>Traspaso entre ubicaciones (Taller ↔ Salón)
                    </h2>
                    <span class="text-[10px] uppercase font-bold text-slate-400 bg-slate-100 px-2 py-0.5 rounded">Logística Dual</span>
                </div>

                <form method="POST" action="{{ route('bodega.traspaso') }}" class="mt-4 space-y-3.5 text-xs">
                    @csrf

                    <label class="block">
                        <span class="label">Producto a traspasar *</span>
                        <select class="field" name="producto_id" id="traspaso-producto-id" required>
                            @foreach($urnas as $u)
                                <option value="{{ $u->id }}" data-salon="{{ $u->stock_salon }}" data-taller="{{ $u->stock_taller }}">
                                    [{{ $u->codigo }}] {{ $u->modelo }} ({{ $u->categoria_etaria }}) · Salón: {{ $u->stock_salon }} | Taller: {{ $u->stock_taller }}
                                </option>
                            @endforeach
                        </select>
                    </label>

                    <div class="grid grid-cols-2 gap-3">
                        <label class="block">
                            <span class="label">Desde (Origen)</span>
                            <select class="field font-semibold" name="origen" id="traspaso-origen">
                                <option value="taller" selected>Bodega Taller</option>
                                <option value="salon">Salón · Calle Blanco</option>
                            </select>
                        </label>

                        <label class="block">
                            <span class="label">Hacia (Destino)</span>
                            <select class="field font-semibold" name="destino" id="traspaso-destino">
                                <option value="salon" selected>Salón · Calle Blanco</option>
                                <option value="taller">Bodega Taller</option>
                            </select>
                        </label>
                    </div>

                    <label class="block">
                        <span class="label">Cantidad de urnas a mover *</span>
                        <input class="field font-mono" type="number" min="1" value="1" name="cantidad" required>
                    </label>

                    <div class="p-3 bg-amber-50 rounded-xl border border-amber-200 text-amber-900 text-[11px] flex items-start space-x-2">
                        <i class="fa-solid fa-circle-info text-amber-600 mt-0.5"></i>
                        <span>Los traspasos actualizan de forma inmediata el saldo en exhibición y quedan auditados con la firma del usuario responsable.</span>
                    </div>

                    <button class="button-primary w-full py-2.5">
                        <i class="fa-solid fa-right-left mr-1.5"></i>Registrar traspaso logístico
                    </button>
                </form>
            </section>
        </div>

        <!-- MOVIMIENTOS RECIENTES AUDITADOS -->
        <section class="panel">
            <h2 class="font-serif-title text-lg font-bold text-slate-900">
                <i class="fa-solid fa-clock-rotate-left text-indigo-700 mr-1.5"></i>Movimientos recientes auditados (Taller ↔ Salón)
            </h2>
            <div class="mt-3 divide-y divide-slate-100">
                @forelse($movimientos as $movimiento)
                    <div class="py-2.5 text-xs text-slate-700 flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <span class="font-semibold text-slate-900 font-mono">{{ $movimiento->created_at->format('d/m/Y H:i') }}</span> ·
                            <strong>{{ $movimiento->cantidad }} × {{ $movimiento->producto->modelo ?? 'Urna' }}</strong>
                            <span class="text-slate-500 font-mono">({{ $movimiento->producto->codigo ?? '' }})</span>
                        </div>
                        <div class="flex items-center space-x-3">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-50 text-indigo-800 border border-indigo-100">
                                {{ ucfirst($movimiento->origen) }} → {{ ucfirst($movimiento->destino) }}
                            </span>
                            <span class="text-indigo-900 font-medium text-[11px]">
                                <i class="fa-solid fa-user-shield mr-1 text-slate-400"></i>{{ $movimiento->user->name ?? 'Sistema' }}
                            </span>
                        </div>
                    </div>
                @empty
                    <p class="py-3 text-xs text-slate-500">Sin traspasos registrados recientemente.</p>
                @endforelse
            </div>
        </section>
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Toggle dinámico de Origen / Destino en traspasos
        const origen = document.getElementById('traspaso-origen');
        const destino = document.getElementById('traspaso-destino');
        if (origen && destino) {
            origen.addEventListener('change', () => {
                destino.value = origen.value === 'salon' ? 'taller' : 'salon';
            });
            destino.addEventListener('change', () => {
                origen.value = destino.value === 'salon' ? 'taller' : 'salon';
            });
        }

        // Sugerencia de medidas según categoría en formulario
        const catSelect = document.getElementById('form-categoria-etaria');
        const medidasInput = document.getElementById('form-medidas');
        if (catSelect && medidasInput) {
            catSelect.addEventListener('change', () => {
                switch(catSelect.value) {
                    case 'Niño':
                        medidasInput.value = '45 x 25 cm';
                        break;
                    case 'Juvenil':
                        medidasInput.value = '1.40m x 50cm';
                        break;
                    case 'Especial':
                        medidasInput.value = '2.10m x 75cm';
                        break;
                    default:
                        medidasInput.value = '1.90m x 65cm';
                }
            });
        }

        // Buscador en tiempo real de urnas
        const buscador = document.getElementById('buscador-urnas');
        const filas = document.querySelectorAll('.urna-fila');

        function filtrarTabla() {
            const termino = buscador ? buscador.value.trim().toLowerCase() : '';
            const filtroActivo = document.querySelector('.filtro-btn.bg-indigo-700')?.dataset.categoria || 'todos';

            filas.forEach(fila => {
                const texto = fila.dataset.busqueda || '';
                const cat = fila.dataset.categoria || '';
                const esPersonalizada = fila.dataset.personalizada === '1';

                const coincideTexto = !termino || texto.includes(termino);
                let coincideCategoria = true;

                if (filtroActivo === 'Personalizada') {
                    coincideCategoria = esPersonalizada;
                } else if (filtroActivo !== 'todos') {
                    coincideCategoria = (cat === filtroActivo);
                }

                fila.style.display = (coincideTexto && coincideCategoria) ? '' : 'none';
            });
        }

        if (buscador) {
            buscador.addEventListener('input', filtrarTabla);
        }

        // Botones de filtro de categoría
        const botonesFiltro = document.querySelectorAll('.filtro-btn');
        botonesFiltro.forEach(btn => {
            btn.addEventListener('click', () => {
                botonesFiltro.forEach(b => {
                    b.classList.remove('bg-indigo-700', 'text-white', 'shadow-sm');
                    b.classList.add('bg-slate-100', 'text-slate-700');
                });
                btn.classList.remove('bg-slate-100', 'text-slate-700');
                btn.classList.add('bg-indigo-700', 'text-white', 'shadow-sm');
                filtrarTabla();
            });
        });

        // Botones de Traspaso Rápido desde la tabla
        const botonesRapido = document.querySelectorAll('.btn-rapido-traspaso');
        const selectProducto = document.getElementById('traspaso-producto-id');
        botonesRapido.forEach(btn => {
            btn.addEventListener('click', () => {
                const id = btn.dataset.urnaId;
                if (selectProducto) {
                    selectProducto.value = id;
                    const stockSalon = parseInt(btn.dataset.stockSalon || '0');
                    const stockTaller = parseInt(btn.dataset.stockTaller || '0');
                    if (stockSalon <= 0 && stockTaller > 0 && origen && destino) {
                        origen.value = 'taller';
                        destino.value = 'salon';
                    }
                    selectProducto.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    selectProducto.classList.add('ring-2', 'ring-indigo-500');
                    setTimeout(() => selectProducto.classList.remove('ring-2', 'ring-indigo-500'), 1500);
                }
            });
        });
    });
</script>
@endpush

@extends('layouts.app')

@section('title', 'Finanzas')
@section('heading', 'Liquidación de Convenios (IPS / AFP 15 UF) y Comprobantes Foliados')

@section('content')
    <div class="space-y-6">

        @if(session('status'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs text-emerald-800 flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <i class="fa-solid fa-circle-check text-emerald-600 text-sm"></i>
                    <span>{{ session('status') }}</span>
                </div>
            </div>
        @endif

        <!-- 1. ALERTA REACTIVA HORARIA DE CALL CENTER 600 -->
        <section id="banner-callcenter">
            @if($callCenterAbierto)
                <div class="rounded-2xl border border-emerald-200 bg-gradient-to-r from-emerald-50 to-teal-50/50 p-4 text-xs text-emerald-950 shadow-sm flex items-center justify-between">
                    <div class="flex items-center space-x-3.5">
                        <div class="w-10 h-10 rounded-xl bg-emerald-500/10 text-emerald-700 flex items-center justify-center text-lg flex-shrink-0">
                            <i class="fa-solid fa-headset"></i>
                        </div>
                        <div>
                            <div class="flex items-center space-x-2">
                                <span class="font-bold text-slate-900 text-sm">Call Center 600 de Validación Institucional: En línea</span>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-600 mr-1.5 animate-pulse"></span>Operativo
                                </span>
                            </div>
                            <p class="text-slate-600 text-[11px] mt-0.5">
                                Horario hábil: Lunes a Viernes de 09:00 a 17:00 hrs · Hora actual en Santiago: <strong class="text-slate-800" id="live-time">{{ $horaSantiago }}</strong> hrs.
                                Las verificaciones de convenios IPS y AFP se confirman en línea durante este horario.
                            </p>
                        </div>
                    </div>
                </div>
            @else
                <div class="rounded-2xl border border-amber-300 bg-gradient-to-r from-amber-50 to-orange-50/60 p-4 text-xs text-amber-950 shadow-sm flex items-start space-x-3.5">
                    <div class="w-10 h-10 rounded-xl bg-amber-500/15 text-amber-700 flex items-center justify-center text-lg flex-shrink-0 mt-0.5">
                        <i class="fa-solid fa-clock-rotate-left"></i>
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center space-x-2">
                            <span class="font-bold text-amber-950 text-sm">Call Center 600 Cerrado (Fuera de Horario Hábil Institucional)</span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-200/70 text-amber-900">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-600 mr-1.5"></span>Cerrado tras las 17:00 hrs
                            </span>
                        </div>
                        <p class="text-amber-900/90 text-[11px] mt-1 leading-relaxed">
                            El servicio de validación telefónica e interconexión institucional (IPS / AFP) atiende de <strong>Lunes a Viernes entre 09:00 y 17:00 hrs</strong> (Hora local en Santiago: <strong id="live-time">{{ $horaSantiago }}</strong> hrs).
                            Las aperturas y revisiones de convenios registradas fuera de este horario quedarán en estado <strong>Pendiente de Confirmación</strong> hasta el siguiente día hábil. Puede continuar emitiendo comprobantes foliados y recibiendo copagos familiares normalmente.
                        </p>
                    </div>
                </div>
            @endif
        </section>

        <!-- 2. CONVENIOS PREVISIONALES Y CONVERSIÓN DINÁMICA DE LA UF -->
        <section class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 text-xs space-y-4">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-2 border-b border-slate-100 pb-3">
                <div>
                    <h3 class="font-serif-title text-xl font-bold text-slate-900">Liquidación de Convenios (IPS / AFP 15 UF) y Comprobantes Foliados</h3>
                    <p class="text-slate-500 text-xs">Cálculo dinámico de coberturas con integración en tiempo real al valor diario de la UF chilena y emisión de documentos tributarios SII.</p>
                </div>
                <div class="inline-flex items-center space-x-1.5 px-3 py-1 rounded-lg bg-indigo-50 border border-indigo-100 text-indigo-900 font-semibold text-[11px] self-start md:self-auto">
                    <i class="fa-solid fa-building-columns text-indigo-700"></i>
                    <span>Sede Calle Blanco · Temuco</span>
                </div>
            </div>

            <div class="grid gap-3.5 text-xs sm:grid-cols-3">
                <!-- IPS -->
                <div class="rounded-xl bg-gradient-to-br from-indigo-50/70 to-blue-50/40 border border-indigo-100 p-4 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between text-slate-500 mb-1">
                            <span>IPS / PGU (Asignación Muerte)</span>
                            <i class="fa-solid fa-shield-halved text-indigo-600"></i>
                        </div>
                        <strong class="text-xl text-indigo-950 font-extrabold">$1.070.000 CLP</strong>
                    </div>
                    <span class="block text-[11px] text-slate-500 mt-2 font-medium">Cobertura legal fija regulada por IPS / Ley 20.255</span>
                </div>

                <!-- AFP CON UF DINÁMICA -->
                <div class="rounded-xl bg-gradient-to-br from-indigo-50/70 to-purple-50/40 border border-indigo-100 p-4 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between text-slate-500 mb-1">
                            <span>AFP (Cuota Mortuoria)</span>
                            <span class="text-[10px] font-bold text-indigo-700 bg-white px-2 py-0.5 rounded-full border border-indigo-100">15 UF</span>
                        </div>
                        <strong class="text-xl text-indigo-950 font-extrabold">${{ number_format($uf15Clp, 0, ',', '.') }} CLP</strong>
                    </div>
                    <div class="mt-2 flex items-center justify-between text-[11px]">
                        <span class="text-emerald-700 font-bold flex items-center">
                            <i class="fa-solid fa-circle-check mr-1 text-[10px]"></i>UF: ${{ number_format($ufValue, 0, ',', '.') }} CLP
                        </span>
                        <span class="text-slate-400 text-[10px]">vía mindicador.cl</span>
                    </div>
                </div>

                <!-- DIPRECA / SINDICATOS -->
                <div class="rounded-xl bg-gradient-to-br from-indigo-50/70 to-slate-50 border border-indigo-100 p-4 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between text-slate-500 mb-1">
                            <span>Dipreca / Sindicatos</span>
                            <i class="fa-solid fa-handshake text-slate-400"></i>
                        </div>
                        <strong class="text-base text-slate-800 font-bold">Aporte según convenio</strong>
                    </div>
                    <span class="block text-[11px] text-slate-500 mt-2 font-medium">Validación contra carta de resguardo institucional</span>
                </div>
            </div>
        </section>

        <!-- 3. MÉTRICAS GLOBALES FINANCIERAS (KPIS) -->
        <section class="grid gap-4 sm:grid-cols-3">
            <!-- KPI 1 -->
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 flex items-center space-x-4">
                <div class="w-12 h-12 rounded-xl bg-indigo-50 border border-indigo-100 text-indigo-700 flex items-center justify-center text-xl flex-shrink-0">
                    <i class="fa-solid fa-building-columns"></i>
                </div>
                <div>
                    <span class="block text-slate-500 text-xs font-medium">Total por Cobrar (Instituciones)</span>
                    <strong class="text-xl font-bold text-slate-900">${{ number_format($kpis['total_instituciones'], 0, ',', '.') }}</strong>
                    <span class="block text-[11px] text-indigo-700 font-medium">Subvenciones IPS / AFP en trámite</span>
                </div>
            </div>

            <!-- KPI 2 -->
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 flex items-center space-x-4">
                <div class="w-12 h-12 rounded-xl bg-amber-50 border border-amber-100 text-amber-700 flex items-center justify-center text-xl flex-shrink-0">
                    <i class="fa-solid fa-users"></i>
                </div>
                <div>
                    <span class="block text-slate-500 text-xs font-medium">Total por Cobrar (Copagos Familias)</span>
                    <strong class="text-xl font-bold text-slate-900">${{ number_format($kpis['total_familias'], 0, ',', '.') }}</strong>
                    <span class="block text-[11px] text-amber-700 font-medium">Diferencias de deudos por recaudar</span>
                </div>
            </div>

            <!-- KPI 3 -->
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 flex items-center space-x-4">
                <div class="w-12 h-12 rounded-xl bg-emerald-50 border border-emerald-100 text-emerald-700 flex items-center justify-center text-xl flex-shrink-0">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
                <div>
                    <span class="block text-slate-500 text-xs font-medium">Total Liquidado este mes</span>
                    <strong class="text-xl font-bold text-slate-900">${{ number_format($kpis['total_liquidado_mes'], 0, ',', '.') }}</strong>
                    <span class="block text-[11px] text-emerald-700 font-medium">Recaudación y liquidaciones recibidas</span>
                </div>
            </div>
        </section>

        <!-- 4. TABLA DE SERVICIOS, COPAGOS Y COMPROBANTES FOLIADOS -->
        <section class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="border-b border-slate-100 px-6 py-4 flex flex-col md:flex-row md:items-center justify-between gap-2">
                <div>
                    <h2 class="font-serif-title text-lg font-bold text-slate-900">Servicios y Desglose de Liquidaciones</h2>
                    <p class="mt-0.5 text-xs text-slate-500">Cálculo dinámico de copagos, control de folios tributarios (Factura Exenta / Boleta SII) y estado de pago.</p>
                </div>
                <div class="flex items-center space-x-2 text-xs">
                    <span class="inline-flex items-center px-2.5 py-1 rounded-md bg-slate-100 text-slate-600 font-medium">
                        <i class="fa-solid fa-calculator mr-1.5 text-indigo-600"></i>Cálculos al vuelo activos
                    </span>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-xs">
                    <thead class="bg-indigo-50/70 text-left text-[10px] uppercase tracking-wide text-indigo-950 font-bold">
                        <tr>
                            <th class="px-4 py-3.5">Difunto / Expediente</th>
                            <th class="px-4 py-3.5">Contratante</th>
                            <th class="px-4 py-3.5">Convenio</th>
                            <th class="px-4 py-3.5">Total Servicio</th>
                            <th class="px-4 py-3.5">Aporte Institución</th>
                            <th class="px-4 py-3.5">Copago Familia</th>
                            <th class="px-4 py-3.5">Estado Liquidación</th>
                            <th class="px-4 py-3.5 text-right">Comprobantes SII</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($servicios as $servicio)
                            @php
                                $liq = $servicio->obtenerOCrearLiquidacion($ufValue);
                                $aporteEstimado = $liq->monto_subvencion;
                                $copagoEstimado = $liq->monto_copago_familia;
                                $saldoPendiente = $liq->saldoCopagoPendiente();
                            @endphp
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="table-cell">
                                    <div class="font-bold text-slate-900">{{ $servicio->difunto }}</div>
                                    <div class="text-[11px] text-slate-500">Exp. #{{ $servicio->id }} · {{ $servicio->folio }}</div>
                                </td>
                                <td class="table-cell">
                                    <div class="text-slate-800 font-medium">{{ $servicio->cliente_contratante }}</div>
                                    <div class="text-[11px] text-slate-500">{{ $servicio->cliente_telefono ?: 'Sin teléfono' }}</div>
                                </td>
                                <td class="table-cell">
                                    <span class="bg-indigo-50 text-indigo-900 border border-indigo-100 px-2.5 py-0.5 rounded-full text-[10px] font-bold">
                                        {{ ucfirst($servicio->convenio) }}
                                        @if(strtolower($servicio->convenio) === 'afp')
                                            (15 UF)
                                        @endif
                                    </span>
                                </td>
                                <td class="table-cell font-semibold text-slate-900">
                                    ${{ number_format((float) $servicio->monto_servicio, 0, ',', '.') }}
                                </td>
                                <td class="table-cell">
                                    <div class="font-bold text-indigo-950">${{ number_format((float) $aporteEstimado, 0, ',', '.') }}</div>
                                    <span class="inline-block mt-0.5 px-2 py-0.5 rounded text-[10px] font-semibold border {{ $liq->badgeSubvencionClass() }}">
                                        {{ $liq->estadoSubvencionLabel() }}
                                    </span>
                                </td>
                                <td class="table-cell">
                                    <div class="font-bold text-indigo-950">${{ number_format((float) $copagoEstimado, 0, ',', '.') }}</div>
                                    <span class="inline-block mt-0.5 px-2 py-0.5 rounded text-[10px] font-semibold border {{ $liq->badgeCopagoClass() }}">
                                        {{ $liq->estadoCopagoLabel() }}
                                    </span>
                                    @if($saldoPendiente > 0 && $liq->monto_copago_pagado > 0)
                                        <div class="text-[10px] text-rose-600 font-medium mt-0.5">Saldo: ${{ number_format($saldoPendiente, 0, ',', '.') }}</div>
                                    @endif
                                </td>
                                <td class="table-cell">
                                    <div class="space-y-1 text-[11px]">
                                        <div class="text-slate-600">
                                            <span class="font-semibold text-slate-700">Factura:</span> {{ $liq->folio_factura ?: 'Sin folio' }}
                                        </div>
                                        <div class="text-slate-600">
                                            <span class="font-semibold text-slate-700">Boleta:</span> {{ $liq->folio_boleta ?: 'Sin folio' }}
                                        </div>
                                    </div>
                                </td>
                                <td class="table-cell text-right space-y-1">
                                    <button
                                        type="button"
                                        onclick="abrirModalLiquidacion({
                                            id: {{ $servicio->id }},
                                            folio: '{{ $servicio->folio }}',
                                            difunto: '{{ addslashes($servicio->difunto) }}',
                                            contratante: '{{ addslashes($servicio->cliente_contratante) }}',
                                            convenio: '{{ addslashes($servicio->convenio) }}',
                                            totalServicio: {{ (int) $servicio->monto_servicio }},
                                            montoSubvencion: {{ (int) $liq->monto_subvencion }},
                                            montoCopagoFamilia: {{ (int) $liq->monto_copago_familia }},
                                            montoCopagoPagado: {{ (int) $liq->monto_copago_pagado }},
                                            estadoSubvencion: '{{ $liq->estado_subvencion }}',
                                            estadoCopago: '{{ $liq->estado_copago }}',
                                            metodoPago: '{{ addslashes($liq->metodo_pago_familia ?? '') }}',
                                            folioFactura: '{{ addslashes($liq->folio_factura ?? '') }}',
                                            folioBoleta: '{{ addslashes($liq->folio_boleta ?? '') }}',
                                            observaciones: '{{ addslashes($liq->observaciones ?? '') }}',
                                            urlUpdate: '{{ route('finanzas.liquidaciones.update', $servicio) }}',
                                            urlPdfFactura: '{{ route('finanzas.factura_exenta', $servicio) }}',
                                            urlPdfBoleta: '{{ route('finanzas.boleta_copago', $servicio) }}',
                                            urlPdfGeneral: '{{ route('finanzas.pdf', $servicio) }}'
                                        })"
                                        class="inline-flex items-center px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-sm transition">
                                        <i class="fa-solid fa-file-lines mr-1.5"></i>Gestionar Comprobante
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="px-4 py-10 text-center text-xs text-slate-500" colspan="8">
                                    Aún no hay servicios registrados para liquidación financiera.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 p-4">{{ $servicios->links() }}</div>
        </section>
    </div>

    <!-- 5. MODAL DE PREVISUALIZACIÓN Y EMISIÓN DE COMPROBANTES FOLIADOS (SII) -->
    <div id="modal-comprobante" class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="relative w-full max-w-3xl rounded-2xl bg-white shadow-2xl border border-slate-100 overflow-hidden transform transition-all animate-in fade-in zoom-in-95 duration-200">

            <!-- Encabezado Modal -->
            <div class="bg-gradient-to-r from-indigo-950 via-indigo-900 to-indigo-800 px-6 py-4 text-white flex items-center justify-between">
                <div>
                    <div class="flex items-center space-x-2">
                        <span class="bg-white/20 text-white text-[10px] font-bold px-2 py-0.5 rounded uppercase tracking-wider">Control Tributario SII</span>
                        <span id="modal-folio-badge" class="text-indigo-200 text-xs font-semibold"></span>
                    </div>
                    <h3 id="modal-difunto" class="font-serif-title text-xl font-bold mt-0.5 text-white"></h3>
                </div>
                <button type="button" onclick="cerrarModalLiquidacion()" class="text-white/70 hover:text-white p-2 rounded-lg hover:bg-white/10 transition">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <!-- Formulario de Liquidación y Folios -->
            <form id="form-liquidacion" method="POST" action="" class="p-6 space-y-5 text-xs">
                @csrf
                @method('PATCH')

                <!-- Resumen Financiero -->
                <div class="grid grid-cols-3 gap-3 p-3.5 rounded-xl bg-slate-50 border border-slate-200">
                    <div>
                        <span class="text-slate-500 block text-[11px]">Costo Total Servicio</span>
                        <strong id="modal-monto-total" class="text-sm font-bold text-slate-900"></strong>
                    </div>
                    <div>
                        <span class="text-slate-500 block text-[11px]">Subvención Convenio</span>
                        <strong id="modal-monto-subvencion" class="text-sm font-bold text-indigo-700"></strong>
                    </div>
                    <div>
                        <span class="text-slate-500 block text-[11px]">Copago Familia Acordado</span>
                        <strong id="modal-monto-copago" class="text-sm font-bold text-slate-900"></strong>
                    </div>
                </div>

                <!-- Dos Columnas: Factura Exenta Institución vs Boleta Copago Familia -->
                <div class="grid md:grid-cols-2 gap-5">

                    <!-- COLUMNA 1: FACTURA EXENTA SII (PARA IPS / AFP) -->
                    <div class="rounded-xl border border-indigo-200 bg-indigo-50/30 p-4 space-y-3">
                        <div class="flex items-center justify-between border-b border-indigo-100 pb-2">
                            <div class="flex items-center space-x-2">
                                <i class="fa-solid fa-file-invoice text-indigo-700 text-base"></i>
                                <h4 class="font-bold text-indigo-950 text-xs uppercase tracking-wide">Factura Exenta SII (Institución)</h4>
                            </div>
                            <span class="text-[10px] font-bold text-indigo-700 bg-indigo-100/70 px-2 py-0.5 rounded">Cobro Convenio</span>
                        </div>
                        <p class="text-[11px] text-slate-500 leading-snug">Documento tributario para liquidar la Cuota Mortuoria (AFP 15 UF) o Asignación por Muerte (IPS).</p>

                        <label class="block">
                            <span class="text-[11px] font-semibold text-slate-700">Folio Factura Exenta:</span>
                            <input type="text" name="folio_factura" id="modal-input-folio-factura" class="field mt-1 w-full text-xs font-mono" placeholder="Ej: FE-00142">
                        </label>

                        <label class="block">
                            <span class="text-[11px] font-semibold text-slate-700">Estado Tramitación Institucional:</span>
                            <select name="estado_subvencion" id="modal-select-estado-subvencion" class="field mt-1 w-full text-xs">
                                @foreach(\App\Models\Liquidacion::ESTADOS_SUBVENCION as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>

                        <div class="pt-2">
                            <a id="modal-link-pdf-factura" href="#" target="_blank" class="w-full inline-flex items-center justify-center px-3 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-sm transition">
                                <i class="fa-solid fa-file-pdf mr-1.5 text-rose-300"></i>Previsualizar Factura Exenta (PDF)
                            </a>
                        </div>
                    </div>

                    <!-- COLUMNA 2: BOLETA DE COPAGO SII (PARA FAMILIA / DEUDOS) -->
                    <div class="rounded-xl border border-emerald-200 bg-emerald-50/20 p-4 space-y-3">
                        <div class="flex items-center justify-between border-b border-emerald-100 pb-2">
                            <div class="flex items-center space-x-2">
                                <i class="fa-solid fa-receipt text-emerald-700 text-base"></i>
                                <h4 class="font-bold text-emerald-950 text-xs uppercase tracking-wide">Boleta Electrónica SII (Familia)</h4>
                            </div>
                            <span class="text-[10px] font-bold text-emerald-700 bg-emerald-100/70 px-2 py-0.5 rounded">Copago Deudos</span>
                        </div>
                        <p class="text-[11px] text-slate-500 leading-snug">Documento tributario extendido a nombre del contratante por la diferencia pagada.</p>

                        <div class="grid grid-cols-2 gap-2">
                            <label class="block">
                                <span class="text-[11px] font-semibold text-slate-700">Folio Boleta Electrónica:</span>
                                <input type="text" name="folio_boleta" id="modal-input-folio-boleta" class="field mt-1 w-full text-xs font-mono" placeholder="Ej: BE-00890">
                            </label>
                            <label class="block">
                                <span class="text-[11px] font-semibold text-slate-700">Estado Pago Copago:</span>
                                <select name="estado_copago" id="modal-select-estado-copago" class="field mt-1 w-full text-xs">
                                    @foreach(\App\Models\Liquidacion::ESTADOS_COPAGO as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </label>
                        </div>

                        <div class="grid grid-cols-2 gap-2">
                            <label class="block">
                                <span class="text-[11px] font-semibold text-slate-700">Monto Abonado / Pagado ($):</span>
                                <input type="number" name="monto_copago_pagado" id="modal-input-copago-pagado" class="field mt-1 w-full text-xs" min="0">
                            </label>
                            <label class="block">
                                <span class="text-[11px] font-semibold text-slate-700">Medio de Pago:</span>
                                <select name="metodo_pago_familia" id="modal-select-metodo-pago" class="field mt-1 w-full text-xs">
                                    <option value="">Seleccionar método...</option>
                                    @foreach(\App\Models\Liquidacion::METODOS_PAGO as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </label>
                        </div>

                        <div class="pt-2">
                            <a id="modal-link-pdf-boleta" href="#" target="_blank" class="w-full inline-flex items-center justify-center px-3 py-2 rounded-lg bg-emerald-700 hover:bg-emerald-800 text-white font-bold text-xs shadow-sm transition">
                                <i class="fa-solid fa-file-pdf mr-1.5 text-rose-300"></i>Previsualizar Boleta de Copago (PDF)
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Observaciones -->
                <label class="block">
                    <span class="text-[11px] font-semibold text-slate-700">Observaciones contables o número de transferencia:</span>
                    <textarea name="observaciones" id="modal-input-observaciones" rows="2" class="field mt-1 w-full text-xs" placeholder="Detalles de la liquidación, depósito bancario o trámite especial..."></textarea>
                </label>

                <!-- Pie de Modal con Acciones -->
                <div class="border-t border-slate-100 pt-4 flex flex-col sm:flex-row items-center justify-between gap-3">
                    <a id="modal-link-pdf-general" href="#" target="_blank" class="text-slate-600 hover:text-indigo-900 font-bold text-xs flex items-center">
                        <i class="fa-solid fa-file-lines text-slate-500 mr-1.5"></i>Descargar Comprobante Consolidado (Interno)
                    </a>
                    <div class="flex items-center space-x-2 w-full sm:w-auto justify-end">
                        <button type="button" onclick="cerrarModalLiquidacion()" class="px-4 py-2 rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50 font-semibold text-xs transition">
                            Cerrar
                        </button>
                        <button type="submit" class="px-5 py-2 rounded-lg bg-indigo-950 hover:bg-indigo-900 text-white font-bold text-xs shadow transition flex items-center">
                            <i class="fa-solid fa-floppy-disk mr-1.5"></i>Guardar Cambios y Folios
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

@endsection

@push('scripts')
<script>
    function formatPesos(num) {
        return '$' + Number(num || 0).toLocaleString('es-CL');
    }

    function abrirModalLiquidacion(data) {
        document.getElementById('form-liquidacion').action = data.urlUpdate;
        document.getElementById('modal-difunto').textContent = data.difunto;
        document.getElementById('modal-folio-badge').textContent = 'Expediente #' + data.id + ' (' + data.folio + ')';

        document.getElementById('modal-monto-total').textContent = formatPesos(data.totalServicio);
        document.getElementById('modal-monto-subvencion').textContent = formatPesos(data.montoSubvencion);
        document.getElementById('modal-monto-copago').textContent = formatPesos(data.montoCopagoFamilia);

        document.getElementById('modal-input-folio-factura').value = data.folioFactura || '';
        document.getElementById('modal-input-folio-boleta').value = data.folioBoleta || '';
        document.getElementById('modal-select-estado-subvencion').value = data.estadoSubvencion || 'tramitacion';
        document.getElementById('modal-select-estado-copago').value = data.estadoCopago || 'pendiente';
        document.getElementById('modal-input-copago-pagado').value = data.montoCopagoPagado || 0;
        document.getElementById('modal-select-metodo-pago').value = data.metodoPago || '';
        document.getElementById('modal-input-observaciones').value = data.observaciones || '';

        document.getElementById('modal-link-pdf-factura').href = data.urlPdfFactura;
        document.getElementById('modal-link-pdf-boleta').href = data.urlPdfBoleta;
        document.getElementById('modal-link-pdf-general').href = data.urlPdfGeneral;

        const modal = document.getElementById('modal-comprobante');
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function cerrarModalLiquidacion() {
        const modal = document.getElementById('modal-comprobante');
        modal.classList.add('hidden');
        document.body.style.overflow = '';
    }

    // Cerrar modal al presionar Escape o click fuera
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') cerrarModalLiquidacion();
    });
    document.getElementById('modal-comprobante').addEventListener('click', function(e) {
        if (e.target === this) cerrarModalLiquidacion();
    });

    // Reloj reactivo local de Santiago de Chile en el frontend
    function actualizarRelojSantiago() {
        try {
            const ahora = new Date();
            const opciones = { timeZone: 'America/Santiago', hour: '2-digit', minute: '2-digit', hour12: false };
            const horaFormateada = new Intl.DateTimeFormat('es-CL', opciones).format(ahora);
            const liveEls = document.querySelectorAll('#live-time');
            liveEls.forEach(el => el.textContent = horaFormateada);
        } catch (e) {
            console.error('Error calculando hora en Santiago:', e);
        }
    }
    setInterval(actualizarRelojSantiago, 30000);
</script>
@endpush

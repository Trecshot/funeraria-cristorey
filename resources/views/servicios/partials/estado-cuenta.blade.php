<!-- ESTADO DE CUENTA Y COPAGO (HITO 5: CIERRE FINANCIERO) -->
<section id="seccion-estado-cuenta" class="panel transition-all duration-300 space-y-4">
    @php
        $liquidacion = $servicio->obtenerOCrearLiquidacion();
        $cuotas = $liquidacion->cuotas;
    @endphp
    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
        <div class="flex items-center gap-2">
            <span class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center font-bold">
                <i class="fa-solid fa-coins"></i>
            </span>
            <div>
                <h2 class="font-serif-title text-base font-bold text-slate-900">Estado de Cuenta y Copago</h2>
                <p class="text-[11px] text-slate-400">Control de aportes previsionales y cuotas familiares</p>
            </div>
        </div>
        <span class="px-2.5 py-1 rounded-full text-xs font-bold border {{ $liquidacion->badgeEstadoGeneralClass() }}">
            {{ $liquidacion->estadoGeneralLabel() }} <span class="font-normal text-[10px]">{{ $liquidacion->resumenMorosidad() }}</span>
        </span>
    </div>

    <!-- DESGLOSE ECONÓMICO -->
    <div class="grid grid-cols-3 gap-2 bg-slate-50/80 p-3 rounded-xl border border-slate-100 text-center">
        <div>
            <span class="text-[10px] text-slate-400 uppercase tracking-wider block font-semibold">Costo Total</span>
            <strong class="text-xs sm:text-sm font-bold text-slate-900 block mt-0.5">${{ number_format($liquidacion->monto_total, 0, ',', '.') }} CLP</strong>
        </div>
        <div>
            <span class="text-[10px] text-slate-400 uppercase tracking-wider block font-semibold truncate" title="Aporte Institucional ({{ strtoupper($servicio->convenio) }})">
                Aporte ({{ strtoupper($servicio->convenio) }})
            </span>
            <strong class="text-xs sm:text-sm font-bold text-emerald-700 block mt-0.5">-${{ number_format($liquidacion->monto_subvencion, 0, ',', '.') }} CLP</strong>
        </div>
        <div>
            <span class="text-[10px] text-slate-400 uppercase tracking-wider block font-semibold">Copago Familia</span>
            <strong class="text-xs sm:text-sm font-bold text-indigo-950 block mt-0.5">${{ number_format($liquidacion->monto_copago_familia, 0, ',', '.') }} CLP</strong>
        </div>
    </div>

    <div class="flex items-center justify-between text-xs text-slate-600 px-1">
        <span class="font-medium">
            <i class="fa-solid fa-calendar-days text-slate-400 mr-1"></i>Plan de Pago: <strong>{{ $cuotas->count() ?: 1 }} Cuotas</strong>
        </span>
        <span class="text-[11px]">
            Saldo pendiente: <strong class="text-rose-600 font-bold">${{ number_format($liquidacion->saldoCopagoPendiente(), 0, ',', '.') }} CLP</strong>
        </span>
    </div>

    <!-- HISTORIAL DE CUOTAS -->
    <div class="border-t border-slate-100 pt-3">
        <div class="flex items-center justify-between mb-2">
            <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Historial de Cuotas</h4>
            @if($liquidacion->saldoCopagoPendiente() > 0)
                <button type="button" onclick="abrirModalPagoRapido()" class="text-[11px] font-bold text-indigo-600 hover:text-indigo-800 inline-flex items-center">
                    <i class="fa-solid fa-credit-card mr-1"></i>Registrar Pago
                </button>
            @endif
        </div>

        @if($cuotas->isEmpty())
            <div class="text-center py-3 text-xs text-slate-400 italic">
                No hay plan de cuotas registrado para este expediente.
            </div>
        @else
            <div class="space-y-2">
                @foreach($cuotas as $cuota)
                    <div class="p-2.5 rounded-xl border flex items-center justify-between gap-2 {{ $cuota->estaAtrasada() ? 'border-rose-200 bg-rose-50/40' : ($cuota->estado === 'pagada' ? 'border-emerald-200 bg-emerald-50/20' : 'border-slate-100 bg-white') }}">
                        <div class="min-w-0">
                            <div class="flex items-center gap-1.5">
                                <span class="text-xs font-bold text-slate-900">Cuota {{ $cuota->numero_cuota }}</span>
                                <span class="text-[11px] text-slate-500 font-mono">
                                    @if($cuota->estado === 'pagada')
                                        (Pagada: {{ $cuota->fecha_pago?->format('d/m/Y') }})
                                    @elseif($cuota->estaAtrasada())
                                        <span class="text-rose-600 font-semibold">(Venció: {{ $cuota->fecha_vencimiento->format('d/m/Y') }})</span>
                                    @else
                                        (Vence: {{ $cuota->fecha_vencimiento->format('d/m/Y') }})
                                    @endif
                                </span>
                            </div>
                            <div class="text-[11px] text-slate-600 font-bold mt-0.5">
                                {{ $cuota->montoFormateado() }}
                                @if($cuota->metodo_pago)
                                    <span class="text-[10px] text-slate-400 font-normal">· {{ $cuota->metodo_pago }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="flex items-center gap-1.5 flex-shrink-0">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $cuota->badgeClass() }}">
                                {{ $cuota->estadoLabel() }}
                            </span>
                            @if($cuota->estado !== 'pagada')
                                <button type="button" onclick="pagarCuotaDirecto({{ $cuota->id }}, {{ $cuota->numero_cuota }}, {{ $cuota->monto_cuota }}, '{{ route('servicios.cuotas.pagar', [$servicio, $cuota]) }}')" class="px-2 py-1 text-[10px] font-bold text-indigo-700 bg-indigo-50 hover:bg-indigo-100 rounded-lg transition" title="Registrar pago de esta cuota">
                                    Pagar
                                </button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <div class="pt-2 border-t border-slate-100 flex flex-wrap items-center justify-between gap-2">
        @if($liquidacion->saldoCopagoPendiente() > 0)
            <button type="button" onclick="abrirModalPagoRapido()" class="button-primary text-xs py-1.5 px-3">
                <i class="fa-solid fa-receipt mr-1.5"></i>Registrar Pago
            </button>
        @else
            <span class="text-xs text-emerald-700 font-bold flex items-center">
                <i class="fa-solid fa-circle-check mr-1.5"></i>Copago familiar saldado en su totalidad
            </span>
        @endif
        <a href="{{ route('finanzas.index') }}" class="button-secondary text-xs py-1.5 px-3">
            Ver en Módulo de Finanzas &rarr;
        </a>
    </div>
</section>

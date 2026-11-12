@extends('layouts.app')

@section('title', 'Expediente '.$servicio->id.' · '.$servicio->difunto)
@section('heading', 'Expediente #'.$servicio->id)

@section('content')
    <div class="space-y-6">

        @if(session('status'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs text-emerald-800 flex items-center justify-between shadow-xs">
                <div class="flex items-center space-x-2">
                    <i class="fa-solid fa-circle-check text-emerald-600 text-sm"></i>
                    <span>{{ session('status') }}</span>
                </div>
            </div>
        @endif

        <!-- ENCABEZADO PRINCIPAL -->
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 flex flex-wrap justify-between items-center gap-4">
            <div>
                <div class="flex items-center space-x-2">
                    <span class="bg-indigo-100 text-indigo-900 text-[10px] font-bold px-2.5 py-0.5 rounded-full uppercase tracking-wider">{{ $servicio->folio }}</span>
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold {{ $servicio->estadoBadgeClass() }}">
                        {{ $servicio->estadoLabel() }}
                    </span>
                </div>
                <h2 class="font-serif-title mt-1.5 text-2xl font-bold text-slate-900">{{ $servicio->difunto }}</h2>
                <p class="text-xs text-slate-500 mt-0.5">Expediente creado el {{ $servicio->created_at->format('d/m/Y \a \l\a\s H:i') }} hrs · Sede Calle Blanco</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a class="button-secondary" href="{{ route('servicios.index') }}">
                    <i class="fa-solid fa-arrow-left mr-1.5"></i>Volver a servicios
                </a>
                @if(auth()->user()->canAccessFinances())
                    <a class="button-secondary" href="{{ route('finanzas.pdf', $servicio) }}">
                        <i class="fa-solid fa-file-pdf text-rose-600 mr-1.5"></i>Comprobante PDF
                    </a>
                @endif
                <a class="button-primary" href="{{ route('servicios.edit', $servicio) }}">
                    <i class="fa-solid fa-pen-to-square mr-1.5"></i>Editar expediente
                </a>
            </div>
        </div>

        @include('servicios.partials.stepper')

        <!-- PANELES DE DETALLE OPERATIVO: LOGÍSTICA DE URNA Y CORTEJO -->
        <div class="grid gap-6 lg:grid-cols-2">
            @include('servicios.partials.logistica-urna')
            @include('servicios.partials.cortejo-flota')
        </div>

        <!-- PANELES DE ANTECEDENTES, ESTADO DE CUENTA Y DOCUMENTACIÓN LEGAL -->
        <div class="grid gap-6 lg:grid-cols-2">
            <!-- COLUMNA IZQUIERDA: ANTECEDENTES Y ESTADO DE CUENTA (COPAGO) -->
            <div class="space-y-6">
                <!-- ANTECEDENTES DEL DIFUNTO Y CONTRATANTE (HITO 1) -->
                <section id="seccion-contratante" class="panel transition-all duration-300">
                    <h2 class="font-serif-title text-base font-bold text-slate-900"><i class="fa-solid fa-clipboard-list text-indigo-700 mr-1.5"></i>Antecedentes del Contratante y Fallecido</h2>
                    <dl class="mt-4 grid grid-cols-2 gap-4 text-xs">
                        <div><dt class="text-slate-400">Contratante</dt><dd class="mt-1 font-bold text-slate-900">{{ $servicio->cliente_contratante }}</dd></div>
                        <div><dt class="text-slate-400">Teléfono</dt><dd class="mt-1 font-semibold">{{ $servicio->telefono_cliente ?: 'No registrado' }}</dd></div>
                        <div><dt class="text-slate-400">Convenio</dt><dd class="mt-1"><span class="bg-indigo-50 text-indigo-900 px-2 py-0.5 rounded font-bold">{{ ucfirst($servicio->convenio) }}</span></dd></div>
                        <div><dt class="text-slate-400">Total del servicio</dt><dd class="mt-1 font-bold text-slate-900">${{ number_format((float) $servicio->total_servicio, 0, ',', '.') }} CLP</dd></div>
                        <div><dt class="text-slate-400">RUT Difunto</dt><dd class="mt-1 font-semibold">{{ $servicio->difunto_rut ?: 'No registrado' }}</dd></div>
                        <div><dt class="text-slate-400">Escolaridad</dt><dd class="mt-1">{{ $servicio->escolaridad ?: 'No registrada' }}</dd></div>
                        <div><dt class="text-slate-400">Oficio</dt><dd class="mt-1">{{ $servicio->oficio ?: 'No registrado' }}</dd></div>
                        <div class="col-span-2"><dt class="text-slate-400">Causa SML / Fiscalía</dt><dd class="mt-1">{{ $servicio->causa_sml ?: 'Fallecimiento por causa natural (sin intervención SML)' }}</dd></div>
                    </dl>
                </section>

                @include('servicios.partials.estado-cuenta')
            </div>

            @include('servicios.partials.documentos-legales')
        </div>

        @include('servicios.partials.auditoria-historial')

        <!-- ELIMINAR EXPEDIENTE -->
        <div id="seccion-cierre" class="flex justify-end">
            <form method="POST" action="{{ route('servicios.destroy', $servicio) }}" onsubmit="return confirm('¿Eliminar este expediente?');">
                @csrf @method('DELETE')
                <button class="text-xs font-bold text-rose-700 hover:underline">
                    <i class="fa-solid fa-trash-can mr-1"></i>Eliminar expediente
                </button>
            </form>
        </div>
    </div>

    @include('servicios.partials.modales-show')

@endsection

@push('scripts')
    @include('servicios.partials.scripts-show')
@endpush

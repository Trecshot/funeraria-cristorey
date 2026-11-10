@extends('layouts.app')

@section('title', 'Expediente '.$servicio->id)
@section('heading', 'Expediente #'.$servicio->id)

@section('content')
    <div class="space-y-6">
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 flex flex-wrap justify-between items-center gap-4">
            <div>
                <span class="bg-indigo-100 text-indigo-900 text-[10px] font-bold px-2.5 py-0.5 rounded-full uppercase">{{ $servicio->folio }}</span>
                <h2 class="font-serif-title mt-1 text-2xl font-bold text-slate-900">{{ $servicio->difunto }}</h2>
            </div>
            <div class="flex flex-wrap gap-2">
                @if(auth()->user()->canAccessFinances())
                    <a class="button-secondary" href="{{ route('finanzas.pdf', $servicio) }}">
                        <i class="fa-solid fa-file-pdf text-rose-600 mr-1.5"></i>Emitir Comprobante PDF
                    </a>
                @endif
                <a class="button-primary" href="{{ route('servicios.edit', $servicio) }}">
                    <i class="fa-solid fa-pen-to-square mr-1.5"></i>Editar expediente
                </a>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <section class="panel">
                <h2 class="font-serif-title text-lg font-bold text-slate-900"><i class="fa-solid fa-clipboard-list text-indigo-700 mr-1.5"></i>Contratante y Servicio</h2>
                <dl class="mt-4 grid grid-cols-2 gap-4 text-xs">
                    <div><dt class="text-slate-400">Contratante</dt><dd class="mt-1 font-bold text-slate-900">{{ $servicio->cliente_contratante }}</dd></div>
                    <div><dt class="text-slate-400">Teléfono</dt><dd class="mt-1 font-semibold">{{ $servicio->telefono_cliente ?: 'No registrado' }}</dd></div>
                    <div><dt class="text-slate-400">Convenio</dt><dd class="mt-1"><span class="bg-indigo-50 text-indigo-900 px-2 py-0.5 rounded font-bold">{{ ucfirst($servicio->convenio) }}</span></dd></div>
                    <div><dt class="text-slate-400">Estado</dt><dd class="mt-1 font-semibold">{{ $servicio->estadoLabel() }}</dd></div>
                    <div><dt class="text-slate-400">Total del servicio</dt><dd class="mt-1 font-bold text-slate-900">${{ number_format((float) $servicio->total_servicio, 0, ',', '.') }} CLP</dd></div>
                    <div><dt class="text-slate-400">Cortejo</dt><dd class="mt-1 font-semibold">{{ $servicio->cortejo_at?->format('d/m/Y H:i') ?? 'Sin programar' }}</dd></div>
                    <div class="col-span-2"><dt class="text-slate-400">Destino / Cementerio</dt><dd class="mt-1 font-semibold">{{ $servicio->destino ?: 'Sin registrar' }}</dd></div>
                    <div><dt class="text-slate-400">Escolaridad</dt><dd class="mt-1">{{ $servicio->escolaridad ?: 'No registrada' }}</dd></div>
                    <div><dt class="text-slate-400">Oficio</dt><dd class="mt-1">{{ $servicio->oficio ?: 'No registrado' }}</dd></div>
                    <div class="col-span-2"><dt class="text-slate-400">Causa SML</dt><dd class="mt-1">{{ $servicio->causa_sml ?: 'No registrada' }}</dd></div>
                </dl>
            </section>

            <section class="panel">
                <h2 class="font-serif-title text-lg font-bold text-slate-900"><i class="fa-solid fa-cloud-arrow-down text-indigo-700 mr-1.5"></i>Documentos de Respaldo (Amazon S3)</h2>
                @if($servicio->documentos->isEmpty())
                    <p class="mt-4 text-xs text-slate-500">No se han cargado documentos todavía.</p>
                @else
                    <ul class="mt-4 divide-y divide-slate-100">
                        @foreach($servicio->documentos as $documento)
                            <li class="flex flex-wrap items-center justify-between gap-2 py-3 text-xs">
                                <span>
                                    <i class="fa-solid fa-file-shield text-indigo-700 mr-1.5"></i>
                                    <strong class="text-slate-800">{{ $documento->nombre_original }}</strong>
                                    <span class="text-slate-400">· {{ $documento->tipoLabel() }}</span>
                                </span>
                                <a class="text-indigo-700 font-bold hover:underline" href="{{ route('servicios.documentos.descargar', [$servicio, $documento]) }}">
                                    <i class="fa-solid fa-download mr-1"></i>Descargar
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </div>

        <form method="POST" action="{{ route('servicios.destroy', $servicio) }}" onsubmit="return confirm('¿Eliminar este expediente?');" class="text-right">
            @csrf @method('DELETE')
            <button class="text-xs font-bold text-rose-700 hover:underline">
                <i class="fa-solid fa-trash-can mr-1"></i>Eliminar expediente
            </button>
        </form>
    </div>
@endsection

@extends('layouts.app')

@section('title', 'Bodega')
@section('heading', 'Bodega e inventario')

@section('content')
    @if($productos->contains(fn ($producto) => $producto->stock_critico))
        <div class="rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-950" role="alert">
            <strong>Alerta de stock crítico:</strong> hay modelos con existencias iguales o inferiores al mínimo.
        </div>
    @endif

    <section class="panel overflow-hidden p-0">
        <div class="border-b border-stone-100 px-5 py-4">
            <h2 class="text-lg">Urnas · Salón (Calle Blanco) y Taller</h2>
            <p class="mt-1 text-sm text-stone-500">Los avisos se activan al alcanzar el stock mínimo configurado.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-stone-200">
                <thead class="bg-stone-50 text-left text-xs uppercase tracking-wide text-stone-500"><tr><th class="px-4 py-3">Modelo</th><th class="px-4 py-3">Color</th><th class="px-4 py-3">Tallado</th><th class="px-4 py-3">Salón · Calle Blanco</th><th class="px-4 py-3">Taller</th><th class="px-4 py-3">Total</th><th class="px-4 py-3">Mínimo</th><th class="px-4 py-3">Estado</th></tr></thead>
                <tbody class="divide-y divide-stone-100">
                    @forelse($productos as $producto)
                        <tr>
                            <td class="table-cell font-medium">{{ $producto->modelo }}</td><td class="table-cell">{{ $producto->color ?: '—' }}</td><td class="table-cell">{{ $producto->tallado ?: '—' }}</td>
                            <td class="table-cell">{{ $producto->stock_salon }}</td><td class="table-cell">{{ $producto->stock_taller }}</td><td class="table-cell font-medium">{{ $producto->stock_total }}</td><td class="table-cell">{{ $producto->stock_minimo }}</td>
                            <td class="table-cell"><span class="rounded-full px-2 py-1 text-xs {{ $producto->stock_critico ? 'bg-amber-100 text-amber-900' : 'bg-emerald-100 text-emerald-900' }}">{{ $producto->stock_critico ? 'Stock crítico' : 'En rango' }}</span></td>
                        </tr>
                    @empty
                        <tr><td class="px-4 py-8 text-center text-sm text-stone-500" colspan="8">No hay productos registrados. Agrega el inventario inicial abajo.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div class="grid gap-5 xl:grid-cols-2">
        <section class="panel">
            <h2 class="text-lg">Agregar producto al inventario</h2>
            <form method="POST" action="{{ route('bodega.productos.store') }}" class="mt-4 grid gap-3 sm:grid-cols-2">
                @csrf
                <label><span class="label">Modelo</span><input class="field" name="modelo" placeholder="Marquetina, lisa, paloma-biblia" required></label>
                <label><span class="label">Color</span><input class="field" name="color"></label>
                <label><span class="label">Tallado</span><input class="field" name="tallado"></label>
                <label><span class="label">Ubicación</span><select class="field" name="ubicacion"><option value="salon">Salón · Calle Blanco</option><option value="taller">Taller</option></select></label>
                <label><span class="label">Existencias</span><input class="field" type="number" min="0" name="stock" value="0" required></label>
                <label><span class="label">Mínimo para alerta</span><input class="field" type="number" min="0" name="stock_minimo" value="1" required></label>
                <div class="sm:col-span-2"><button class="button-primary">Agregar a bodega</button></div>
            </form>
        </section>

        <section class="panel">
            <h2 class="text-lg">Traspaso entre ubicaciones</h2>
            <form method="POST" action="{{ route('bodega.traspaso') }}" class="mt-4 space-y-3">
                @csrf
                <label class="block"><span class="label">Producto</span><select class="field" name="producto_id" required>
                    @foreach($productos as $producto)<option value="{{ $producto->id }}">{{ $producto->modelo }} · {{ $producto->color ?: 'sin color' }} · Salón {{ $producto->stock_salon }} / Taller {{ $producto->stock_taller }}</option>@endforeach
                </select></label>
                <div class="grid grid-cols-2 gap-3">
                    <label><span class="label">Desde</span><select class="field" name="origen" id="traspaso-origen"><option value="salon">Salón</option><option value="taller">Taller</option></select></label>
                    <label><span class="label">Hacia</span><select class="field" name="destino" id="traspaso-destino"><option value="taller">Taller</option><option value="salon">Salón</option></select></label>
                </div>
                <label class="block"><span class="label">Cantidad</span><input class="field" type="number" min="1" name="cantidad" required></label>
                <button class="button-primary">Registrar traspaso</button>
            </form>
        </section>
    </div>

    <section class="panel">
        <h2 class="text-lg">Movimientos recientes</h2>
        <div class="mt-3 divide-y divide-stone-100">
            @forelse($movimientos as $movimiento)
                <p class="py-2 text-sm text-stone-700">{{ $movimiento->created_at->format('d/m/Y H:i') }} · {{ $movimiento->cantidad }} × {{ $movimiento->producto->modelo }} · {{ ucfirst($movimiento->origen) }} → {{ ucfirst($movimiento->destino) }} · {{ $movimiento->user->name }}</p>
            @empty
                <p class="py-2 text-sm text-stone-500">Sin traspasos registrados.</p>
            @endforelse
        </div>
    </section>
@endsection

@push('scripts')
<script>
    (() => {
        const origen = document.getElementById('traspaso-origen');
        const destino = document.getElementById('traspaso-destino');
        if (!origen || !destino) return;

        function actualizarUbicaciones() {
            destino.value = origen.value === 'salon' ? 'taller' : 'salon';
        }

        origen.addEventListener('change', actualizarUbicaciones);
        actualizarUbicaciones();
    })();
</script>
@endpush
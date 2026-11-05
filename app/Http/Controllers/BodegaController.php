<?php

namespace App\Http\Controllers;

use App\Models\MovimientoBodega;
use App\Models\ProductoBodega;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BodegaController extends Controller
{
    public function crearProducto(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'modelo' => ['required', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:100'],
            'tallado' => ['nullable', 'string', 'max:255'],
            'ubicacion' => ['required', 'in:salon,taller'],
            'stock' => ['required', 'integer', 'min:0'],
            'stock_minimo' => ['required', 'integer', 'min:0'],
        ]);

        DB::transaction(function () use ($data) {
            $producto = ProductoBodega::where('modelo', $data['modelo'])
                ->where('color', $data['color'] ?? null)
                ->where('tallado', $data['tallado'] ?? null)
                ->lockForUpdate()
                ->first();

            if (! $producto) {
                $producto = new ProductoBodega([
                    'modelo' => $data['modelo'],
                    'color' => $data['color'] ?? null,
                    'tallado' => $data['tallado'] ?? null,
                    'stock_minimo' => $data['stock_minimo'],
                ]);
            }

            $stockColumn = $producto->stockColumn($data['ubicacion']);
            $nuevoStock = (int) $producto->getAttribute($stockColumn) + $data['stock'];
            $producto->setAttribute($stockColumn, $nuevoStock);
            $producto->stock_minimo = $data['stock_minimo'];
            if (Schema::hasColumn('productos_bodega', 'ubicacion')) {
                $producto->ubicacion = $data['ubicacion'];
                $producto->stock = $nuevoStock;
            }
            $producto->save();
        });

        return back()->with('status', 'Producto incorporado al inventario.');
    }

    public function index(): View
    {
        return view('bodega.index', [
            'productos' => ProductoBodega::orderBy('modelo')->get(),
            'movimientos' => MovimientoBodega::with(['producto', 'user'])
                ->latest()->limit(8)->get(),
        ]);
    }

    public function traspaso(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'producto_id' => ['required', 'integer', 'exists:productos_bodega,id'],
            'origen' => ['required', 'in:salon,taller'],
            'destino' => ['required', 'in:salon,taller', 'different:origen'],
            'cantidad' => ['required', 'integer', 'min:1'],
        ]);

        DB::transaction(function () use ($data, $request) {
            $producto = ProductoBodega::whereKey($data['producto_id'])->lockForUpdate()->firstOrFail();

            $origenColumn = $producto->stockColumn($data['origen']);
            $destinoColumn = $producto->stockColumn($data['destino']);
            if ($producto->{$origenColumn} < $data['cantidad']) {
                throw ValidationException::withMessages([
                    'cantidad' => 'No hay existencias suficientes en la ubicacion de origen.',
                ]);
            }

            $producto->decrement($origenColumn, $data['cantidad']);
            $producto->increment($destinoColumn, $data['cantidad']);
            if (Schema::hasColumn('productos_bodega', 'ubicacion')) {
                $producto->ubicacion = $data['destino'];
                $producto->stock = $producto->{$destinoColumn};
                $producto->save();
            }

            MovimientoBodega::create([
                'producto_bodega_id' => $producto->id,
                'user_id' => $request->user()->id,
                'origen' => $data['origen'],
                'destino' => $data['destino'],
                'cantidad' => $data['cantidad'],
            ]);
        });

        return back()->with('status', 'Traspaso registrado correctamente.');
    }
}

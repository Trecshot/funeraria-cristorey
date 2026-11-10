<?php

namespace App\Http\Controllers;

use App\Models\MovimientoBodega;
use App\Models\Urna;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BodegaController extends Controller
{
    public function index(): View
    {
        $urnas = Urna::orderBy('modelo')->get();

        return view('bodega.index', [
            'urnas' => $urnas,
            'productos' => $urnas, // compatibilidad con vistas existentes
            'movimientos' => MovimientoBodega::with(['producto', 'user'])
                ->latest()
                ->limit(8)
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        return $this->crearProducto($request);
    }

    public function crearProducto(Request $request): RedirectResponse
    {
        // Generar código automático si no se ingresa manualmente
        if (! $request->filled('codigo')) {
            $prefijo = match ($request->input('categoria_etaria')) {
                'Niño' => 'URN-NN',
                'Juvenil' => 'URN-JV',
                'Especial' => 'URN-XL',
                default => 'URN-AD',
            };
            $siguienteNumero = Urna::count() + 1;
            $request->merge([
                'codigo' => sprintf('%s-%02d', $prefijo, $siguienteNumero),
            ]);
        }

        // Si se envió el formulario previo con ubicación única y existencias
        if (! $request->has('stock_salon') && ! $request->has('stock_taller')) {
            $ubicacion = $request->input('ubicacion', 'salon');
            $stock = (int) $request->input('stock', 0);
            $request->merge([
                'stock_salon' => $ubicacion === 'salon' ? $stock : 0,
                'stock_taller' => $ubicacion === 'taller' ? $stock : 0,
            ]);
        }

        $validated = $request->validate([
            'codigo' => ['required', 'string', 'max:50', 'unique:urnas,codigo'],
            'modelo' => ['required', 'string', 'max:100'],
            'color' => ['required', 'string', 'max:50'],
            'tallado' => ['nullable', 'string', 'max:100'],
            'categoria_etaria' => ['required', Rule::in(Urna::CATEGORIAS_ETARIAS)],
            'medidas_cm' => ['required', 'string', 'max:50'],
            'personalizada' => ['nullable', 'boolean'],
            'precio' => ['required', 'numeric', 'min:0'],
            'stock_salon' => ['required', 'integer', 'min:0'],
            'stock_taller' => ['required', 'integer', 'min:0'],
            'stock_minimo' => ['required', 'integer', 'min:0'],
            'mostrar_catalogo' => ['nullable', 'boolean'],
        ]);

        $validated['personalizada'] = $request->boolean('personalizada');
        $validated['mostrar_catalogo'] = $request->has('mostrar_catalogo')
            ? $request->boolean('mostrar_catalogo')
            : true;
        $validated['publicado'] = $validated['mostrar_catalogo'];

        Urna::create($validated);

        return redirect()->route('bodega.index')->with('status', 'Urna registrada exitosamente en el inventario dual.');
    }

    public function traspaso(Request $request): RedirectResponse
    {
        $idKey = $request->has('urna_id') ? 'urna_id' : 'producto_id';

        $data = $request->validate([
            $idKey => ['required', 'integer', 'exists:urnas,id'],
            'origen' => ['required', Rule::in(Urna::UBICACIONES)],
            'destino' => ['required', Rule::in(Urna::UBICACIONES), 'different:origen'],
            'cantidad' => ['required', 'integer', 'min:1'],
        ]);

        $urnaId = $data[$idKey];

        DB::transaction(function () use ($data, $urnaId, $request): void {
            $urna = Urna::whereKey($urnaId)->lockForUpdate()->firstOrFail();

            $origenColumn = $urna->stockColumn($data['origen']);
            $destinoColumn = $urna->stockColumn($data['destino']);
            if ($urna->{$origenColumn} < $data['cantidad']) {
                throw ValidationException::withMessages([
                    'cantidad' => 'No hay existencias suficientes en la ubicación de origen.',
                ]);
            }

            $urna->decrement($origenColumn, $data['cantidad']);
            $urna->increment($destinoColumn, $data['cantidad']);

            MovimientoBodega::create([
                'producto_bodega_id' => $urna->id,
                'urna_id' => $urna->id,
                'user_id' => $request->user()->id,
                'origen' => $data['origen'],
                'destino' => $data['destino'],
                'cantidad' => $data['cantidad'],
            ]);
        });

        return back()->with('status', 'Traspaso registrado correctamente entre Salón y Taller.');
    }
}

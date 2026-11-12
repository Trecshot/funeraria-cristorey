<?php

namespace App\Http\Controllers;

use App\Http\Requests\Bodega\StoreUrnaRequest;
use App\Http\Requests\Bodega\TraspasoBodegaRequest;
use App\Models\MovimientoBodega;
use App\Models\Urna;
use App\Services\BodegaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class BodegaController extends Controller
{
    public function __construct(
        protected BodegaService $bodegaService
    ) {}

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

    public function store(StoreUrnaRequest $request): RedirectResponse
    {
        return $this->crearProducto($request);
    }

    public function crearProducto(StoreUrnaRequest $request): RedirectResponse
    {
        $this->bodegaService->crearUrna($request->normalizedPayload());

        return redirect()->route('bodega.index')->with('status', 'Urna registrada exitosamente en el inventario dual.');
    }

    public function traspaso(TraspasoBodegaRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $this->bodegaService->registrarTraspaso(
            $request->urnaId(),
            $data['origen'],
            $data['destino'],
            (int) $data['cantidad'],
            $request->user()->id
        );

        return back()->with('status', 'Traspaso registrado correctamente entre Salón y Taller.');
    }
}

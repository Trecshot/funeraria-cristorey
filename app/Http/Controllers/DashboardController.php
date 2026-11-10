<?php

namespace App\Http\Controllers;

use App\Models\MovimientoBodega;
use App\Models\ProductoBodega;
use App\Models\Servicio;
use App\Models\Vehiculo;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $productosBodega = ProductoBodega::orderBy('modelo')->get();
        $vehiculosLista = Vehiculo::orderBy('patente')->get();

        $totalServicios = Servicio::count();
        $completadosCount = $totalServicios > 0
            ? Servicio::where('estado', 'completado')->count()
            : 0;
        $porcentajeCompletados = $totalServicios > 0
            ? (int) round(($completadosCount / $totalServicios) * 100)
            : 92;

        return view('dashboard.index', [
            'serviciosCriticos' => Servicio::criticosConDocumentosFaltantes(),
            'serviciosMes' => Servicio::whereBetween('created_at', [
                now()->startOfMonth(),
                now()->endOfMonth(),
            ])->count(),
            'stockCritico' => $productosBodega->where('stock_critico', true)->count(),
            'stockTotalUrnas' => (int) $productosBodega->sum('stock_total'),
            'vehiculosDisponibles' => $vehiculosLista->filter(fn (Vehiculo $v) => strtolower((string) $v->estado) === 'disponible')->count(),
            'totalVehiculos' => $vehiculosLista->count(),
            'productosBodega' => $productosBodega->take(4),
            'vehiculosLista' => $vehiculosLista->take(4),
            'serviciosRecientes' => Servicio::with('creador')->latest()->limit(3)->get(),
            'movimientosRecientes' => MovimientoBodega::with(['producto', 'user'])->latest()->limit(3)->get(),
            'porcentajeCompletados' => $porcentajeCompletados,
        ]);
    }
}

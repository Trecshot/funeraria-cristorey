<?php

namespace App\Http\Controllers;

use App\Models\ProductoBodega;
use App\Models\Servicio;
use App\Models\Vehiculo;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $serviciosCriticos = Servicio::query()
            ->whereBetween('cortejo_at', [now(), now()->addHours(3)])
            ->with('documentos:id,servicio_id,tipo')
            ->orderBy('cortejo_at')
            ->get()
            ->map(function (Servicio $servicio) {
                $documentosPresentes = $servicio->documentos->pluck('tipo')->all();
                $documentosRequeridos = ['resolucion_seremi'];
                if (filled($servicio->causa_sml)) {
                    $documentosRequeridos[] = 'oficio_fiscalia_sml';
                }

                $servicio->documentosFaltantes = array_values(array_diff($documentosRequeridos, $documentosPresentes));

                return $servicio;
            })
            ->filter(fn (Servicio $servicio) => $servicio->documentosFaltantes !== [])
            ->values();

        return view('dashboard.index', [
            'serviciosCriticos' => $serviciosCriticos,
            'serviciosMes' => Servicio::whereBetween('created_at', [
                Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth(),
            ])->count(),
            'stockCritico' => ProductoBodega::whereRaw('(stock_salon + stock_taller) <= stock_minimo')->count(),
            'vehiculosDisponibles' => Vehiculo::where('estado', 'disponible')->count(),
        ]);
    }
}

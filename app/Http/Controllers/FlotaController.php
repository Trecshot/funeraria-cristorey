<?php

namespace App\Http\Controllers;

use App\Http\Requests\Flota\AsignarTrasladoRequest;
use App\Http\Requests\Flota\StoreChoferRequest;
use App\Http\Requests\Flota\StoreVehiculoRequest;
use App\Http\Requests\Flota\UpdateChoferRequest;
use App\Http\Requests\Flota\UpdateEstadoTrasladoRequest;
use App\Http\Requests\Flota\UpdateEstadoVehiculoRequest;
use App\Models\Chofer;
use App\Models\ProductoBodega;
use App\Models\Traslado;
use App\Models\User;
use App\Models\Vehiculo;
use App\Services\FlotaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FlotaController extends Controller
{
    public function __construct(
        protected FlotaService $flotaService
    ) {}

    public function calendario(): View
    {
        $vehiculosFlota = Vehiculo::with('choferAsignado')->orderBy('patente')->get();
        $choferesFlota = Chofer::with(['vehiculoAsignado', 'user'])->orderBy('nombre_completo')->get();

        return view('flota.calendario', [
            'traslados' => Traslado::with(['servicio', 'vehiculo', 'chofer'])
                ->whereNotNull('salida_at')
                ->orderBy('salida_at')
                ->paginate(20),
            'vehiculosFlota' => $vehiculosFlota,
            'vehiculos' => $vehiculosFlota->filter(fn (Vehiculo $v) => $v->estaOperativo()),
            'choferesFlota' => $choferesFlota,
            'choferes' => User::where('role', 'chofer')->orderBy('name')->get(),
        ]);
    }

    public function crearVehiculo(StoreVehiculoRequest $request): RedirectResponse
    {
        $this->flotaService->crearVehiculo($request->validated(), $request->boolean('apto_rural'));

        return back()->with('status', 'Vehículo incorporado exitosamente al control de flota.');
    }

    public function actualizarEstadoVehiculo(UpdateEstadoVehiculoRequest $request, $id): JsonResponse|RedirectResponse
    {
        $vehiculo = Vehiculo::findOrFail($id);

        $vehiculo = $this->flotaService->actualizarEstadoVehiculo(
            $vehiculo,
            $request->input('estado'),
            $request->filled('kilometraje') ? (int) $request->input('kilometraje') : null,
            $request->has('apto_rural') ? $request->boolean('apto_rural') : null
        );

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'El estado del vehículo ha sido actualizado correctamente.',
                'nuevo_estado' => $vehiculo->estado,
            ]);
        }

        return back()->with('status', "Estado del vehículo {$vehiculo->patente} actualizado a «{$vehiculo->estado}».");
    }

    public function crearChofer(StoreChoferRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $this->flotaService->crearChofer($data);

        return back()->with('status', "Conductor {$data['nombre_completo']} registrado exitosamente en la flota.");
    }

    public function actualizarChofer(UpdateChoferRequest $request, Chofer $chofer): RedirectResponse
    {
        $this->flotaService->actualizarChofer($chofer, $request->validated());

        return back()->with('status', "Datos operativos de {$chofer->nombre_completo} actualizados.");
    }

    public function chofer(Request $request): View
    {
        // Minimización de datos según Ley N° 21.663 (Ciberseguridad y RBAC):
        // Choferes solo acceden al nombre del difunto, horario y destino, omitiendo montos, convenios y causas legales
        $traslados = Traslado::with([
            'servicio:id,difunto_nombre',
            'vehiculo:id,patente,modelo_uso,estado',
        ])
            ->whereIn('estado', ['programado', 'en_camino'])
            ->when(
                $request->user()->hasRole('chofer'),
                fn ($query) => $query->where('chofer_id', $request->user()->id)
            )
            ->orderBy('salida_at')
            ->get();

        $productos = ProductoBodega::orderBy('modelo')->get();

        return view('movil.chofer', compact('traslados', 'productos'));
    }

    public function actualizarEstado(UpdateEstadoTrasladoRequest $request, Traslado $traslado): RedirectResponse
    {
        abort_if(
            $request->user()->hasRole('chofer') && $traslado->chofer_id !== $request->user()->id,
            403
        );

        $this->flotaService->actualizarEstadoTrasladoChofer($traslado, $request->validated('estado'));

        return back()->with('status', 'Estado del traslado actualizado.');
    }

    public function asignar(AsignarTrasladoRequest $request, Traslado $traslado): RedirectResponse
    {
        $this->flotaService->asignarTraslado($traslado, $request->validated());

        return back()->with('status', 'Asignación del traslado guardada sin solapamientos.');
    }
}

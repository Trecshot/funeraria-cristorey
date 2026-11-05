<?php

namespace App\Http\Controllers;

use App\Models\Traslado;
use App\Models\User;
use App\Models\Vehiculo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FlotaController extends Controller
{
    public function crearVehiculo(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'patente' => ['required', 'string', 'max:20', 'unique:vehiculos,patente'],
            'modelo' => ['required', 'string', 'max:255'],
        ]);

        $vehiculo = new Vehiculo([
            'patente' => $data['patente'],
            'nombre' => $data['modelo'],
        ]);
        if (Schema::hasColumn('vehiculos', 'modelo')) {
            $vehiculo->modelo = $data['modelo'];
        }
        $vehiculo->save();

        return back()->with('status', 'Vehículo agregado a la flota.');
    }

    public function calendario(): View
    {
        return view('flota.calendario', [
            'traslados' => Traslado::with(['servicio', 'vehiculo', 'chofer'])
                ->whereNotNull('salida_at')
                ->orderBy('salida_at')
                ->paginate(20),
            'vehiculos' => Vehiculo::where('estado', 'disponible')->orderBy('patente')->get(),
            'choferes' => User::where('role', 'chofer')->orderBy('name')->get(),
        ]);
    }

    public function chofer(Request $request): View
    {
        $traslados = Traslado::with(['servicio:id,difunto_nombre,cliente_nombre', 'vehiculo'])
            ->whereIn('estado', ['programado', 'en_camino'])
            ->when(
                $request->user()->role === 'chofer',
                fn ($query) => $query->where('chofer_id', $request->user()->id)
            )
            ->orderBy('salida_at')
            ->get();

        return view('movil.chofer', compact('traslados'));
    }

    public function actualizarEstado(Request $request, Traslado $traslado): RedirectResponse
    {
        abort_if(
            $request->user()->role === 'chofer' && $traslado->chofer_id !== $request->user()->id,
            403
        );

        $data = $request->validate([
            'estado' => ['required', 'in:en_camino,completado'],
        ]);

        $traslado->update($data);

        return back()->with('status', 'Estado del traslado actualizado.');
    }

    public function asignar(Request $request, Traslado $traslado): RedirectResponse
    {
        $data = $request->validate([
            'vehiculo_id' => ['nullable', 'integer', 'exists:vehiculos,id'],
            'chofer_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where('role', 'chofer'),
            ],
        ]);

        $traslado->update($data);

        return back()->with('status', 'Asignación del traslado actualizada.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Chofer;
use App\Models\ProductoBodega;
use App\Models\Traslado;
use App\Models\User;
use App\Models\Vehiculo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class FlotaController extends Controller
{
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

    public function crearVehiculo(Request $request): RedirectResponse
    {
        $modeloInput = $request->input('modelo_uso') ?: $request->input('modelo');
        $request->merge(['modelo_uso' => $modeloInput]);

        $data = $request->validate([
            'patente' => ['required', 'string', 'max:20', 'unique:vehiculos,patente'],
            'modelo_uso' => ['required', 'string', 'max:150'],
            'apto_rural' => ['nullable', 'boolean'],
            'licencia_requerida' => ['nullable', Rule::in(Chofer::LICENCIAS)],
            'estado' => ['nullable', Rule::in(Vehiculo::ESTADOS)],
            'kilometraje' => ['nullable', 'integer', 'min:0'],
        ]);

        Vehiculo::create([
            'patente' => strtoupper(trim($data['patente'])),
            'nombre' => $data['modelo_uso'],
            'modelo_uso' => $data['modelo_uso'],
            'apto_rural' => $request->boolean('apto_rural'),
            'licencia_requerida' => $data['licencia_requerida'] ?? 'A2',
            'estado' => $data['estado'] ?? 'Disponible',
            'kilometraje' => (int) ($data['kilometraje'] ?? 0),
        ]);

        return back()->with('status', 'Vehículo incorporado exitosamente al control de flota.');
    }

    public function actualizarEstadoVehiculo(Request $request, $id): JsonResponse|RedirectResponse
    {
        $request->validate([
            'estado' => ['required', Rule::in(Vehiculo::ESTADOS)],
            'kilometraje' => ['nullable', 'integer', 'min:0'],
            'apto_rural' => ['nullable', 'boolean'],
        ]);

        $vehiculo = Vehiculo::findOrFail($id);
        $vehiculo->estado = $request->input('estado');

        if ($request->filled('kilometraje')) {
            $vehiculo->kilometraje = (int) $request->input('kilometraje');
        }
        if ($request->has('apto_rural')) {
            $vehiculo->apto_rural = $request->boolean('apto_rural');
        }

        $vehiculo->save();

        // Sincronizar estado del chofer asignado si el móvil pasa a mantenimiento o ruta
        if ($vehiculo->choferAsignado) {
            if (in_array($vehiculo->estado, ['Mantenimiento', 'Fuera de Servicio'], true)) {
                $vehiculo->choferAsignado->update([
                    'estado' => 'Descanso',
                    'vehiculo_asignado_id' => null,
                ]);
            } elseif ($vehiculo->estado === 'En Ruta') {
                $vehiculo->choferAsignado->update(['estado' => 'Asignado en Ruta']);
            } elseif ($vehiculo->estado === 'Disponible' && $vehiculo->choferAsignado->estado === 'Asignado en Ruta') {
                $vehiculo->choferAsignado->update(['estado' => 'Disponible']);
            }
        }

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'El estado del vehículo ha sido actualizado correctamente.',
                'nuevo_estado' => $vehiculo->estado,
            ]);
        }

        return back()->with('status', "Estado del vehículo {$vehiculo->patente} actualizado a «{$vehiculo->estado}».");
    }

    public function crearChofer(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nombre_completo' => ['required', 'string', 'max:150'],
            'rut' => ['required', 'string', 'max:20', 'unique:choferes,rut'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'licencia_clase' => ['required', Rule::in(Chofer::LICENCIAS)],
            'estado' => ['required', Rule::in(Chofer::ESTADOS)],
            'vehiculo_asignado_id' => ['nullable', 'integer', 'exists:vehiculos,id'],
        ]);

        if (! empty($data['vehiculo_asignado_id'])) {
            $vehiculo = Vehiculo::findOrFail($data['vehiculo_asignado_id']);
            $choferTemp = new Chofer(['licencia_clase' => $data['licencia_clase']]);

            if (! $vehiculo->estaOperativo()) {
                throw ValidationException::withMessages([
                    'vehiculo_asignado_id' => "El vehículo {$vehiculo->patente} se encuentra en «{$vehiculo->estado_label}» y no puede ser asignado.",
                ]);
            }

            if (! $choferTemp->tieneLicenciaCompatible($vehiculo)) {
                throw ValidationException::withMessages([
                    'licencia_clase' => "La licencia clase {$data['licencia_clase']} no es compatible con el vehículo {$vehiculo->patente} (requiere {$vehiculo->licencia_requerida}).",
                ]);
            }

            // Liberar otro chofer que tuviera ese vehículo asignado
            Chofer::where('vehiculo_asignado_id', $vehiculo->id)->update(['vehiculo_asignado_id' => null]);
        }

        // Crear o vincular usuario RBAC con rol chofer
        $emailSlug = Str::slug($data['nombre_completo'], '.').'@cristorey.cl';
        $user = User::firstOrCreate(
            ['email' => $emailSlug],
            [
                'name' => $data['nombre_completo'],
                'password' => 'chofer123',
                'role' => 'chofer',
            ]
        );

        $data['user_id'] = $user->id;
        Chofer::create($data);

        return back()->with('status', "Conductor {$data['nombre_completo']} registrado exitosamente en la flota.");
    }

    public function actualizarChofer(Request $request, Chofer $chofer): RedirectResponse
    {
        $data = $request->validate([
            'estado' => ['required', Rule::in(Chofer::ESTADOS)],
            'licencia_clase' => ['nullable', Rule::in(Chofer::LICENCIAS)],
            'vehiculo_asignado_id' => ['nullable', 'integer', 'exists:vehiculos,id'],
        ]);

        if ($request->filled('licencia_clase')) {
            $chofer->licencia_clase = $data['licencia_clase'];
        }

        if (! empty($data['vehiculo_asignado_id'])) {
            $vehiculo = Vehiculo::findOrFail($data['vehiculo_asignado_id']);

            if (! $vehiculo->estaOperativo()) {
                throw ValidationException::withMessages([
                    'vehiculo_asignado_id' => "No se puede asignar el móvil {$vehiculo->patente} porque está en «{$vehiculo->estado_label}».",
                ]);
            }

            if (! $chofer->tieneLicenciaCompatible($vehiculo)) {
                throw ValidationException::withMessages([
                    'vehiculo_asignado_id' => "El conductor {$chofer->nombre_completo} (Lic. {$chofer->licencia_clase}) requiere licencia {$vehiculo->licencia_requerida} para operar {$vehiculo->patente}.",
                ]);
            }

            Chofer::where('vehiculo_asignado_id', $vehiculo->id)
                ->where('id', '!=', $chofer->id)
                ->update(['vehiculo_asignado_id' => null]);
        }

        $chofer->estado = $data['estado'];
        $chofer->vehiculo_asignado_id = in_array($data['estado'], ['Descanso', 'Permiso'], true)
            ? null
            : ($data['vehiculo_asignado_id'] ?? null);
        $chofer->save();

        // Sincronizar estado del vehículo si pasa a ruta
        if ($chofer->vehiculoAsignado) {
            if ($chofer->estado === 'Asignado en Ruta') {
                $chofer->vehiculoAsignado->update(['estado' => 'En Ruta']);
            } elseif ($chofer->estado === 'Disponible' && $chofer->vehiculoAsignado->estado === 'En Ruta') {
                $chofer->vehiculoAsignado->update(['estado' => 'Disponible']);
            }
        }

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

    public function actualizarEstado(Request $request, Traslado $traslado): RedirectResponse
    {
        abort_if(
            $request->user()->hasRole('chofer') && $traslado->chofer_id !== $request->user()->id,
            403
        );

        $data = $request->validate([
            'estado' => ['required', 'in:en_camino,completado'],
        ]);

        $traslado->update($data);

        if ($traslado->vehiculo) {
            $traslado->vehiculo->update([
                'estado' => $data['estado'] === 'en_camino' ? 'En Ruta' : 'Disponible',
            ]);
        }

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

        // 1. Validar estado del vehículo y prevención de solapamiento horario
        if (! empty($data['vehiculo_id'])) {
            $vehiculo = Vehiculo::findOrFail($data['vehiculo_id']);
            if (! $vehiculo->estaOperativo()) {
                throw ValidationException::withMessages([
                    'vehiculo_id' => "El vehículo {$vehiculo->patente} se encuentra en estado «{$vehiculo->estado_label}» y no puede asignarse.",
                ]);
            }

            if ($traslado->salida_at) {
                $inicioRango = $traslado->salida_at->copy()->subHours(2);
                $finRango = $traslado->salida_at->copy()->addHours(2);

                $conflictoVehiculo = Traslado::where('id', '!=', $traslado->id)
                    ->where('vehiculo_id', $vehiculo->id)
                    ->whereIn('estado', ['programado', 'en_camino'])
                    ->whereBetween('salida_at', [$inicioRango, $finRango])
                    ->exists();

                if ($conflictoVehiculo) {
                    throw ValidationException::withMessages([
                        'vehiculo_id' => "Solapamiento detectado: el vehículo {$vehiculo->patente} ya tiene un cortejo programado en ese bloque horario (±2 hrs).",
                    ]);
                }
            }
        }

        // 2. Validar disponibilidad del chofer, licencia y prevención de solapamiento horario
        if (! empty($data['chofer_id'])) {
            $fichaChofer = Chofer::where('user_id', $data['chofer_id'])->first();
            if ($fichaChofer && in_array($fichaChofer->estado, ['Descanso', 'Permiso'], true)) {
                throw ValidationException::withMessages([
                    'chofer_id' => "El conductor {$fichaChofer->nombre_completo} se encuentra en «{$fichaChofer->estado}».",
                ]);
            }

            if ($fichaChofer && ! empty($data['vehiculo_id'])) {
                $vehiculo = Vehiculo::find($data['vehiculo_id']);
                if ($vehiculo && ! $fichaChofer->tieneLicenciaCompatible($vehiculo)) {
                    throw ValidationException::withMessages([
                        'chofer_id' => "Asignación cruzada inválida: {$fichaChofer->nombre_completo} (Lic. {$fichaChofer->licencia_clase}) requiere licencia {$vehiculo->licencia_requerida} para el móvil {$vehiculo->patente}.",
                    ]);
                }
            }

            if ($traslado->salida_at) {
                $inicioRango = $traslado->salida_at->copy()->subHours(2);
                $finRango = $traslado->salida_at->copy()->addHours(2);

                $conflictoChofer = Traslado::where('id', '!=', $traslado->id)
                    ->where('chofer_id', $data['chofer_id'])
                    ->whereIn('estado', ['programado', 'en_camino'])
                    ->whereBetween('salida_at', [$inicioRango, $finRango])
                    ->exists();

                if ($conflictoChofer) {
                    throw ValidationException::withMessages([
                        'chofer_id' => 'Solapamiento detectado: el conductor seleccionado ya registra un traslado activo en el mismo rango horario (±2 hrs).',
                    ]);
                }
            }
        }

        $traslado->update($data);

        return back()->with('status', 'Asignación del traslado guardada sin solapamientos.');
    }
}

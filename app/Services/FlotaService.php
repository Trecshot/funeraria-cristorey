<?php

namespace App\Services;

use App\Models\Chofer;
use App\Models\Traslado;
use App\Models\User;
use App\Models\Vehiculo;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FlotaService
{
    public function crearVehiculo(array $data, bool $aptoRural): Vehiculo
    {
        return Vehiculo::create([
            'patente' => strtoupper(trim($data['patente'])),
            'nombre' => $data['modelo_uso'],
            'modelo_uso' => $data['modelo_uso'],
            'apto_rural' => $aptoRural,
            'licencia_requerida' => $data['licencia_requerida'] ?? 'A2',
            'estado' => $data['estado'] ?? 'Disponible',
            'kilometraje' => (int) ($data['kilometraje'] ?? 0),
        ]);
    }

    public function actualizarEstadoVehiculo(
        Vehiculo $vehiculo,
        string $estado,
        ?int $kilometraje = null,
        ?bool $aptoRural = null
    ): Vehiculo {
        $vehiculo->estado = $estado;

        if ($kilometraje !== null) {
            $vehiculo->kilometraje = $kilometraje;
        }
        if ($aptoRural !== null) {
            $vehiculo->apto_rural = $aptoRural;
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

        return $vehiculo;
    }

    public function crearChofer(array $data): Chofer
    {
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

        return Chofer::create($data);
    }

    public function actualizarChofer(Chofer $chofer, array $data): Chofer
    {
        if (! empty($data['licencia_clase'])) {
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

        return $chofer;
    }

    public function asignarTraslado(Traslado $traslado, array $data): void
    {
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
    }

    public function actualizarEstadoTrasladoChofer(Traslado $traslado, string $estado): void
    {
        $traslado->update(['estado' => $estado]);

        if ($traslado->vehiculo) {
            $traslado->vehiculo->update([
                'estado' => $estado === 'en_camino' ? 'En Ruta' : 'Disponible',
            ]);
        }
    }
}

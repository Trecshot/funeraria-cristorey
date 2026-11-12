<?php

namespace App\Services;

use App\Models\DocumentoServicio;
use App\Models\HistorialServicio;
use App\Models\Servicio;

class HitosOperativosService
{
    /**
     * Sincroniza y retorna los 5 hitos operativos del expediente.
     *
     * @return array<int, array<string, mixed>>
     */
    public function sincronizarHitos(Servicio $servicio, ?int $usuarioId = null): array
    {
        $userId = $usuarioId ?: (auth()->id() ?: $servicio->created_by);
        $historialMap = $servicio->relationLoaded('historial')
            ? $servicio->historial->keyBy('hito')
            : $servicio->historial()->get()->keyBy('hito');

        // Hito 1: Apertura
        $hito1Registro = $historialMap->get(HistorialServicio::HITO_APERTURA);
        if (! $hito1Registro && $servicio->exists) {
            $hito1Registro = $servicio->registrarHito(
                HistorialServicio::HITO_APERTURA,
                'Apertura de Expediente',
                "Expediente {$servicio->folio} creado para {$servicio->difunto_nombre}. Convenio: ".strtoupper((string) $servicio->convenio).'.',
                $servicio->created_by ?: $userId,
                $servicio->created_at ?: now()
            );
        }
        $paso1 = [
            'numero' => 1,
            'clave' => HistorialServicio::HITO_APERTURA,
            'nombre' => 'Apertura',
            'titulo' => 'Apertura de Expediente',
            'icono' => 'fa-folder-open',
            'estado' => 'completado',
            'fecha' => $hito1Registro?->completado_at ?? $servicio->created_at,
            'responsable' => $servicio->creador?->name ?? 'Usuario Sistema',
            'detalle' => "Folio: {$servicio->folio}",
            'completado' => true,
        ];

        // Hito 2: Logística / Urna
        $tieneUrna = $servicio->urna_id !== null && $servicio->urna !== null;
        $hito2Registro = $historialMap->get(HistorialServicio::HITO_LOGISTICA_URNA);
        if ($tieneUrna && ! $hito2Registro) {
            $hito2Registro = $servicio->registrarHito(
                HistorialServicio::HITO_LOGISTICA_URNA,
                'Urna Asignada',
                "Urna modelo {$servicio->urna->modelo} ({$servicio->urna->color}) asignada desde inventario.",
                $userId,
                $servicio->updated_at ?: now()
            );
        }
        $paso2 = [
            'numero' => 2,
            'clave' => HistorialServicio::HITO_LOGISTICA_URNA,
            'nombre' => 'Logística / Urna',
            'titulo' => 'Asignación de Urna',
            'icono' => 'fa-box-archive',
            'estado' => $tieneUrna ? 'completado' : 'activo',
            'fecha' => $tieneUrna ? ($hito2Registro?->completado_at ?? $servicio->updated_at) : null,
            'responsable' => $tieneUrna ? ($hito2Registro?->usuario?->name ?? 'Bodega / Operaciones') : null,
            'detalle' => $tieneUrna ? "Modelo: {$servicio->urna->modelo} ({$servicio->urna->color})" : 'Pendiente asignar urna',
            'completado' => $tieneUrna,
        ];

        // Hito 3: Legal y Documental
        $faltantes = $servicio->calcularDocumentosFaltantes();
        $docs = $servicio->relationLoaded('documentos') ? $servicio->documentos : $servicio->documentos()->get();
        $tieneSeremi = $docs->contains(fn ($doc) => $doc->tipo === 'resolucion_seremi');
        $documentosCompletados = $tieneSeremi && count($faltantes) === 0;
        $hito3Registro = $historialMap->get(HistorialServicio::HITO_DOCUMENTAL);
        if ($documentosCompletados && ! $hito3Registro) {
            $ultimoDoc = $docs->sortByDesc('created_at')->first();
            $hito3Registro = $servicio->registrarHito(
                HistorialServicio::HITO_DOCUMENTAL,
                'Documentación Validada',
                'Resolución Seremi y antecedentes legales verificados en el expediente digital.',
                $ultimoDoc?->uploaded_by ?: $userId,
                $ultimoDoc?->created_at ?: now()
            );
        }
        $estadoPaso3 = $documentosCompletados ? 'completado' : ($docs->isNotEmpty() || $tieneUrna ? 'activo' : 'pendiente');
        $detallePaso3 = $documentosCompletados
            ? 'Resolución Seremi y antecedentes verificados'
            : (count($faltantes) > 0
                ? 'Falta: '.implode(', ', array_map(fn ($t) => DocumentoServicio::TIPOS[$t] ?? $t, $faltantes))
                : 'Pendiente cargar Resolución Seremi');
        $paso3 = [
            'numero' => 3,
            'clave' => HistorialServicio::HITO_DOCUMENTAL,
            'nombre' => 'Legal y Documental',
            'titulo' => 'Validación Seremi / Legal',
            'icono' => 'fa-file-shield',
            'estado' => $estadoPaso3,
            'fecha' => $documentosCompletados ? ($hito3Registro?->completado_at ?? now()) : null,
            'responsable' => $documentosCompletados ? ($hito3Registro?->usuario?->name ?? 'Operaciones') : null,
            'detalle' => $detallePaso3,
            'completado' => $documentosCompletados,
        ];

        // Hito 4: Cortejo y Flota
        $trasladosList = $servicio->relationLoaded('traslados') ? $servicio->traslados : $servicio->traslados()->get();
        $traslado = $trasladosList->sortByDesc('salida_at')->first();
        $tieneChofer = $traslado && $traslado->chofer_id;
        $tieneVehiculo = $traslado && $traslado->vehiculo_id;
        $tieneFechaCortejo = $servicio->cortejo_at !== null;
        $cortejoCompletado = $tieneFechaCortejo && $tieneChofer && $tieneVehiculo;
        $hito4Registro = $historialMap->get(HistorialServicio::HITO_CORTEJO_FLOTA);
        if ($cortejoCompletado && ! $hito4Registro) {
            $hito4Registro = $servicio->registrarHito(
                HistorialServicio::HITO_CORTEJO_FLOTA,
                'Cortejo Programado',
                "Vehículo {$traslado->vehiculo?->patente} con chofer {$traslado->chofer?->name} para ".$servicio->cortejo_at?->format('d/m/Y H:i').'.',
                $userId,
                $traslado->updated_at ?: now()
            );
        }
        $estadoPaso4 = $cortejoCompletado
            ? 'completado'
            : ($tieneFechaCortejo ? 'activo' : ($documentosCompletados ? 'activo' : 'pendiente'));
        $detallePaso4 = $cortejoCompletado
            ? 'Chofer: '.($traslado->chofer?->name ?? 'Asignado').' · '.($traslado->vehiculo?->patente ?? 'Vehículo asignado')
            : ($tieneFechaCortejo
                ? 'Falta asignar '.(! $tieneChofer && ! $tieneVehiculo ? 'chofer y vehículo' : (! $tieneChofer ? 'chofer' : 'vehículo'))
                : 'Sin programar fecha');
        $paso4 = [
            'numero' => 4,
            'clave' => HistorialServicio::HITO_CORTEJO_FLOTA,
            'nombre' => 'Cortejo y Flota',
            'titulo' => 'Cortejo y Flota',
            'icono' => 'fa-van-shuttle',
            'estado' => $estadoPaso4,
            'fecha' => $cortejoCompletado ? ($hito4Registro?->completado_at ?? $servicio->cortejo_at) : null,
            'responsable' => $cortejoCompletado ? ($traslado->chofer?->name ?? 'Chofer asignado') : null,
            'detalle' => $detallePaso4,
            'completado' => $cortejoCompletado,
        ];

        // Hito 5: Finalizado
        $esFinalizado = $servicio->estado === 'completado';
        $hito5Registro = $historialMap->get(HistorialServicio::HITO_FINALIZADO);
        if ($esFinalizado && ! $hito5Registro) {
            $hito5Registro = $servicio->registrarHito(
                HistorialServicio::HITO_FINALIZADO,
                'Servicio Finalizado',
                'Servicio ejecutado y expediente cerrado operativamente.',
                $userId,
                $servicio->updated_at ?: now()
            );
        }
        $estadoPaso5 = $esFinalizado ? 'completado' : ($cortejoCompletado ? 'activo' : 'pendiente');
        $paso5 = [
            'numero' => 5,
            'clave' => HistorialServicio::HITO_FINALIZADO,
            'nombre' => 'Finalizado',
            'titulo' => 'Servicio Concluido',
            'icono' => 'fa-circle-check',
            'estado' => $estadoPaso5,
            'fecha' => $esFinalizado ? ($hito5Registro?->completado_at ?? $servicio->updated_at) : null,
            'responsable' => $esFinalizado ? ($hito5Registro?->usuario?->name ?? 'Administración') : null,
            'detalle' => $esFinalizado ? 'Expediente cerrado con éxito' : 'Pendiente de ejecución',
            'completado' => $esFinalizado,
        ];

        return [$paso1, $paso2, $paso3, $paso4, $paso5];
    }

    /**
     * Calcula el porcentaje de avance operativo del expediente.
     */
    public function calcularPorcentajeAvance(Servicio $servicio): int
    {
        $pasos = $this->sincronizarHitos($servicio);
        $completados = count(array_filter($pasos, fn ($p) => $p['completado']));

        return (int) round(($completados / count($pasos)) * 100);
    }
}

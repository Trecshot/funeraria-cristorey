<?php

namespace App\Models;

use App\Services\IndicadoresService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;

class Servicio extends Model
{
    public const APORTE_IPS_CLP = 1_070_000;

    public const APORTE_AFP_UF = 15;

    public const CONVENIOS = [
        'particular' => 'Particular',
        'ips' => 'IPS · $1.070.000',
        'afp' => 'AFP · 15 UF',
        'dipreca' => 'Dipreca',
        'sindicato' => 'Sindicato',
    ];

    public const ESTADOS = [
        'abierto' => 'Abierto',
        'en_preparacion' => 'En preparación',
        'completado' => 'Completado',
        'cancelado' => 'Cancelado',
    ];

    protected $table = 'servicios';

    protected $fillable = [
        'folio', 'created_by', 'cliente_nombre', 'cliente_rut', 'cliente_telefono',
        'difunto_nombre', 'difunto_rut', 'escolaridad', 'oficio', 'causa_sml',
        'requiere_sml', 'convenio', 'urna_id', 'monto_servicio', 'aporte_convenio', 'copago',
        'uf_value_clp', 'cortejo_at', 'estado',
    ];

    protected function casts(): array
    {
        return [
            'monto_servicio' => 'integer',
            'aporte_convenio' => 'integer',
            'copago' => 'integer',
            'uf_value_clp' => 'integer',
            'cortejo_at' => 'datetime',
            'requiere_sml' => 'boolean',
        ];
    }

    public function getClienteContratanteAttribute(): ?string
    {
        return $this->cliente_nombre;
    }

    public function getTelefonoClienteAttribute(): ?string
    {
        return $this->cliente_telefono;
    }

    public function getDifuntoAttribute(): ?string
    {
        return $this->difunto_nombre;
    }

    public function getTotalServicioAttribute(): int
    {
        return (int) $this->monto_servicio;
    }

    public function getDestinoAttribute(): ?string
    {
        if ($this->relationLoaded('traslados')) {
            return $this->traslados->first()?->cementerio;
        }

        return $this->traslados()->latest('salida_at')->value('cementerio');
    }

    public function estadoLabel(): string
    {
        return self::ESTADOS[$this->estado] ?? str_replace('_', ' ', ucfirst((string) $this->estado));
    }

    public function estadoBadgeClass(): string
    {
        return match ($this->estado) {
            'completado' => 'bg-emerald-100 text-emerald-800',
            'cancelado' => 'bg-rose-100 text-rose-800',
            default => 'bg-blue-100 text-blue-800',
        };
    }

    public function urna(): BelongsTo
    {
        return $this->belongsTo(Urna::class, 'urna_id');
    }

    public function historial(): HasMany
    {
        return $this->hasMany(HistorialServicio::class, 'servicio_id')->orderBy('completado_at', 'asc')->orderBy('id', 'asc');
    }

    public function documentos(): HasMany
    {
        return $this->hasMany(DocumentoServicio::class);
    }

    public function traslados(): HasMany
    {
        return $this->hasMany(Traslado::class);
    }

    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return list<string>
     */
    public function documentosRequeridos(): array
    {
        $requeridos = ['resolucion_seremi'];
        if (filled($this->causa_sml)) {
            $requeridos[] = 'oficio_fiscalia_sml';
        }

        return $requeridos;
    }

    /**
     * @return list<string>
     */
    public function calcularDocumentosFaltantes(): array
    {
        $presentes = $this->documentos->pluck('tipo')->all();

        return array_values(array_diff($this->documentosRequeridos(), $presentes));
    }

    /**
     * @return Collection<int, self>
     */
    public static function criticosConDocumentosFaltantes(): Collection
    {
        return static::query()
            ->whereBetween('cortejo_at', [now(), now()->addHours(3)])
            ->with('documentos:id,servicio_id,tipo')
            ->orderBy('cortejo_at')
            ->get()
            ->map(function (self $servicio): self {
                $servicio->documentosFaltantes = $servicio->calcularDocumentosFaltantes();

                return $servicio;
            })
            ->filter(fn (self $servicio): bool => $servicio->documentosFaltantes !== [])
            ->values();
    }

    public function liquidacion(): HasOne
    {
        return $this->hasOne(Liquidacion::class, 'servicio_id');
    }

    public function getCopagoEstimadoAttribute(): ?int
    {
        $copago = $this->copagoEstimado();

        return $copago !== null ? (int) round($copago) : null;
    }

    public function getAporteConvenioEstimadoAttribute(): ?int
    {
        $aporte = $this->aporteConvenio();

        return $aporte !== null ? (int) round($aporte) : null;
    }

    public function aporteConvenio(?float $valorUf = null): ?float
    {
        $convenio = strtolower((string) $this->convenio);

        if ($convenio === 'particular') {
            return 0.0;
        }

        if ($convenio === 'ips') {
            return (float) self::APORTE_IPS_CLP;
        }

        if ($convenio === 'afp') {
            $uf = $valorUf ?: ($this->uf_value_clp ?: app(IndicadoresService::class)->getValorUF());
            if (! $uf) {
                return null;
            }

            return (float) round(self::APORTE_AFP_UF * $uf);
        }

        if (in_array($convenio, ['dipreca', 'sindicato'], true)) {
            return $this->aporte_convenio > 0 ? (float) $this->aporte_convenio : null;
        }

        return (float) ($this->aporte_convenio ?: 0);
    }

    public function copagoEstimado(?float $valorUf = null): ?float
    {
        $aporte = $this->aporteConvenio($valorUf);
        if ($aporte === null) {
            return null;
        }

        return (float) max(0, (int) $this->monto_servicio - (int) round($aporte));
    }

    public function calcularAportes(?float $valorUf = null): void
    {
        $convenio = strtolower((string) $this->convenio);
        $ufValue = null;

        if ($convenio === 'afp') {
            $ufValue = (int) round((float) ($valorUf ?: ($this->uf_value_clp ?: app(IndicadoresService::class)->getValorUF())));
        }

        $aporte = match ($convenio) {
            'ips' => self::APORTE_IPS_CLP,
            'afp' => $ufValue ? (int) round(self::APORTE_AFP_UF * $ufValue) : 0,
            default => (int) ($this->aporte_convenio ?: 0),
        };

        $convenioConAporteConocido = in_array($convenio, ['particular', 'ips'], true)
            || ($convenio === 'afp' && $ufValue);

        $this->uf_value_clp = $ufValue;
        $this->aporte_convenio = $aporte;
        $this->copago = $convenioConAporteConocido
            ? max(0, (int) $this->monto_servicio - $aporte)
            : (int) $this->monto_servicio;
    }

    public function obtenerOCrearLiquidacion(?float $valorUf = null): Liquidacion
    {
        $aporte = (int) round($this->aporteConvenio($valorUf) ?? 0);
        $copago = (int) round($this->copagoEstimado($valorUf) ?? $this->monto_servicio);

        $liquidacion = $this->relationLoaded('liquidacion') ? $this->getRelation('liquidacion') : $this->liquidacion;

        if (! $liquidacion) {
            $liquidacion = $this->liquidacion()->create([
                'monto_total' => (int) $this->monto_servicio,
                'monto_subvencion' => $aporte,
                'monto_copago_familia' => $copago,
                'monto_copago_pagado' => 0,
                'estado_subvencion' => 'tramitacion',
                'estado_copago' => 'pendiente',
                'folio_factura' => 'FE-'.str_pad((string) $this->id, 5, '0', STR_PAD_LEFT),
                'folio_boleta' => 'BE-'.str_pad((string) $this->id, 5, '0', STR_PAD_LEFT),
            ]);
            $this->setRelation('liquidacion', $liquidacion);
        } else {
            if ($liquidacion->monto_total === 0 || $liquidacion->estado_subvencion === 'tramitacion') {
                $liquidacion->monto_total = (int) $this->monto_servicio;
                $liquidacion->monto_subvencion = $aporte;
                $liquidacion->monto_copago_familia = $copago;
                if (! $liquidacion->folio_factura) {
                    $liquidacion->folio_factura = 'FE-'.str_pad((string) $this->id, 5, '0', STR_PAD_LEFT);
                }
                if (! $liquidacion->folio_boleta) {
                    $liquidacion->folio_boleta = 'BE-'.str_pad((string) $this->id, 5, '0', STR_PAD_LEFT);
                }
                $liquidacion->save();
            }
        }

        return $liquidacion;
    }

    public function registrarHito(
        string $hito,
        string $titulo,
        ?string $descripcion = null,
        ?int $usuarioId = null,
        mixed $completadoAt = null
    ): HistorialServicio {
        return $this->historial()->updateOrCreate(
            ['hito' => $hito],
            [
                'titulo' => $titulo,
                'descripcion' => $descripcion,
                'usuario_id' => $usuarioId ?: (auth()->id() ?: $this->created_by),
                'completado_at' => $completadoAt ?: now(),
            ]
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function sincronizarHitosOperativos(?int $usuarioId = null): array
    {
        $userId = $usuarioId ?: (auth()->id() ?: $this->created_by);
        $historialMap = $this->relationLoaded('historial')
            ? $this->historial->keyBy('hito')
            : $this->historial()->get()->keyBy('hito');

        // Hito 1: Apertura
        $hito1Registro = $historialMap->get(HistorialServicio::HITO_APERTURA);
        if (! $hito1Registro && $this->exists) {
            $hito1Registro = $this->registrarHito(
                HistorialServicio::HITO_APERTURA,
                'Apertura de Expediente',
                "Expediente {$this->folio} creado para {$this->difunto_nombre}. Convenio: ".strtoupper((string) $this->convenio).'.',
                $this->created_by ?: $userId,
                $this->created_at ?: now()
            );
        }
        $paso1 = [
            'numero' => 1,
            'clave' => HistorialServicio::HITO_APERTURA,
            'nombre' => 'Apertura',
            'titulo' => 'Apertura de Expediente',
            'icono' => 'fa-folder-open',
            'estado' => 'completado',
            'fecha' => $hito1Registro?->completado_at ?? $this->created_at,
            'responsable' => $this->creador?->name ?? 'Usuario Sistema',
            'detalle' => "Folio: {$this->folio}",
            'completado' => true,
        ];

        // Hito 2: Logística / Urna
        $tieneUrna = $this->urna_id !== null && $this->urna !== null;
        $hito2Registro = $historialMap->get(HistorialServicio::HITO_LOGISTICA_URNA);
        if ($tieneUrna && ! $hito2Registro) {
            $hito2Registro = $this->registrarHito(
                HistorialServicio::HITO_LOGISTICA_URNA,
                'Urna Asignada',
                "Urna modelo {$this->urna->modelo} ({$this->urna->color}) asignada desde inventario.",
                $userId,
                $this->updated_at ?: now()
            );
        }
        $paso2 = [
            'numero' => 2,
            'clave' => HistorialServicio::HITO_LOGISTICA_URNA,
            'nombre' => 'Logística / Urna',
            'titulo' => 'Asignación de Urna',
            'icono' => 'fa-box-archive',
            'estado' => $tieneUrna ? 'completado' : 'activo',
            'fecha' => $tieneUrna ? ($hito2Registro?->completado_at ?? $this->updated_at) : null,
            'responsable' => $tieneUrna ? ($hito2Registro?->usuario?->name ?? 'Bodega / Operaciones') : null,
            'detalle' => $tieneUrna ? "Modelo: {$this->urna->modelo} ({$this->urna->color})" : 'Pendiente asignar urna',
            'completado' => $tieneUrna,
        ];

        // Hito 3: Legal y Documental
        $faltantes = $this->calcularDocumentosFaltantes();
        $docs = $this->relationLoaded('documentos') ? $this->documentos : $this->documentos()->get();
        $tieneSeremi = $docs->contains(fn ($doc) => $doc->tipo === 'resolucion_seremi');
        $documentosCompletados = $tieneSeremi && count($faltantes) === 0;
        $hito3Registro = $historialMap->get(HistorialServicio::HITO_DOCUMENTAL);
        if ($documentosCompletados && ! $hito3Registro) {
            $ultimoDoc = $docs->sortByDesc('created_at')->first();
            $hito3Registro = $this->registrarHito(
                HistorialServicio::HITO_DOCUMENTAL,
                'Documentación Validada',
                'Resolución Seremi y antecedentes legales respaldados en S3.',
                $ultimoDoc?->uploaded_by ?: $userId,
                $ultimoDoc?->created_at ?: now()
            );
        }
        $estadoPaso3 = $documentosCompletados ? 'completado' : ($docs->isNotEmpty() || $tieneUrna ? 'activo' : 'pendiente');
        $detallePaso3 = $documentosCompletados
            ? 'Resolución Seremi respaldada en S3'
            : (count($faltantes) > 0
                ? 'Falta: '.implode(', ', array_map(fn ($t) => DocumentoServicio::TIPOS[$t] ?? $t, $faltantes))
                : 'Pendiente cargar Resolución Seremi');
        $paso3 = [
            'numero' => 3,
            'clave' => HistorialServicio::HITO_DOCUMENTAL,
            'nombre' => 'Legal y Documental',
            'titulo' => 'Validación Seremi / S3',
            'icono' => 'fa-file-shield',
            'estado' => $estadoPaso3,
            'fecha' => $documentosCompletados ? ($hito3Registro?->completado_at ?? now()) : null,
            'responsable' => $documentosCompletados ? ($hito3Registro?->usuario?->name ?? 'Operaciones') : null,
            'detalle' => $detallePaso3,
            'completado' => $documentosCompletados,
        ];

        // Hito 4: Cortejo y Flota
        $trasladosList = $this->relationLoaded('traslados') ? $this->traslados : $this->traslados()->get();
        $traslado = $trasladosList->sortByDesc('salida_at')->first();
        $tieneChofer = $traslado && $traslado->chofer_id;
        $tieneVehiculo = $traslado && $traslado->vehiculo_id;
        $tieneFechaCortejo = $this->cortejo_at !== null;
        $cortejoCompletado = $tieneFechaCortejo && $tieneChofer && $tieneVehiculo;
        $hito4Registro = $historialMap->get(HistorialServicio::HITO_CORTEJO_FLOTA);
        if ($cortejoCompletado && ! $hito4Registro) {
            $hito4Registro = $this->registrarHito(
                HistorialServicio::HITO_CORTEJO_FLOTA,
                'Cortejo Programado',
                "Vehículo {$traslado->vehiculo?->patente} con chofer {$traslado->chofer?->name} para ".$this->cortejo_at?->format('d/m/Y H:i').'.',
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
            'fecha' => $cortejoCompletado ? ($hito4Registro?->completado_at ?? $this->cortejo_at) : null,
            'responsable' => $cortejoCompletado ? ($traslado->chofer?->name ?? 'Chofer asignado') : null,
            'detalle' => $detallePaso4,
            'completado' => $cortejoCompletado,
        ];

        // Hito 5: Finalizado
        $esFinalizado = $this->estado === 'completado';
        $hito5Registro = $historialMap->get(HistorialServicio::HITO_FINALIZADO);
        if ($esFinalizado && ! $hito5Registro) {
            $hito5Registro = $this->registrarHito(
                HistorialServicio::HITO_FINALIZADO,
                'Servicio Finalizado',
                'Servicio ejecutado y expediente cerrado operativamente.',
                $userId,
                $this->updated_at ?: now()
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
            'fecha' => $esFinalizado ? ($hito5Registro?->completado_at ?? $this->updated_at) : null,
            'responsable' => $esFinalizado ? ($hito5Registro?->usuario?->name ?? 'Administración') : null,
            'detalle' => $esFinalizado ? 'Expediente cerrado con éxito' : 'Pendiente de ejecución',
            'completado' => $esFinalizado,
        ];

        return [$paso1, $paso2, $paso3, $paso4, $paso5];
    }

    public function calcularPorcentajeAvance(): int
    {
        $pasos = $this->sincronizarHitosOperativos();
        $completados = count(array_filter($pasos, fn ($p) => $p['completado']));

        return (int) round(($completados / count($pasos)) * 100);
    }
}

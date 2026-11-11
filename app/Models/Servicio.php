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
        'requiere_sml', 'convenio', 'monto_servicio', 'aporte_convenio', 'copago',
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
}

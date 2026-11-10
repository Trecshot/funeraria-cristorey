<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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

    public function aporteConvenio(): ?float
    {
        return in_array($this->convenio, ['particular', 'ips'], true)
            || ($this->convenio === 'afp' && ($this->uf_value_clp || config('funeraria.uf_value_clp')))
            ? (float) $this->aporte_convenio
            : null;
    }

    public function copagoEstimado(): ?float
    {
        return $this->aporteConvenio() === null ? null : (float) $this->copago;
    }

    public function calcularAportes(): void
    {
        $ufValue = $this->convenio === 'afp'
            ? (int) round((float) ($this->uf_value_clp ?: config('funeraria.uf_value_clp')))
            : null;

        $aporte = match ($this->convenio) {
            'ips' => self::APORTE_IPS_CLP,
            'afp' => $ufValue ? self::APORTE_AFP_UF * $ufValue : 0,
            default => 0,
        };
        $convenioConAporteConocido = in_array($this->convenio, ['particular', 'ips'], true)
            || ($this->convenio === 'afp' && $ufValue);

        $this->uf_value_clp = $ufValue;
        $this->aporte_convenio = $aporte;
        $this->copago = $convenioConAporteConocido
            ? max(0, (int) $this->monto_servicio - $aporte)
            : (int) $this->monto_servicio;
    }
}

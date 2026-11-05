<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Servicio extends Model
{
    protected $table = 'servicios';

    protected $fillable = [
        'folio', 'created_by', 'cliente_nombre', 'cliente_rut', 'cliente_telefono',
        'difunto_nombre', 'difunto_rut', 'escolaridad', 'oficio', 'causa_sml',
        'requiere_sml', 'convenio', 'monto_servicio', 'aporte_convenio', 'copago',
        'uf_value_clp', 'cortejo_at', 'estado', 'cliente_contratante',
        'telefono_cliente', 'difunto', 'total_servicio', 'destino',
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
            'ips' => 1_070_000,
            'afp' => $ufValue ? 15 * $ufValue : 0,
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

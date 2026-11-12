<?php

namespace App\Models;

use App\Models\Concerns\CalculaAportesPrevisionales;
use App\Models\Concerns\GestionaHitosYDocumentos;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Servicio extends Model
{
    use CalculaAportesPrevisionales;
    use GestionaHitosYDocumentos;

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

    public function getNombreDifuntoAttribute(): ?string
    {
        return $this->difunto_nombre;
    }

    public function getRutDifuntoAttribute(): ?string
    {
        return $this->difunto_rut;
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

    public function documentoLegal(): HasOne
    {
        return $this->hasOne(DocumentoLegal::class, 'expediente_id');
    }

    public function documentosLegales(): HasOne
    {
        return $this->hasOne(DocumentoLegal::class, 'expediente_id');
    }

    public function auditorias(): HasMany
    {
        return $this->hasMany(HistorialAuditoria::class, 'expediente_id')->latest();
    }

    public function traslados(): HasMany
    {
        return $this->hasMany(Traslado::class);
    }

    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function liquidacion(): HasOne
    {
        return $this->hasOne(Liquidacion::class, 'servicio_id');
    }
}

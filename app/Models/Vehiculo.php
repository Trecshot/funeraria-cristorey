<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Vehiculo extends Model
{
    public const ESTADOS = [
        'Disponible',
        'En Ruta',
        'Mantenimiento',
        'Fuera de Servicio',
    ];

    protected $table = 'vehiculos';

    protected $fillable = [
        'patente',
        'nombre',
        'modelo_uso',
        'apto_rural',
        'licencia_requerida',
        'estado',
        'kilometraje',
    ];

    protected $attributes = [
        'apto_rural' => false,
        'licencia_requerida' => 'A2',
        'estado' => 'Disponible',
        'kilometraje' => 0,
    ];

    protected function casts(): array
    {
        return [
            'apto_rural' => 'boolean',
            'kilometraje' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Vehiculo $vehiculo): void {
            if (! empty($vehiculo->modelo_uso) && empty($vehiculo->nombre)) {
                $vehiculo->nombre = $vehiculo->modelo_uso;
            } elseif (! empty($vehiculo->nombre) && empty($vehiculo->modelo_uso)) {
                $vehiculo->modelo_uso = $vehiculo->nombre;
            }
        });
    }

    public function getModeloAttribute(): ?string
    {
        return $this->modelo_uso ?: $this->nombre;
    }

    public function getEstadoLabelAttribute(): string
    {
        return match (strtolower((string) $this->estado)) {
            'disponible' => 'Disponible',
            'en ruta', 'en_ruta', 'en cortejo' => 'En Ruta',
            'mantenimiento', 'en mantenimiento' => 'Mantenimiento',
            'fuera de servicio', 'fuera_de_servicio' => 'Fuera de Servicio',
            default => ucfirst((string) $this->estado),
        };
    }

    public function getEstadoBadgeAttribute(): array
    {
        return match ($this->estado_label) {
            'Disponible' => [
                'label' => 'Disponible',
                'class' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                'dot' => 'text-emerald-600',
            ],
            'En Ruta' => [
                'label' => 'En Ruta',
                'class' => 'bg-blue-100 text-blue-800 border-blue-200',
                'dot' => 'text-blue-600',
            ],
            'Mantenimiento' => [
                'label' => 'Mantenimiento',
                'class' => 'bg-amber-100 text-amber-900 border-amber-200',
                'dot' => 'text-amber-600',
            ],
            default => [
                'label' => 'Fuera de Servicio',
                'class' => 'bg-rose-100 text-rose-800 border-rose-200',
                'dot' => 'text-rose-600',
            ],
        };
    }

    public function getKilometrajeFormateadoAttribute(): string
    {
        return number_format((int) $this->kilometraje, 0, ',', '.').' km';
    }

    public function estaOperativo(): bool
    {
        return in_array($this->estado_label, ['Disponible', 'En Ruta'], true);
    }

    public function choferAsignado(): HasOne
    {
        return $this->hasOne(Chofer::class, 'vehiculo_asignado_id');
    }

    public function traslados(): HasMany
    {
        return $this->hasMany(Traslado::class);
    }
}

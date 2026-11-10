<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Chofer extends Model
{
    public const ESTADOS = [
        'Disponible',
        'Asignado en Ruta',
        'Descanso',
        'Permiso',
    ];

    public const LICENCIAS = ['A1', 'A2', 'A3', 'B'];

    protected $table = 'choferes';

    protected $fillable = [
        'user_id',
        'nombre_completo',
        'rut',
        'telefono',
        'licencia_clase',
        'estado',
        'vehiculo_asignado_id',
    ];

    protected $attributes = [
        'licencia_clase' => 'A2',
        'estado' => 'Disponible',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function vehiculoAsignado(): BelongsTo
    {
        return $this->belongsTo(Vehiculo::class, 'vehiculo_asignado_id');
    }

    public function getEstadoBadgeAttribute(): array
    {
        return match ($this->estado) {
            'Disponible' => [
                'label' => 'Disponible',
                'class' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                'dot' => 'text-emerald-600',
            ],
            'Asignado en Ruta' => [
                'label' => 'Asignado en Ruta',
                'class' => 'bg-blue-100 text-blue-800 border-blue-200',
                'dot' => 'text-blue-600',
            ],
            'Descanso' => [
                'label' => 'Descanso',
                'class' => 'bg-slate-100 text-slate-700 border-slate-200',
                'dot' => 'text-slate-500',
            ],
            default => [
                'label' => 'Permiso',
                'class' => 'bg-amber-100 text-amber-900 border-amber-200',
                'dot' => 'text-amber-600',
            ],
        };
    }

    public function tieneLicenciaCompatible(?Vehiculo $vehiculo): bool
    {
        if (! $vehiculo) {
            return true;
        }

        // Clases profesionales A1, A2, A3 cubren carrozas y furgones; B cubre vehículos auxiliares livianos
        $jerarquia = ['B' => 1, 'A1' => 2, 'A2' => 3, 'A3' => 4];
        $nivelChofer = $jerarquia[$this->licencia_clase] ?? 2;
        $nivelRequerido = $jerarquia[$vehiculo->licencia_requerida] ?? 2;

        return $nivelChofer >= $nivelRequerido;
    }
}

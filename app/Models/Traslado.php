<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Traslado extends Model
{
    public const IMPLEMENTOS_POR_DEFECTO = [
        'Cruz grabada',
        'Tarjetas',
        'Polietileno (Nailon rural)',
    ];

    protected $fillable = [
        'servicio_id', 'vehiculo_id', 'chofer_id', 'salida_at', 'cementerio',
        'implementos', 'estado',
    ];

    protected function casts(): array
    {
        return [
            'salida_at' => 'datetime',
            'implementos' => 'array',
        ];
    }

    public function getDestinoAttribute(): ?string
    {
        return $this->cementerio;
    }

    public function estadoLabel(): string
    {
        return str_replace('_', ' ', ucfirst((string) $this->estado));
    }

    public function servicio(): BelongsTo
    {
        return $this->belongsTo(Servicio::class);
    }

    public function vehiculo(): BelongsTo
    {
        return $this->belongsTo(Vehiculo::class);
    }

    public function chofer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'chofer_id');
    }
}

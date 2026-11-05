<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehiculo extends Model
{
    protected $fillable = ['patente', 'nombre', 'modelo', 'estado'];

    public function getModeloAttribute(): ?string
    {
        return $this->nombre;
    }

    public function traslados(): HasMany
    {
        return $this->hasMany(Traslado::class);
    }
}

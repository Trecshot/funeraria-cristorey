<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductoBodega extends Model
{
    protected $table = 'productos_bodega';

    protected $fillable = [
        'modelo', 'color', 'tallado', 'stock_salon', 'stock_taller', 'stock_minimo',
        'publicado', 'stock', 'ubicacion',
    ];

    protected function casts(): array
    {
        return [
            'publicado' => 'boolean',
            'stock_salon' => 'integer',
            'stock_taller' => 'integer',
            'stock_minimo' => 'integer',
        ];
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(MovimientoBodega::class);
    }

    public function getStockCriticoAttribute(): bool
    {
        return $this->stock_total <= $this->stock_minimo;
    }

    public function getStockTotalAttribute(): int
    {
        return $this->stock_salon + $this->stock_taller;
    }

    public function stockColumn(string $ubicacion): string
    {
        return $ubicacion === 'salon' ? 'stock_salon' : 'stock_taller';
    }
}

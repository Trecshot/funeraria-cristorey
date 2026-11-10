<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Urna extends Model
{
    use HasFactory;

    public const CATEGORIAS_ETARIAS = ['Niño', 'Juvenil', 'Adulto', 'Especial'];
    public const UBICACIONES = ['salon', 'taller'];

    protected $table = 'urnas';

    protected $fillable = [
        'codigo',
        'modelo',
        'color',
        'tallado',
        'categoria_etaria',
        'medidas_cm',
        'personalizada',
        'precio',
        'stock_salon',
        'stock_taller',
        'stock_minimo',
        'mostrar_catalogo',
        'publicado',
    ];

    protected $attributes = [
        'categoria_etaria' => 'Adulto',
        'medidas_cm' => '1.90m x 60cm',
        'precio' => 0,
        'stock_salon' => 0,
        'stock_taller' => 0,
        'stock_minimo' => 1,
        'mostrar_catalogo' => true,
        'publicado' => true,
        'personalizada' => false,
    ];

    protected function casts(): array
    {
        return [
            'personalizada' => 'boolean',
            'mostrar_catalogo' => 'boolean',
            'publicado' => 'boolean',
            'precio' => 'decimal:2',
            'stock_salon' => 'integer',
            'stock_taller' => 'integer',
            'stock_minimo' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Urna $urna): void {
            if (empty($urna->codigo)) {
                $prefijo = match ($urna->categoria_etaria) {
                    'Niño' => 'URN-NN',
                    'Juvenil' => 'URN-JV',
                    'Especial' => 'URN-XL',
                    default => 'URN-AD',
                };
                $urna->codigo = sprintf('%s-%s', $prefijo, strtoupper(substr(uniqid(), -4)));
            }
        });
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(MovimientoBodega::class, 'producto_bodega_id');
    }

    public function scopeStockCritico(Builder $query): Builder
    {
        return $query->whereRaw('(stock_salon + stock_taller) <= stock_minimo');
    }

    public function scopePublicadosConStock(Builder $query): Builder
    {
        return $query->where(function (Builder $q): void {
            $q->where('mostrar_catalogo', true)
                ->orWhere('publicado', true);
        })->where(function (Builder $q): void {
            $q->where('stock_salon', '>', 0)
                ->orWhere('stock_taller', '>', 0);
        });
    }

    public function getCodigoAttribute(?string $value): string
    {
        if (! empty($value)) {
            return $value;
        }

        return 'URN-'.str_pad((string) ($this->id ?? 0), 3, '0', STR_PAD_LEFT);
    }

    public function getStockTotalAttribute(): int
    {
        return (int) $this->stock_salon + (int) $this->stock_taller;
    }

    public function getStockCriticoAttribute(): bool
    {
        return $this->stock_total <= (int) $this->stock_minimo;
    }

    public function getEstadoBadgeAttribute(): array
    {
        if ($this->stock_total <= 0) {
            return [
                'label' => 'Sin existencias',
                'class' => 'bg-rose-100 text-rose-900 border border-rose-200',
                'icon' => 'fa-circle-xmark',
            ];
        }

        if ($this->stock_total <= $this->stock_minimo) {
            return [
                'label' => 'Stock crítico',
                'class' => 'bg-amber-100 text-amber-900 border border-amber-200',
                'icon' => 'fa-triangle-exclamation',
            ];
        }

        if ($this->stock_salon <= 0 && $this->stock_taller > 0) {
            return [
                'label' => 'Crítico (Salón)',
                'class' => 'bg-orange-100 text-orange-900 border border-orange-200',
                'icon' => 'fa-dolly',
            ];
        }

        return [
            'label' => 'Óptimo',
            'class' => 'bg-emerald-100 text-emerald-900 border border-emerald-200',
            'icon' => 'fa-circle-check',
        ];
    }

    public function getPrecioFormateadoAttribute(): string
    {
        return '$'.number_format((float) $this->precio, 0, ',', '.');
    }

    public function getColorTalladoAttribute(): string
    {
        $color = $this->color ?: 'Natural';
        $tallado = $this->tallado ?: ($this->personalizada ? 'Personalizada' : 'Liso');

        return "{$color} / {$tallado}";
    }

    public function stockColumn(string $ubicacion): string
    {
        return $ubicacion === 'salon' ? 'stock_salon' : 'stock_taller';
    }
}

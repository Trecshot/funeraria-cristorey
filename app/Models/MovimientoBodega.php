<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovimientoBodega extends Model
{
    protected $table = 'movimientos_bodega';

    protected $fillable = [
        'producto_bodega_id', 'urna_id', 'user_id', 'origen', 'destino', 'cantidad',
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Urna::class, 'producto_bodega_id');
    }

    public function urna(): BelongsTo
    {
        return $this->belongsTo(Urna::class, 'producto_bodega_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

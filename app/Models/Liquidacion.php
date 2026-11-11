<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Liquidacion extends Model
{
    protected $table = 'liquidaciones';

    public const ESTADOS_SUBVENCION = [
        'tramitacion' => 'En tramitación',
        'aprobado' => 'Aprobado',
        'rechazado' => 'Rechazado',
        'liquidado' => 'Liquidado',
    ];

    public const ESTADOS_COPAGO = [
        'pendiente' => 'Pendiente',
        'abonado' => 'Abonado',
        'pagado_total' => 'Pagado total',
    ];

    public const METODOS_PAGO = [
        'Transferencia' => 'Transferencia bancaria',
        'Transbank' => 'Transbank / Tarjeta débito o crédito',
        'Efectivo' => 'Efectivo',
        'Cheque' => 'Cheque al día',
    ];

    protected $fillable = [
        'servicio_id',
        'folio_factura',
        'folio_boleta',
        'monto_total',
        'monto_subvencion',
        'monto_copago_familia',
        'monto_copago_pagado',
        'estado_subvencion',
        'estado_copago',
        'metodo_pago_familia',
        'fecha_liquidacion',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'monto_total' => 'integer',
            'monto_subvencion' => 'integer',
            'monto_copago_familia' => 'integer',
            'monto_copago_pagado' => 'integer',
            'fecha_liquidacion' => 'datetime',
        ];
    }

    public function servicio(): BelongsTo
    {
        return $this->belongsTo(Servicio::class, 'servicio_id');
    }

    public function saldoCopagoPendiente(): int
    {
        return max(0, (int) $this->monto_copago_familia - (int) $this->monto_copago_pagado);
    }

    public function estaTotalmenteLiquidado(): bool
    {
        return $this->estado_subvencion === 'liquidado' && $this->estado_copago === 'pagado_total';
    }

    public function estadoSubvencionLabel(): string
    {
        return self::ESTADOS_SUBVENCION[$this->estado_subvencion] ?? ucfirst((string) $this->estado_subvencion);
    }

    public function estadoCopagoLabel(): string
    {
        return self::ESTADOS_COPAGO[$this->estado_copago] ?? ucfirst((string) $this->estado_copago);
    }

    public function badgeSubvencionClass(): string
    {
        return match ($this->estado_subvencion) {
            'liquidado' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
            'aprobado' => 'bg-blue-100 text-blue-800 border-blue-200',
            'rechazado' => 'bg-rose-100 text-rose-800 border-rose-200',
            default => 'bg-amber-100 text-amber-800 border-amber-200',
        };
    }

    public function badgeCopagoClass(): string
    {
        return match ($this->estado_copago) {
            'pagado_total' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
            'abonado' => 'bg-indigo-100 text-indigo-800 border-indigo-200',
            default => 'bg-rose-100 text-rose-800 border-rose-200',
        };
    }
}

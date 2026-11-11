<?php

namespace App\Models;

use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CuotaPago extends Model
{
    protected $table = 'cuotas_pago';

    public const ESTADO_PENDIENTE = 'pendiente';
    public const ESTADO_PAGADA = 'pagada';
    public const ESTADO_ATRASADA = 'atrasada';

    public const ESTADOS = [
        self::ESTADO_PENDIENTE => 'Pendiente',
        self::ESTADO_PAGADA => 'Pagada',
        self::ESTADO_ATRASADA => 'No Pagada (Atrasada)',
    ];

    protected $fillable = [
        'liquidacion_id',
        'numero_cuota',
        'monto_cuota',
        'fecha_vencimiento',
        'fecha_pago',
        'estado',
        'metodo_pago',
        'comprobante_referencia',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'numero_cuota' => 'integer',
            'monto_cuota' => 'integer',
            'fecha_vencimiento' => 'date',
            'fecha_pago' => 'date',
        ];
    }

    public function liquidacion(): BelongsTo
    {
        return $this->belongsTo(Liquidacion::class, 'liquidacion_id');
    }

    public function estaAtrasada(): bool
    {
        if ($this->estado === self::ESTADO_PAGADA) {
            return false;
        }

        if ($this->estado === self::ESTADO_ATRASADA) {
            return true;
        }

        return $this->fecha_vencimiento && $this->fecha_vencimiento < Carbon::today();
    }

    public function estadoLabel(): string
    {
        if ($this->estado === self::ESTADO_PAGADA) {
            return 'Pagada';
        }

        if ($this->estaAtrasada()) {
            return 'No Pagada (Atrasada)';
        }

        return 'Pendiente';
    }

    public function badgeClass(): string
    {
        if ($this->estado === self::ESTADO_PAGADA) {
            return 'bg-emerald-100 text-emerald-800 border-emerald-300';
        }

        if ($this->estaAtrasada()) {
            return 'bg-rose-100 text-rose-800 border-rose-300';
        }

        return 'bg-slate-100 text-slate-700 border-slate-300';
    }

    public function montoFormateado(): string
    {
        return '$'.number_format($this->monto_cuota, 0, ',', '.').' CLP';
    }

    public function marcarComoPagada(?string $metodoPago = null, mixed $fechaPago = null, ?string $comprobante = null): void
    {
        $this->update([
            'estado' => self::ESTADO_PAGADA,
            'fecha_pago' => $fechaPago instanceof DateTimeInterface ? $fechaPago : Carbon::today(),
            'metodo_pago' => $metodoPago ?: ($this->metodo_pago ?: 'Transferencia'),
            'comprobante_referencia' => $comprobante ?: $this->comprobante_referencia,
        ]);

        $this->liquidacion->recalcularEstados();
    }
}

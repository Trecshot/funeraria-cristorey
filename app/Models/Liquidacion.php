<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Liquidacion extends Model
{
    protected $table = 'liquidaciones';

    public const ESTADO_GENERAL_PENDIENTE = 'pendiente';
    public const ESTADO_GENERAL_AL_DIA = 'al_dia';
    public const ESTADO_GENERAL_MOROSO = 'moroso';
    public const ESTADO_GENERAL_PAGADO_TOTAL = 'pagado_total';

    public const ESTADOS_GENERAL = [
        self::ESTADO_GENERAL_PENDIENTE => 'Pendiente',
        self::ESTADO_GENERAL_AL_DIA => 'Al Día',
        self::ESTADO_GENERAL_MOROSO => 'Moroso',
        self::ESTADO_GENERAL_PAGADO_TOTAL => 'Pagado Total',
    ];

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
        'estado_general',
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

    public function cuotas(): HasMany
    {
        return $this->hasMany(CuotaPago::class, 'liquidacion_id')->orderBy('numero_cuota');
    }

    public function estadoGeneralLabel(): string
    {
        return match ($this->estado_general) {
            self::ESTADO_GENERAL_MOROSO => '⚠️ Moroso',
            self::ESTADO_GENERAL_AL_DIA => '✓ Al Día',
            self::ESTADO_GENERAL_PAGADO_TOTAL => '✓ Pagado Total',
            default => 'Pendiente',
        };
    }

    public function badgeEstadoGeneralClass(): string
    {
        return match ($this->estado_general) {
            self::ESTADO_GENERAL_MOROSO => 'bg-rose-100 text-rose-900 border-rose-300',
            self::ESTADO_GENERAL_AL_DIA => 'bg-emerald-100 text-emerald-800 border-emerald-300',
            self::ESTADO_GENERAL_PAGADO_TOTAL => 'bg-emerald-100 text-emerald-800 border-emerald-300',
            default => 'bg-amber-100 text-amber-900 border-amber-300',
        };
    }

    public function resumenMorosidad(): string
    {
        $atrasadas = $this->cuotas->filter(fn (CuotaPago $cuota) => $cuota->estaAtrasada())->count();
        if ($atrasadas > 0) {
            return "({$atrasadas} cuota" . ($atrasadas > 1 ? 's' : '') . ' atrasada' . ($atrasadas > 1 ? 's' : '') . ')';
        }

        if ($this->estado_general === self::ESTADO_GENERAL_PAGADO_TOTAL) {
            return '(Deuda saldada)';
        }

        if ($this->estado_general === self::ESTADO_GENERAL_AL_DIA) {
            return '(Al día con pagos)';
        }

        return '(Sin pagos registrados)';
    }

    public function recalcularEstados(): void
    {
        $cuotas = $this->cuotas()->get();
        if ($cuotas->isEmpty()) {
            return;
        }

        $totalPagado = $cuotas->where('estado', CuotaPago::ESTADO_PAGADA)->sum('monto_cuota');
        $hayAtrasada = $cuotas->contains(fn (CuotaPago $cuota) => $cuota->estaAtrasada());
        $todasPagadas = $cuotas->every(fn (CuotaPago $cuota) => $cuota->estado === CuotaPago::ESTADO_PAGADA);

        $estadoGeneral = match (true) {
            $todasPagadas || ($this->monto_copago_familia > 0 && $totalPagado >= $this->monto_copago_familia) => self::ESTADO_GENERAL_PAGADO_TOTAL,
            $hayAtrasada => self::ESTADO_GENERAL_MOROSO,
            $totalPagado > 0 => self::ESTADO_GENERAL_AL_DIA,
            default => self::ESTADO_GENERAL_PENDIENTE,
        };

        $estadoCopago = match (true) {
            $todasPagadas || ($this->monto_copago_familia > 0 && $totalPagado >= $this->monto_copago_familia) => 'pagado_total',
            $totalPagado > 0 => 'abonado',
            default => 'pendiente',
        };

        $this->update([
            'monto_copago_pagado' => $totalPagado,
            'estado_general' => $estadoGeneral,
            'estado_copago' => $estadoCopago,
        ]);
    }

    public function generarPlanCuotas(int $cuotasTotal = 2, ?Carbon $primerVencimiento = null): void
    {
        if ($this->cuotas()->exists() || $this->monto_copago_familia <= 0) {
            return;
        }

        $cuotasTotal = max(1, $cuotasTotal);
        $montoCuota = (int) floor($this->monto_copago_familia / $cuotasTotal);
        $fechaBase = $primerVencimiento ?: Carbon::today()->subDays(6);

        for ($i = 1; $i <= $cuotasTotal; $i++) {
            $vencimiento = (clone $fechaBase)->addMonths($i - 1);
            $estaAtrasada = $vencimiento < Carbon::today();
            $monto = ($i === $cuotasTotal)
                ? ($this->monto_copago_familia - ($montoCuota * ($cuotasTotal - 1)))
                : $montoCuota;

            $this->cuotas()->create([
                'numero_cuota' => $i,
                'monto_cuota' => $monto,
                'fecha_vencimiento' => $vencimiento,
                'estado' => $estaAtrasada ? CuotaPago::ESTADO_ATRASADA : CuotaPago::ESTADO_PENDIENTE,
            ]);
        }

        $this->recalcularEstados();
    }
}

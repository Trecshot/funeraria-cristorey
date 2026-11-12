<?php

namespace App\Models\Concerns;

use App\Models\Liquidacion;
use App\Services\IndicadoresService;

trait CalculaAportesPrevisionales
{
    public function getCopagoEstimadoAttribute(): ?int
    {
        $copago = $this->copagoEstimado();

        return $copago !== null ? (int) round($copago) : null;
    }

    public function getAporteConvenioEstimadoAttribute(): ?int
    {
        $aporte = $this->aporteConvenio();

        return $aporte !== null ? (int) round($aporte) : null;
    }

    public function aporteConvenio(?float $valorUf = null): ?float
    {
        $convenio = strtolower((string) $this->convenio);

        if ($convenio === 'particular') {
            return 0.0;
        }

        if ($convenio === 'ips') {
            return (float) self::APORTE_IPS_CLP;
        }

        if ($convenio === 'afp') {
            $uf = $valorUf ?: ($this->uf_value_clp ?: app(IndicadoresService::class)->getValorUF());
            if (! $uf) {
                return null;
            }

            return (float) round(self::APORTE_AFP_UF * $uf);
        }

        if (in_array($convenio, ['dipreca', 'sindicato'], true)) {
            return $this->aporte_convenio > 0 ? (float) $this->aporte_convenio : null;
        }

        return (float) ($this->aporte_convenio ?: 0);
    }

    public function copagoEstimado(?float $valorUf = null): ?float
    {
        $aporte = $this->aporteConvenio($valorUf);
        if ($aporte === null) {
            return null;
        }

        return (float) max(0, (int) $this->monto_servicio - (int) round($aporte));
    }

    public function calcularAportes(?float $valorUf = null): void
    {
        $convenio = strtolower((string) $this->convenio);
        $ufValue = null;

        if ($convenio === 'afp') {
            $ufValue = (int) round((float) ($valorUf ?: ($this->uf_value_clp ?: app(IndicadoresService::class)->getValorUF())));
        }

        $aporte = match ($convenio) {
            'ips' => self::APORTE_IPS_CLP,
            'afp' => $ufValue ? (int) round(self::APORTE_AFP_UF * $ufValue) : 0,
            default => (int) ($this->aporte_convenio ?: 0),
        };

        $convenioConAporteConocido = in_array($convenio, ['particular', 'ips'], true)
            || ($convenio === 'afp' && $ufValue);

        $this->uf_value_clp = $ufValue;
        $this->aporte_convenio = $aporte;
        $this->copago = $convenioConAporteConocido
            ? max(0, (int) $this->monto_servicio - $aporte)
            : (int) $this->monto_servicio;
    }

    public function obtenerOCrearLiquidacion(?float $valorUf = null): Liquidacion
    {
        $aporte = (int) round($this->aporteConvenio($valorUf) ?? 0);
        $copago = (int) round($this->copagoEstimado($valorUf) ?? $this->monto_servicio);

        $liquidacion = $this->relationLoaded('liquidacion') ? $this->getRelation('liquidacion') : $this->liquidacion;

        if (! $liquidacion) {
            $liquidacion = $this->liquidacion()->create([
                'monto_total' => (int) $this->monto_servicio,
                'monto_subvencion' => $aporte,
                'monto_copago_familia' => $copago,
                'monto_copago_pagado' => 0,
                'estado_subvencion' => 'tramitacion',
                'estado_copago' => 'pendiente',
                'folio_factura' => 'FE-'.str_pad((string) $this->id, 5, '0', STR_PAD_LEFT),
                'folio_boleta' => 'BE-'.str_pad((string) $this->id, 5, '0', STR_PAD_LEFT),
            ]);
            $this->setRelation('liquidacion', $liquidacion);
        } else {
            if ($liquidacion->monto_total === 0 || $liquidacion->estado_subvencion === 'tramitacion') {
                $liquidacion->monto_total = (int) $this->monto_servicio;
                $liquidacion->monto_subvencion = $aporte;
                $liquidacion->monto_copago_familia = $copago;
                if (! $liquidacion->folio_factura) {
                    $liquidacion->folio_factura = 'FE-'.str_pad((string) $this->id, 5, '0', STR_PAD_LEFT);
                }
                if (! $liquidacion->folio_boleta) {
                    $liquidacion->folio_boleta = 'BE-'.str_pad((string) $this->id, 5, '0', STR_PAD_LEFT);
                }
                $liquidacion->save();
            }
        }

        if ($liquidacion->cuotas()->doesntExist() && $liquidacion->monto_copago_familia > 0) {
            $liquidacion->generarPlanCuotas(2);
        }

        return $liquidacion;
    }
}

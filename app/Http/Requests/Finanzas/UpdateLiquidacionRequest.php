<?php

namespace App\Http\Requests\Finanzas;

use App\Models\Liquidacion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLiquidacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        /** @var \App\Models\Servicio|null $servicio */
        $servicio = $this->route('servicio');
        $liquidacion = $servicio?->obtenerOCrearLiquidacion();
        $copagoAcordado = (int) ($liquidacion?->monto_copago_familia ?? 0);
        $maxPermitido = $copagoAcordado > 0
            ? $copagoAcordado
            : max((int) ($servicio?->monto_servicio ?? 0), 0);

        return [
            'folio_factura' => ['nullable', 'string', 'max:50'],
            'folio_boleta' => ['nullable', 'string', 'max:50'],
            'estado_subvencion' => ['required', Rule::in(array_keys(Liquidacion::ESTADOS_SUBVENCION))],
            'estado_copago' => ['required', Rule::in(array_keys(Liquidacion::ESTADOS_COPAGO))],
            'metodo_pago_familia' => ['nullable', 'string', 'max:100'],
            'monto_copago_pagado' => ['nullable', 'integer', 'min:0', 'max:'.($maxPermitido > 0 ? $maxPermitido : 999999999)],
            'observaciones' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'monto_copago_pagado.min' => 'El monto abonado no puede ser negativo.',
            'monto_copago_pagado.max' => 'El monto abonado excede el copago familiar acordado para este expediente.',
            'monto_copago_pagado.integer' => 'El monto abonado debe ser un valor entero en pesos (CLP).',
        ];
    }
}

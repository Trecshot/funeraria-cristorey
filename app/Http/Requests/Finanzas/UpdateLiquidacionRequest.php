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
        return [
            'folio_factura' => ['nullable', 'string', 'max:50'],
            'folio_boleta' => ['nullable', 'string', 'max:50'],
            'estado_subvencion' => ['required', Rule::in(array_keys(Liquidacion::ESTADOS_SUBVENCION))],
            'estado_copago' => ['required', Rule::in(array_keys(Liquidacion::ESTADOS_COPAGO))],
            'metodo_pago_familia' => ['nullable', 'string', 'max:100'],
            'monto_copago_pagado' => ['nullable', 'integer', 'min:0'],
            'observaciones' => ['nullable', 'string', 'max:1000'],
        ];
    }
}

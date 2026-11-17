<?php

namespace App\Http\Requests\Servicio;

use Illuminate\Foundation\Http\FormRequest;

class PagarCuotaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'metodo_pago' => ['required', 'string', 'max:50'],
            'comprobante_referencia' => ['nullable', 'string', 'max:100'],
            'observaciones' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'metodo_pago.required' => 'Debes seleccionar un método de pago para registrar el abono.',
            'comprobante_referencia.max' => 'La referencia del comprobante no puede superar los 100 caracteres.',
            'observaciones.max' => 'Las observaciones del pago no pueden superar los 500 caracteres.',
        ];
    }
}

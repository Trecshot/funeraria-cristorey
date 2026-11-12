<?php

namespace App\Http\Requests\Flota;

use App\Models\Vehiculo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEstadoVehiculoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'estado' => ['required', Rule::in(Vehiculo::ESTADOS)],
            'kilometraje' => ['nullable', 'integer', 'min:0'],
            'apto_rural' => ['nullable', 'boolean'],
        ];
    }
}

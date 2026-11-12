<?php

namespace App\Http\Requests\Flota;

use App\Models\Chofer;
use App\Models\Vehiculo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVehiculoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $modeloInput = $this->input('modelo_uso') ?: $this->input('modelo');
        $this->merge(['modelo_uso' => $modeloInput]);
    }

    public function rules(): array
    {
        return [
            'patente' => ['required', 'string', 'max:20', 'unique:vehiculos,patente'],
            'modelo_uso' => ['required', 'string', 'max:150'],
            'apto_rural' => ['nullable', 'boolean'],
            'licencia_requerida' => ['nullable', Rule::in(Chofer::LICENCIAS)],
            'estado' => ['nullable', Rule::in(Vehiculo::ESTADOS)],
            'kilometraje' => ['nullable', 'integer', 'min:0'],
        ];
    }
}

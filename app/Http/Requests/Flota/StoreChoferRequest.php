<?php

namespace App\Http\Requests\Flota;

use App\Models\Chofer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreChoferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'nombre_completo' => ['required', 'string', 'max:150'],
            'rut' => ['required', 'string', 'max:20', 'unique:choferes,rut'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'licencia_clase' => ['required', Rule::in(Chofer::LICENCIAS)],
            'estado' => ['required', Rule::in(Chofer::ESTADOS)],
            'vehiculo_asignado_id' => ['nullable', 'integer', 'exists:vehiculos,id'],
        ];
    }
}

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
            'rut' => ['required', 'string', 'max:20', 'regex:/^[0-9]{1,2}\.?[0-9]{3}\.?[0-9]{3}-[0-9kK]$/', 'unique:choferes,rut'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'licencia_clase' => ['required', Rule::in(Chofer::LICENCIAS)],
            'estado' => ['required', Rule::in(Chofer::ESTADOS)],
            'vehiculo_asignado_id' => ['nullable', 'integer', 'exists:vehiculos,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'rut.regex' => 'El RUT del conductor debe tener un formato chileno válido (ej: 14.582.391-4).',
            'rut.unique' => 'Ya existe un conductor registrado con este RUT en la flota.',
        ];
    }
}

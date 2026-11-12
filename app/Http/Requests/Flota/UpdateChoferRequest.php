<?php

namespace App\Http\Requests\Flota;

use App\Models\Chofer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateChoferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'estado' => ['required', Rule::in(Chofer::ESTADOS)],
            'licencia_clase' => ['nullable', Rule::in(Chofer::LICENCIAS)],
            'vehiculo_asignado_id' => ['nullable', 'integer', 'exists:vehiculos,id'],
        ];
    }
}

<?php

namespace App\Http\Requests\Flota;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AsignarTrasladoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'vehiculo_id' => ['nullable', 'integer', 'exists:vehiculos,id'],
            'chofer_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where('role', 'chofer'),
            ],
        ];
    }
}

<?php

namespace App\Http\Requests\Flota;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEstadoTrasladoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'estado' => ['required', 'in:en_camino,completado'],
        ];
    }
}

<?php

namespace App\Http\Requests\Usuarios;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEstadoUsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'estado_operativo' => ['required', 'in:Disponible,En Ruta,Licencia Médica,Vacaciones,Suspendido'],
        ];
    }
}

<?php

namespace App\Http\Requests\Usuarios;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class StoreUsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return User::baseValidationRules() + [
            'password' => ['required', 'string', 'min:12'],
        ];
    }

    public function normalizedPayload(): array
    {
        $data = $this->validated();

        if (($data['role'] ?? null) !== 'chofer') {
            $data['tipo_licencia'] = null;
        }

        $data['sede_asignada'] = $data['sede_asignada'] ?? 'Calle Blanco';
        $data['estado_operativo'] = $data['estado_operativo'] ?? 'Disponible';
        $data['estado_cuenta'] = $data['estado_cuenta'] ?? 'activa';

        return $data;
    }
}

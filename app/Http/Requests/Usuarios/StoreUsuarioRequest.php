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
            'password' => ['required', 'string', 'min:12', 'regex:/^(?=.*[a-zA-Z])(?=.*[0-9]).+$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.unique' => 'Ya existe un usuario registrado con este correo electrónico.',
            'rut.unique' => 'Ya existe un usuario registrado con este RUT.',
            'rut.regex' => 'El RUT debe tener un formato chileno válido (ej: 15.345.678-9).',
            'password.min' => 'La contraseña debe tener al menos 12 caracteres.',
            'password.regex' => 'La contraseña debe incluir tanto letras como números.',
        ];
    }

    public function normalizedPayload(): array
    {
        $data = $this->validated();

        if (($data['role'] ?? null) !== 'chofer') {
            $data['tipo_licencia'] = null;
            if (($data['estado_operativo'] ?? null) === 'En Ruta') {
                $data['estado_operativo'] = 'Disponible';
            }
        }

        $data['sede_asignada'] = $data['sede_asignada'] ?? 'Calle Blanco';
        $data['estado_operativo'] = $data['estado_operativo'] ?? 'Disponible';
        $data['estado_cuenta'] = $data['estado_cuenta'] ?? 'activa';

        return $data;
    }
}

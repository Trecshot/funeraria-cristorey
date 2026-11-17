<?php

namespace App\Http\Requests\Usuarios;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $userId = $this->route('id');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'role' => ['required', Rule::in(User::validRoles())],
            'rut' => ['nullable', 'string', 'max:15', 'regex:/^[0-9]{1,2}\.?[0-9]{3}\.?[0-9]{3}-[0-9kK]$/', Rule::unique('users', 'rut')->ignore($userId)],
            'telefono' => ['nullable', 'string', 'max:25'],
            'sede_asignada' => ['nullable', Rule::in(array_keys(User::SEDES))],
            'tipo_licencia' => ['nullable', Rule::in(array_keys(User::TIPOS_LICENCIA))],
            'estado_operativo' => ['nullable', Rule::in(array_keys(User::ESTADOS_OPERATIVOS))],
            'estado_cuenta' => ['nullable', Rule::in(array_keys(User::ESTADOS_CUENTA))],
            'password' => ['nullable', 'string', 'min:12', 'regex:/^(?=.*[a-zA-Z])(?=.*[0-9]).+$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.unique' => 'Ya existe otro usuario registrado con este correo electrónico.',
            'rut.unique' => 'Ya existe otro usuario registrado con este RUT.',
            'rut.regex' => 'El RUT debe tener un formato chileno válido (ej: 15.345.678-9).',
            'password.min' => 'La nueva contraseña debe tener al menos 12 caracteres.',
            'password.regex' => 'La nueva contraseña debe incluir tanto letras como números.',
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

        if (empty($data['password'])) {
            unset($data['password']);
        }

        return $data;
    }
}

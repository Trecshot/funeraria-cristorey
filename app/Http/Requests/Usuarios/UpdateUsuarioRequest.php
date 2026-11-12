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
            'rut' => ['nullable', 'string', 'max:15', Rule::unique('users', 'rut')->ignore($userId)],
            'telefono' => ['nullable', 'string', 'max:25'],
            'sede_asignada' => ['nullable', Rule::in(array_keys(User::SEDES))],
            'tipo_licencia' => ['nullable', Rule::in(array_keys(User::TIPOS_LICENCIA))],
            'estado_operativo' => ['nullable', Rule::in(array_keys(User::ESTADOS_OPERATIVOS))],
            'estado_cuenta' => ['nullable', Rule::in(array_keys(User::ESTADOS_CUENTA))],
            'password' => ['nullable', 'string', 'min:12'],
        ];
    }

    public function normalizedPayload(): array
    {
        $data = $this->validated();

        if (($data['role'] ?? null) !== 'chofer') {
            $data['tipo_licencia'] = null;
        }

        if (empty($data['password'])) {
            unset($data['password']);
        }

        return $data;
    }
}

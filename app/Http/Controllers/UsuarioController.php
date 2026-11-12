<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UsuarioController extends Controller
{
    public function index(): View
    {
        return view('usuarios.index', [
            'usuarios' => User::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(User::baseValidationRules() + [
            'password' => ['required', 'string', 'min:12'],
        ]);

        if (($data['role'] ?? null) !== 'chofer') {
            $data['tipo_licencia'] = null;
        }

        $data['sede_asignada'] = $data['sede_asignada'] ?? 'Calle Blanco';
        $data['estado_operativo'] = $data['estado_operativo'] ?? 'Disponible';
        $data['estado_cuenta'] = $data['estado_cuenta'] ?? 'activa';

        User::create($data);

        return back()
            ->with('success', "Usuario {$data['name']} registrado correctamente con rol {$data['role']}.")
            ->with('status', "Usuario {$data['name']} registrado correctamente con rol {$data['role']}.");
    }

    /**
     * Actualiza dinámicamente el estado laboral o de cuenta del usuario.
     */
    public function actualizarEstado(Request $request, $id): RedirectResponse
    {
        $request->validate([
            'estado_operativo' => 'required|in:Disponible,En Ruta,Licencia Médica,Vacaciones,Suspendido',
        ]);

        $usuario = User::findOrFail($id);

        // Regla de Negocio: No se puede cambiar a "Vacaciones" si está "En Ruta" (o cualquier estado excepto Disponible)
        if ($usuario->estado_operativo === 'En Ruta' && $request->estado_operativo !== 'Disponible') {
            return back()->with('error', 'El chofer debe finalizar su ruta antes de cambiar su estado.');
        }

        $usuario->estado_operativo = $request->estado_operativo;
        $usuario->save();

        return back()
            ->with('success', 'Estado operativo actualizado correctamente.')
            ->with('status', 'Estado operativo actualizado correctamente.');
    }

    /**
     * Actualiza los datos maestros del usuario (Edición).
     */
    public function update(Request $request, $id): RedirectResponse
    {
        $usuario = User::findOrFail($id);

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($usuario->id)],
            'role' => ['required', Rule::in(User::validRoles())],
            'rut' => ['nullable', 'string', 'max:15', Rule::unique('users', 'rut')->ignore($usuario->id)],
            'telefono' => ['nullable', 'string', 'max:25'],
            'sede_asignada' => ['nullable', Rule::in(array_keys(User::SEDES))],
            'tipo_licencia' => ['nullable', Rule::in(array_keys(User::TIPOS_LICENCIA))],
            'estado_operativo' => ['nullable', Rule::in(array_keys(User::ESTADOS_OPERATIVOS))],
            'estado_cuenta' => ['nullable', Rule::in(array_keys(User::ESTADOS_CUENTA))],
            'password' => ['nullable', 'string', 'min:12'],
        ];

        $data = $request->validate($rules);

        if (($data['role'] ?? null) !== 'chofer') {
            $data['tipo_licencia'] = null;
        }

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $usuario->update($data);

        return back()
            ->with('success', "Datos del usuario {$usuario->name} actualizados correctamente.")
            ->with('status', "Datos del usuario {$usuario->name} actualizados correctamente.");
    }

    /**
     * Desactiva (Soft Delete) la cuenta del usuario para revocar su acceso.
     */
    public function destroy($id): RedirectResponse
    {
        $usuario = User::findOrFail($id);

        // Evitar que el administrador principal se elimine a sí mismo
        if (Auth::id() === $usuario->id) {
            return back()->with('error', 'Infracción de seguridad: No puede desactivar su propia cuenta.');
        }

        $usuario->delete(); // Aplica SoftDelete, no lo borra físicamente.

        return back()
            ->with('success', 'Usuario desactivado. El acceso al sistema ha sido revocado.')
            ->with('status', 'Usuario desactivado. El acceso al sistema ha sido revocado.');
    }
}

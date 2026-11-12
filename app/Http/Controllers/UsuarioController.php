<?php

namespace App\Http\Controllers;

use App\Http\Requests\Usuarios\StoreUsuarioRequest;
use App\Http\Requests\Usuarios\UpdateEstadoUsuarioRequest;
use App\Http\Requests\Usuarios\UpdateUsuarioRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class UsuarioController extends Controller
{
    public function index(): View
    {
        return view('usuarios.index', [
            'usuarios' => User::orderBy('name')->get(),
        ]);
    }

    public function store(StoreUsuarioRequest $request): RedirectResponse
    {
        $data = $request->normalizedPayload();
        User::create($data);

        return back()
            ->with('success', "Usuario {$data['name']} registrado correctamente con rol {$data['role']}.")
            ->with('status', "Usuario {$data['name']} registrado correctamente con rol {$data['role']}.");
    }

    /**
     * Actualiza dinámicamente el estado laboral o de cuenta del usuario.
     */
    public function actualizarEstado(UpdateEstadoUsuarioRequest $request, $id): RedirectResponse
    {
        $usuario = User::findOrFail($id);
        $nuevoEstado = $request->validated('estado_operativo');

        // Regla de Negocio: No se puede cambiar a otro estado si está "En Ruta", salvo pasar a "Disponible"
        if ($usuario->estado_operativo === 'En Ruta' && $nuevoEstado !== 'Disponible') {
            return back()->with('error', 'El chofer debe finalizar su ruta antes de cambiar su estado.');
        }

        $usuario->estado_operativo = $nuevoEstado;
        $usuario->save();

        return back()
            ->with('success', 'Estado operativo actualizado correctamente.')
            ->with('status', 'Estado operativo actualizado correctamente.');
    }

    /**
     * Actualiza los datos maestros del usuario (Edición).
     */
    public function update(UpdateUsuarioRequest $request, $id): RedirectResponse
    {
        $usuario = User::findOrFail($id);
        $usuario->update($request->normalizedPayload());

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

        if (Auth::id() === $usuario->id) {
            return back()->with('error', 'Infracción de seguridad: No puede desactivar su propia cuenta.');
        }

        $usuario->delete();

        return back()
            ->with('success', 'Usuario desactivado. El acceso al sistema ha sido revocado.')
            ->with('status', 'Usuario desactivado. El acceso al sistema ha sido revocado.');
    }
}

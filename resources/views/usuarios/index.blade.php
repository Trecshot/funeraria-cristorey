@extends('layouts.app')

@section('title', 'Usuarios RBAC')
@section('heading', 'Usuarios Registrados y Control de Roles (RBAC)')

@section('content')
    <div class="space-y-6">
        <section class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-100">
                <div>
                    <h3 class="font-serif-title text-xl font-bold text-slate-900">Usuarios Registrados y Control de Roles (RBAC)</h3>
                    <p class="text-xs text-slate-500 mt-1">Dotación de Funeraria Cristo Rey: gestión de permisos por rol operativo, turnos y delegación administrativa.</p>
                </div>
                <a href="#nuevo-usuario-form" class="bg-indigo-800 hover:bg-indigo-900 text-white text-xs font-bold px-4 py-2.5 rounded-xl self-start sm:self-center">
                    <i class="fa-solid fa-user-plus mr-1.5"></i>Nuevo Usuario
                </a>
            </div>

            <div class="overflow-x-auto mt-4">
                <table class="w-full text-left text-xs">
                    <thead class="bg-indigo-50/70 text-indigo-950 uppercase text-[10px]">
                        <tr>
                            <th class="p-3 rounded-l-xl">Nombre / Correo</th>
                            <th class="p-3">Rol en el Sistema (RBAC)</th>
                            <th class="p-3">Módulos Autorizados</th>
                            <th class="p-3">Estado</th>
                            <th class="p-3 rounded-r-xl">Control de Seguridad</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($usuarios as $usuario)
                            <tr>
                                <td class="p-3 font-bold text-slate-900">
                                    {{ $usuario->name }}
                                    <span class="block text-[10px] font-normal text-slate-400">{{ $usuario->email }}</span>
                                </td>
                                <td class="p-3">
                                    <span class="{{ $usuario->roleBadgeClass() }} px-2.5 py-0.5 rounded-full text-[10px] font-bold">
                                        {{ $usuario->roleLabel() }}
                                    </span>
                                </td>
                                <td class="p-3 text-slate-600">
                                    {{ $usuario->authorizedModulesDescription() }}
                                </td>
                                <td class="p-3">
                                    <span class="bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded-full font-bold text-[10px]">● Activo</span>
                                </td>
                                <td class="p-3">
                                    @if($usuario->canAccessFinances())
                                        <span class="text-emerald-700 font-semibold"><i class="fa-solid fa-shield-check mr-1"></i>Finanzas Autorizadas</span>
                                    @else
                                        <span class="text-rose-600 font-semibold"><i class="fa-solid fa-lock mr-1"></i>Finanzas Bloqueadas</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Formulario para registrar nuevo integrante en la BD -->
        <section id="nuevo-usuario-form" class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100">
            <h3 class="font-serif-title text-lg font-bold text-slate-900">Registrar Nuevo Integrante (RBAC)</h3>
            <p class="text-xs text-slate-500 mt-1">Las credenciales se almacenan con hash seguro y permisos restringidos según el rol asignado.</p>

            <form method="POST" action="{{ route('usuarios.store') }}" class="mt-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-xs">
                @csrf
                <label class="block">
                    <span class="label">Nombre completo *</span>
                    <input class="field" type="text" name="name" value="{{ old('name') }}" placeholder="Ej: Carlos Muñoz" required>
                </label>
                <label class="block">
                    <span class="label">Correo electrónico *</span>
                    <input class="field" type="email" name="email" value="{{ old('email') }}" placeholder="carlos@cristorey.cl" required>
                </label>
                <label class="block">
                    <span class="label">Rol operativo (RBAC) *</span>
                    <select class="field" name="role" required>
                        @foreach(\App\Models\User::ROLES as $roleKey => $roleMeta)
                            <option value="{{ $roleKey }}" @selected(old('role', 'asistente') === $roleKey)>{{ $roleMeta['form_label'] }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block">
                    <span class="label">Contraseña (mín. 12 caracteres) *</span>
                    <input class="field" type="password" name="password" minlength="12" placeholder="••••••••••••" required>
                </label>
                <div class="sm:col-span-2 lg:col-span-4 flex justify-end">
                    <button type="submit" class="button-primary">
                        <i class="fa-solid fa-user-check mr-1.5"></i>Crear Cuenta de Usuario
                    </button>
                </div>
            </form>
        </section>
    </div>
@endsection

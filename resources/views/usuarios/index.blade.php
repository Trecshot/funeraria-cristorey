@extends('layouts.app')

@section('title', 'Usuarios RBAC')
@section('heading', 'Usuarios Registrados y Control de Roles (RBAC)')

@section('content')
    <div class="space-y-6">
        <!-- TABLA PRINCIPAL DE USUARIOS -->
        <section class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-100">
                <div>
                    <h3 class="font-serif-title text-xl font-bold text-slate-900">Usuarios Registrados y Control de Roles (RBAC)</h3>
                    <p class="text-xs text-slate-500 mt-1">Dotación de Funeraria Cristo Rey: gestión de permisos por rol operativo, turnos, sedes y contacto directo.</p>
                </div>
                <a href="#nuevo-usuario-form" class="bg-indigo-800 hover:bg-indigo-900 text-white text-xs font-bold px-4 py-2.5 rounded-xl self-start sm:self-center transition shadow-sm">
                    <i class="fa-solid fa-user-plus mr-1.5"></i>Registrar Nuevo Integrante
                </a>
            </div>

            <div class="overflow-x-auto mt-4">
                <table class="w-full text-left text-xs">
                    <thead class="bg-indigo-50/70 text-indigo-950 uppercase text-[10px]">
                        <tr>
                            <th class="p-3.5 rounded-l-xl">Integrante / Documento</th>
                            <th class="p-3.5">Rol en el Sistema (RBAC)</th>
                            <th class="p-3.5">Contacto & Sede</th>
                            <th class="p-3.5">Estado Laboral & Cuenta</th>
                            <th class="p-3.5 rounded-r-xl">Seguridad y Permisos</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($usuarios as $usuario)
                            @php
                                $opMeta = $usuario->estadoOperativoMeta();
                                $ctaMeta = $usuario->estadoCuentaMeta();
                            @endphp
                            <tr class="hover:bg-slate-50/50 transition">
                                <!-- 1. Integrante / Documento -->
                                <td class="p-3.5">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-8 h-8 rounded-full bg-indigo-50 text-indigo-700 border border-indigo-200 flex items-center justify-center font-bold text-xs flex-shrink-0">
                                            {{ strtoupper(substr($usuario->name, 0, 2)) }}
                                        </div>
                                        <div class="min-w-0">
                                            <span class="font-bold text-slate-900 block truncate">{{ $usuario->name }}</span>
                                            <span class="text-[11px] text-slate-400 block truncate">{{ $usuario->email }}</span>
                                            @if($usuario->rut)
                                                <span class="inline-block text-[10px] text-slate-500 font-mono mt-0.5">RUT: {{ $usuario->rut }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                <!-- 2. Rol en el Sistema (RBAC) -->
                                <td class="p-3.5">
                                    <span class="{{ $usuario->roleBadgeClass() }} px-2.5 py-0.5 rounded-full text-[10px] font-bold inline-block">
                                        {{ $usuario->roleLabel() }}
                                    </span>
                                    @if($usuario->hasRole('chofer') && $usuario->tipo_licencia)
                                        <span class="block text-[10px] text-blue-700 font-semibold mt-1">
                                            <i class="fa-solid fa-id-card mr-1"></i>Licencia {{ $usuario->tipo_licencia }}
                                        </span>
                                    @endif
                                </td>

                                <!-- 3. Contacto & Sede (Reemplaza a Módulos Autorizados) -->
                                <td class="p-3.5">
                                    @if($usuario->telefono)
                                        <a href="tel:{{ preg_replace('/[^0-9+]/', '', $usuario->telefono) }}" class="inline-flex items-center text-xs font-semibold text-slate-800 hover:text-indigo-600 transition" title="Llamar directamente">
                                            <i class="fa-solid fa-phone text-emerald-600 mr-1.5 text-[10px]"></i>{{ $usuario->telefono }}
                                        </a>
                                    @else
                                        <span class="text-slate-400 italic text-[11px]">Sin teléfono</span>
                                    @endif
                                    <span class="block text-[11px] text-slate-500 mt-1">
                                        <i class="fa-solid fa-building text-indigo-500 mr-1 text-[10px]"></i>{{ $usuario->sedeLabel() }}
                                    </span>
                                </td>

                                <!-- 4. Estado Laboral & Cuenta -->
                                <td class="p-3.5">
                                    <div class="space-y-1">
                                        <div>
                                            <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $opMeta['badge'] }}">
                                                <i class="fa-solid fa-circle text-[7px] {{ $opMeta['dot'] }}"></i>{{ $opMeta['label'] }}
                                            </span>
                                        </div>
                                        <div class="text-[10px] text-slate-500 flex items-center gap-1">
                                            <i class="fa-solid fa-shield-halved text-[9px] {{ $usuario->estado_cuenta === 'activa' ? 'text-emerald-500' : 'text-rose-500' }}"></i>
                                            Cuenta: <strong class="{{ $usuario->estado_cuenta === 'activa' ? 'text-emerald-700' : 'text-rose-700' }}">{{ $ctaMeta['label'] }}</strong>
                                        </div>
                                    </div>
                                </td>

                                <!-- 5. Seguridad y Permisos -->
                                <td class="p-3.5">
                                    @if($usuario->canAccessFinances())
                                        <span class="text-emerald-700 font-semibold text-xs flex items-center">
                                            <i class="fa-solid fa-shield-check text-emerald-600 mr-1.5"></i>Finanzas Autorizadas
                                        </span>
                                    @else
                                        <span class="text-slate-400 font-medium text-xs flex items-center">
                                            <i class="fa-solid fa-lock text-slate-300 mr-1.5"></i>Finanzas Bloqueadas
                                        </span>
                                    @endif
                                    <span class="text-[10px] text-slate-400 block mt-0.5">
                                        {{ Str::limit($usuario->authorizedModulesDescription(), 30) }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <!-- FORMULARIO ESTRUCTURADO EN 2 COLUMNAS: REGISTRAR NUEVO INTEGRANTE -->
        <section id="nuevo-usuario-form" class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 space-y-4">
            <div class="border-b border-slate-100 pb-3">
                <h3 class="font-serif-title text-lg font-bold text-slate-900 flex items-center">
                    <i class="fa-solid fa-user-plus text-indigo-700 mr-2"></i>Registrar Nuevo Integrante (RBAC)
                </h3>
                <p class="text-xs text-slate-500 mt-1">
                    Separa las credenciales informáticas del perfil laboral del empleado para asegurar el control de accesos y la gestión operativa de turnos.
                </p>
            </div>

            <form method="POST" action="{{ route('usuarios.store') }}" class="space-y-6">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    
                    <!-- COLUMNA 1: CREDENCIALES DE ACCESO (SISTEMA) -->
                    <div class="bg-slate-50/60 p-4 rounded-2xl border border-slate-200/80 space-y-3.5">
                        <div class="flex items-center gap-2 border-b border-slate-200 pb-2">
                            <span class="w-6 h-6 rounded-md bg-indigo-100 text-indigo-800 flex items-center justify-center text-xs">
                                <i class="fa-solid fa-lock"></i>
                            </span>
                            <h4 class="font-bold text-xs text-slate-800 uppercase tracking-wide">Credenciales de Acceso (Sistema)</h4>
                        </div>

                        <label class="block">
                            <span class="label">Rol Operativo (RBAC) *</span>
                            <select id="select-role-usuario" name="role" class="field bg-white" required onchange="actualizarCamposPorRol(this.value)">
                                @foreach(\App\Models\User::ROLES as $roleKey => $roleMeta)
                                    <option value="{{ $roleKey }}" @selected(old('role', 'chofer') === $roleKey)>{{ $roleMeta['form_label'] }}</option>
                                @endforeach
                            </select>
                            <span class="text-[10px] text-slate-400 mt-0.5 block">Define el nivel de acceso a módulos y finanzas.</span>
                        </label>

                        <label class="block">
                            <span class="label">Nombre Completo *</span>
                            <input class="field bg-white" type="text" name="name" value="{{ old('name') }}" placeholder="Ej: Carlos Muñoz Valenzuela" required>
                        </label>

                        <label class="block">
                            <span class="label">Correo Electrónico (Login) *</span>
                            <input class="field bg-white" type="email" name="email" value="{{ old('email') }}" placeholder="carlos.munoz@cristorey.cl" required>
                        </label>

                        <label class="block">
                            <span class="label">Contraseña Temporal *</span>
                            <input class="field bg-white" type="password" name="password" minlength="12" placeholder="••••••••••••" required>
                            <span class="text-[10px] text-slate-400 mt-0.5 block">Mínimo 12 caracteres. El usuario podrá cambiarla al iniciar sesión.</span>
                        </label>
                    </div>

                    <!-- COLUMNA 2: DATOS DEL EMPLEADO (OPERATIVA) -->
                    <div class="bg-slate-50/60 p-4 rounded-2xl border border-slate-200/80 space-y-3.5">
                        <div class="flex items-center gap-2 border-b border-slate-200 pb-2">
                            <span class="w-6 h-6 rounded-md bg-emerald-100 text-emerald-800 flex items-center justify-center text-xs">
                                <i class="fa-solid fa-id-card"></i>
                            </span>
                            <h4 class="font-bold text-xs text-slate-800 uppercase tracking-wide">Datos del Empleado (Operativa)</h4>
                        </div>

                        <label class="block">
                            <span class="label">RUT / Documento de Identidad *</span>
                            <input class="field bg-white" type="text" name="rut" value="{{ old('rut') }}" placeholder="Ej: 15.345.678-9">
                        </label>

                        <label class="block">
                            <span class="label">Teléfono Celular de Contacto *</span>
                            <input class="field bg-white" type="text" name="telefono" value="{{ old('telefono') }}" placeholder="+56 9 8412 5590">
                            <span class="text-[10px] text-slate-400 mt-0.5 block">Para contacto operativo en ruta y despacho.</span>
                        </label>

                        <label class="block">
                            <span class="label">Sede Principal Asignada *</span>
                            <select name="sede_asignada" class="field bg-white" required>
                                @foreach(\App\Models\User::SEDES as $sedeKey => $sedeLabel)
                                    <option value="{{ $sedeKey }}" @selected(old('sede_asignada', 'Calle Blanco') === $sedeKey)>{{ $sedeLabel }}</option>
                                @endforeach
                            </select>
                        </label>

                        <!-- CAMPO CONDICIONAL: TIPO DE LICENCIA (SOLO PARA CHOFERES) -->
                        <div id="wrapper-tipo-licencia" class="space-y-1 transition-all duration-300">
                            <label class="block">
                                <span class="label flex items-center justify-between">
                                    <span>Tipo de Licencia de Conducir *</span>
                                    <span class="text-[10px] text-blue-600 font-semibold bg-blue-50 px-1.5 py-0.5 rounded">Exclusivo Chofer</span>
                                </span>
                                <select id="select-tipo-licencia" name="tipo_licencia" class="field bg-white">
                                    <option value="">Selecciona clase de licencia...</option>
                                    @foreach(\App\Models\User::TIPOS_LICENCIA as $licKey => $licLabel)
                                        <option value="{{ $licKey }}" @selected(old('tipo_licencia', 'A2') === $licKey)>{{ $licLabel }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <span class="text-[10px] text-slate-400 block">Requisito legal para conducción de carrozas y vehículos de traslado.</span>
                        </div>

                        <label class="block">
                            <span class="label">Estado Laboral Inicial</span>
                            <select name="estado_operativo" class="field bg-white">
                                @foreach(\App\Models\User::ESTADOS_OPERATIVOS as $opKey => $opMeta)
                                    <option value="{{ $opKey }}" @selected(old('estado_operativo', 'Disponible') === $opKey)>{{ $opMeta['label'] }}</option>
                                @endforeach
                            </select>
                        </label>
                    </div>

                </div>

                <div class="pt-4 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3">
                    <p class="text-[11px] text-slate-400">
                        <i class="fa-solid fa-circle-info text-indigo-500 mr-1"></i>Se crearán las credenciales y se vinculará la dotación física a la sede indicada.
                    </p>
                    <button type="submit" class="button-primary w-full sm:w-auto">
                        <i class="fa-solid fa-user-plus mr-1.5"></i>Crear Cuenta de Usuario
                    </button>
                </div>
            </form>
        </section>
    </div>
@endsection

@push('scripts')
<script>
    function actualizarCamposPorRol(rol) {
        const wrapperLicencia = document.getElementById('wrapper-tipo-licencia');
        const selectLicencia = document.getElementById('select-tipo-licencia');
        if (wrapperLicencia) {
            if (rol === 'chofer') {
                wrapperLicencia.classList.remove('hidden');
                if (selectLicencia && !selectLicencia.value) {
                    selectLicencia.value = 'A2';
                }
            } else {
                wrapperLicencia.classList.add('hidden');
            }
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        const selectRole = document.getElementById('select-role-usuario');
        if (selectRole) {
            actualizarCamposPorRol(selectRole.value);
        }
    });
</script>
@endpush

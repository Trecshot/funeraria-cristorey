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
                            <th class="p-3.5">Seguridad y Permisos</th>
                            <th class="p-3.5 rounded-r-xl text-center">Acciones</th>
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

                                <!-- 3. Contacto & Sede -->
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

                                <!-- 6. Acciones (Gestión) -->
                                <td class="p-3.5">
                                    <div class="flex flex-col gap-2 min-w-[170px]">
                                        <!-- Fila de Botones: Editar y Desactivar -->
                                        <div class="flex items-center gap-1.5">
                                            <!-- [ ✏️ Editar ] -->
                                            <button type="button"
                                                onclick="abrirModalEditar({{ json_encode([
                                                    'id' => $usuario->id,
                                                    'name' => $usuario->name,
                                                    'email' => $usuario->email,
                                                    'rut' => $usuario->rut,
                                                    'telefono' => $usuario->telefono,
                                                    'role' => $usuario->role,
                                                    'sede_asignada' => $usuario->sede_asignada,
                                                    'tipo_licencia' => $usuario->tipo_licencia,
                                                    'estado_operativo' => $usuario->estado_operativo,
                                                    'estado_cuenta' => $usuario->estado_cuenta,
                                                ]) }})"
                                                title="Editar datos de {{ $usuario->name }}"
                                                class="px-2.5 py-1 text-[11px] font-semibold text-indigo-700 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 rounded-lg transition flex items-center gap-1.5 shadow-sm">
                                                <i class="fa-solid fa-pen-to-square text-[10px]"></i>
                                                <span>Editar</span>
                                            </button>

                                            <!-- [ 🗑️ Desactivar ] -->
                                            @if(auth()->id() !== $usuario->id)
                                                <form method="POST" action="{{ route('usuarios.destroy', $usuario->id) }}" onsubmit="return confirm('¿Confirmas que deseas desactivar la cuenta de {{ $usuario->name }}? Se revocará su acceso al sistema.')" class="inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                        title="Desactivar usuario (Soft Delete)"
                                                        class="px-2.5 py-1 text-[11px] font-semibold text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-200 rounded-lg transition flex items-center gap-1.5 shadow-sm">
                                                        <i class="fa-solid fa-trash-can text-[10px]"></i>
                                                        <span>Desactivar</span>
                                                    </button>
                                                </form>
                                            @else
                                                <span title="Infracción de seguridad: No puede desactivar su propia cuenta" class="px-2.5 py-1 text-[11px] font-semibold text-slate-400 bg-slate-100 border border-slate-200 rounded-lg cursor-not-allowed flex items-center gap-1.5 opacity-60">
                                                    <i class="fa-solid fa-shield-halved text-[10px]"></i>
                                                    <span>Tú</span>
                                                </span>
                                            @endif
                                        </div>

                                        <!-- Menú desplegable / Selector: Cambiar Estado -->
                                        <form method="POST" action="{{ route('usuarios.estado', $usuario->id) }}" class="space-y-1">
                                            @csrf
                                            @method('PATCH')
                                            <div class="flex items-center text-[10px] text-slate-500 font-semibold">
                                                <span><i class="fa-solid fa-rotate text-indigo-500 mr-1 text-[9px]"></i>Cambiar Estado:</span>
                                            </div>
                                            <div class="relative">
                                                <select name="estado_operativo"
                                                    onchange="this.form.submit()"
                                                    class="w-full text-[11px] font-medium py-1 px-2 pr-6 border border-slate-200 rounded-lg bg-white text-slate-700 hover:border-indigo-400 focus:outline-none focus:ring-1 focus:ring-indigo-500 transition cursor-pointer appearance-none shadow-sm">
                                                    <option value="Disponible" @selected($usuario->estado_operativo === 'Disponible')>🟢 Disponible</option>
                                                    @if($usuario->hasRole('chofer') || $usuario->estado_operativo === 'En Ruta')
                                                        <option value="En Ruta" @selected($usuario->estado_operativo === 'En Ruta')>🟡 En Ruta</option>
                                                    @endif
                                                    <option value="Licencia Médica" @selected($usuario->estado_operativo === 'Licencia Médica')>🟣 Licencia Médica</option>
                                                    <option value="Vacaciones" @selected($usuario->estado_operativo === 'Vacaciones')>⚪ Vacaciones</option>
                                                    <option value="Suspendido" @selected($usuario->estado_operativo === 'Suspendido')>🔴 Suspendido</option>
                                                </select>
                                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-1.5 text-slate-400 text-[9px]">
                                                    <i class="fa-solid fa-chevron-down"></i>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
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

    <!-- MODAL: EDITAR DATOS MAESTROS DEL USUARIO -->
    <div id="modal-editar-usuario" class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="relative w-full max-w-2xl bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden transform transition-all">
            <!-- Header Modal -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 bg-slate-50/70">
                <div class="flex items-center gap-2">
                    <span class="w-8 h-8 rounded-xl bg-indigo-100 text-indigo-800 flex items-center justify-center text-sm">
                        <i class="fa-solid fa-user-pen"></i>
                    </span>
                    <div>
                        <h4 class="font-serif-title text-base font-bold text-slate-900">Editar Datos de Integrante (RBAC)</h4>
                        <p class="text-xs text-slate-500">Actualiza datos personales, rol laboral, permisos o sede asignada.</p>
                    </div>
                </div>
                <button type="button" onclick="cerrarModalEditar()" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-100 transition">
                    <i class="fa-solid fa-xmark text-base"></i>
                </button>
            </div>

            <!-- Formulario de Edición -->
            <form id="form-editar-usuario" method="POST" action="" class="p-6 space-y-6">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Columna 1: Credenciales y Rol -->
                    <div class="bg-slate-50/60 p-4 rounded-2xl border border-slate-200/80 space-y-3.5">
                        <div class="flex items-center gap-2 border-b border-slate-200 pb-2">
                            <span class="w-6 h-6 rounded-md bg-indigo-100 text-indigo-800 flex items-center justify-center text-xs">
                                <i class="fa-solid fa-lock"></i>
                            </span>
                            <h5 class="font-bold text-xs text-slate-800 uppercase tracking-wide">Credenciales y Rol</h5>
                        </div>

                        <label class="block">
                            <span class="label">Rol Operativo (RBAC) *</span>
                            <select id="edit-role" name="role" class="field bg-white" required onchange="actualizarCamposPorRolModal(this.value)">
                                @foreach(\App\Models\User::ROLES as $roleKey => $roleMeta)
                                    <option value="{{ $roleKey }}">{{ $roleMeta['form_label'] }}</option>
                                @endforeach
                            </select>
                        </label>

                        <label class="block">
                            <span class="label">Nombre Completo *</span>
                            <input id="edit-name" class="field bg-white" type="text" name="name" required>
                        </label>

                        <label class="block">
                            <span class="label">Correo Electrónico (Login) *</span>
                            <input id="edit-email" class="field bg-white" type="email" name="email" required>
                        </label>

                        <label class="block">
                            <span class="label">Nueva Contraseña (Opcional)</span>
                            <input id="edit-password" class="field bg-white" type="password" name="password" minlength="12" placeholder="Dejar en blanco para conservar">
                            <span class="text-[10px] text-slate-400 mt-0.5 block">Solo llenar si se desea cambiar la clave (mín. 12 car.).</span>
                        </label>
                    </div>

                    <!-- Columna 2: Datos Operativos y Turnos -->
                    <div class="bg-slate-50/60 p-4 rounded-2xl border border-slate-200/80 space-y-3.5">
                        <div class="flex items-center gap-2 border-b border-slate-200 pb-2">
                            <span class="w-6 h-6 rounded-md bg-emerald-100 text-emerald-800 flex items-center justify-center text-xs">
                                <i class="fa-solid fa-id-card"></i>
                            </span>
                            <h5 class="font-bold text-xs text-slate-800 uppercase tracking-wide">Datos Operativos</h5>
                        </div>

                        <label class="block">
                            <span class="label">RUT / Documento</span>
                            <input id="edit-rut" class="field bg-white" type="text" name="rut" placeholder="Ej: 15.345.678-9">
                        </label>

                        <label class="block">
                            <span class="label">Teléfono Celular</span>
                            <input id="edit-telefono" class="field bg-white" type="text" name="telefono" placeholder="+56 9 8412 5590">
                        </label>

                        <label class="block">
                            <span class="label">Sede Principal Asignada *</span>
                            <select id="edit-sede" name="sede_asignada" class="field bg-white" required>
                                @foreach(\App\Models\User::SEDES as $sedeKey => $sedeLabel)
                                    <option value="{{ $sedeKey }}">{{ $sedeLabel }}</option>
                                @endforeach
                            </select>
                        </label>

                        <!-- Campo condicional para chofer -->
                        <div id="wrapper-modal-licencia" class="space-y-1">
                            <label class="block">
                                <span class="label flex items-center justify-between">
                                    <span>Tipo de Licencia</span>
                                    <span class="text-[10px] text-blue-600 font-semibold bg-blue-50 px-1.5 py-0.5 rounded">Exclusivo Chofer</span>
                                </span>
                                <select id="edit-tipo-licencia" name="tipo_licencia" class="field bg-white">
                                    <option value="">Selecciona clase...</option>
                                    @foreach(\App\Models\User::TIPOS_LICENCIA as $licKey => $licLabel)
                                        <option value="{{ $licKey }}">{{ $licLabel }}</option>
                                    @endforeach
                                </select>
                            </label>
                        </div>

                        <div class="grid grid-cols-2 gap-2">
                            <label class="block">
                                <span class="label">Estado Laboral</span>
                                <select id="edit-estado-operativo" name="estado_operativo" class="field bg-white">
                                    @foreach(\App\Models\User::ESTADOS_OPERATIVOS as $opKey => $opMeta)
                                        <option value="{{ $opKey }}">{{ $opMeta['label'] }}</option>
                                    @endforeach
                                </select>
                            </label>

                            <label class="block">
                                <span class="label">Estado Cuenta</span>
                                <select id="edit-estado-cuenta" name="estado_cuenta" class="field bg-white">
                                    @foreach(\App\Models\User::ESTADOS_CUENTA as $ctaKey => $ctaMeta)
                                        <option value="{{ $ctaKey }}">{{ $ctaMeta['label'] }}</option>
                                    @endforeach
                                </select>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Footer Modal -->
                <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                    <button type="button" onclick="cerrarModalEditar()" class="px-4 py-2 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 text-xs font-semibold transition">
                        Cancelar
                    </button>
                    <button type="submit" class="button-primary">
                        <i class="fa-solid fa-floppy-disk mr-1.5"></i>Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
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

    function abrirModalEditar(data) {
        const modal = document.getElementById('modal-editar-usuario');
        const form = document.getElementById('form-editar-usuario');
        if (!modal || !form) return;

        form.action = `/usuarios/${data.id}`;
        document.getElementById('edit-name').value = data.name || '';
        document.getElementById('edit-email').value = data.email || '';
        document.getElementById('edit-rut').value = data.rut || '';
        document.getElementById('edit-telefono').value = data.telefono || '';
        document.getElementById('edit-role').value = data.role || 'chofer';
        document.getElementById('edit-sede').value = data.sede_asignada || 'Calle Blanco';
        document.getElementById('edit-tipo-licencia').value = data.tipo_licencia || '';
        document.getElementById('edit-estado-operativo').value = data.estado_operativo || 'Disponible';
        document.getElementById('edit-estado-cuenta').value = data.estado_cuenta || 'activa';
        document.getElementById('edit-password').value = '';

        actualizarCamposPorRolModal(data.role);

        modal.classList.remove('hidden');
    }

    function cerrarModalEditar() {
        const modal = document.getElementById('modal-editar-usuario');
        if (modal) {
            modal.classList.add('hidden');
        }
    }

    function actualizarCamposPorRolModal(rol) {
        const wrapper = document.getElementById('wrapper-modal-licencia');
        if (wrapper) {
            if (rol === 'chofer') {
                wrapper.classList.remove('hidden');
            } else {
                wrapper.classList.add('hidden');
            }
        }
    }

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            cerrarModalEditar();
        }
    });

    document.addEventListener('DOMContentLoaded', () => {
        const selectRole = document.getElementById('select-role-usuario');
        if (selectRole) {
            actualizarCamposPorRol(selectRole.value);
        }
    });
</script>
@endpush

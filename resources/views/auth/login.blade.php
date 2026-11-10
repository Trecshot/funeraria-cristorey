<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesión · Funeraria Cristo Rey</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="text-slate-800 min-h-screen">

    <div id="login-screen" class="fixed inset-0 z-50 flex items-center justify-center p-4 hero-banner">
        <div class="bg-white/95 backdrop-blur-md rounded-3xl shadow-2xl max-w-4xl w-full overflow-hidden grid grid-cols-1 md:grid-cols-2 border border-indigo-100">
            <!-- Lado Izquierdo Institucional -->
            <div class="sidebar-bg p-8 text-white flex flex-col justify-between relative overflow-hidden">
                <div class="text-center mt-4">
                    <div class="w-20 h-20 mx-auto mb-3 rounded-full border-2 border-indigo-300/40 flex items-center justify-center bg-indigo-950/60 shadow-inner">
                        <i class="fa-solid fa-cross text-3xl text-white"></i>
                    </div>
                    <h1 class="font-serif-title text-2xl font-bold tracking-wide text-white">Funeraria<br>Cristo Rey</h1>
                    <p class="text-xs text-indigo-200 mt-2 italic">“Un servicio con respeto, cercanía y profesionalismo”</p>
                </div>

                <div class="my-6 bg-indigo-950/60 border border-indigo-400/30 rounded-2xl p-4 text-xs space-y-2">
                    <p class="font-semibold text-indigo-200"><i class="fa-solid fa-shield-halved mr-1.5 text-emerald-400"></i>Seguridad RBAC Activa (ISO 27034)</p>
                    <p class="text-indigo-100/80 leading-relaxed">Acceso protegido mediante credenciales cifradas (Argon2/Bcrypt) y control de sesiones por rol operativo.</p>
                </div>

                <div class="text-center text-[11px] text-indigo-300">
                    Sede Central: Calle Blanco (Frente al Hospital), Temuco
                </div>
            </div>

            <!-- Lado Derecho Formulario de Ingreso -->
            <div class="p-8 flex flex-col justify-center">
                <div class="mb-6">
                    <span class="bg-indigo-100 text-indigo-800 text-[11px] font-bold px-3 py-1 rounded-full uppercase">Portal de Acceso Interno</span>
                    <h2 class="text-2xl font-bold text-slate-900 mt-2">Iniciar Sesión</h2>
                    <p class="text-xs text-slate-500">Ingrese sus credenciales o seleccione un perfil de prueba rápido:</p>
                </div>

                @if($errors->any())
                    <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 p-3 text-xs text-rose-800 font-medium" role="alert">
                        <i class="fa-solid fa-circle-exclamation mr-1"></i>{{ $errors->first() }}
                    </div>
                @endif

                <form id="login-form" method="POST" action="{{ route('login.store') }}" class="space-y-4 text-xs">
                    @csrf
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1" for="login-user">Correo o RUT de Usuario</label>
                        <div class="relative">
                            <i class="fa-solid fa-user absolute left-3.5 top-3 text-slate-400"></i>
                            <input type="email" id="login-user" name="email" value="{{ old('email', 'renato.osses@cristorey.cl') }}" required class="w-full pl-9 pr-4 py-2.5 border border-slate-300 rounded-xl focus:outline-none focus:border-indigo-600">
                        </div>
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1" for="login-pass">Contraseña</label>
                        <div class="relative">
                            <i class="fa-solid fa-lock absolute left-3.5 top-3 text-slate-400"></i>
                            <input type="password" id="login-pass" name="password" value="admin123" required class="w-full pl-9 pr-4 py-2.5 border border-slate-300 rounded-xl focus:outline-none focus:border-indigo-600">
                        </div>
                    </div>

                    <div class="flex items-center justify-between text-[11px] text-slate-500">
                        <label class="flex items-center space-x-1.5 cursor-pointer">
                            <input type="checkbox" name="remember" value="1" checked class="accent-indigo-600">
                            <span>Recordar equipo en oficina</span>
                        </label>
                        <a href="#" class="text-indigo-600 font-semibold hover:underline">¿Olvidó su clave?</a>
                    </div>

                    <button type="submit" class="w-full bg-indigo-900 hover:bg-indigo-800 text-white font-bold py-3 rounded-xl shadow-lg transition text-sm">
                        Ingresar al Panel de Control <i class="fa-solid fa-arrow-right ml-1.5"></i>
                    </button>

                    <div class="pt-4 border-t border-slate-200">
                        <p class="text-[11px] font-semibold text-slate-500 mb-2">Acceso Rápido para Demostración Docente:</p>
                        <div class="grid grid-cols-2 gap-2">
                            <button type="button" onclick="quickLogin('renato.osses@cristorey.cl', 'admin123')" class="border border-indigo-200 bg-indigo-50/70 hover:bg-indigo-100 text-indigo-900 py-2 px-3 rounded-lg font-semibold text-[11px] text-left">
                                <i class="fa-solid fa-user-tie mr-1 text-indigo-600"></i>Renato Osses (Admin)
                            </button>
                            <a href="{{ route('catalogo.publico') }}" class="border border-purple-200 bg-purple-50/70 hover:bg-purple-100 text-purple-900 py-2 px-3 rounded-lg font-semibold text-[11px] text-left flex items-center">
                                <i class="fa-solid fa-globe mr-1.5 text-purple-600"></i>Ir a Catálogo Web
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function quickLogin(email, pass) {
            document.getElementById('login-user').value = email;
            document.getElementById('login-pass').value = pass;
            document.getElementById('login-form').submit();
        }
    </script>
</body>
</html>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Panel') · Funeraria Cristo Rey</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="text-slate-800 min-h-screen">
    <div id="app-container" class="flex min-h-screen flex-col md:flex-row">

        <!-- SIDEBAR IZQUIERDO (ESTILO FIEL AL PROTOTIPO) -->
        <aside class="w-full md:w-64 sidebar-bg text-white flex-shrink-0 flex flex-col justify-between shadow-xl select-none">
            <div>
                <!-- Logo Superior con Cruz y Laureles -->
                <a href="{{ $user->homeRoute() }}" class="block pt-7 pb-5 px-6 text-center border-b border-indigo-800/50">
                    <div class="w-16 h-16 mx-auto mb-2 rounded-full border-2 border-white/70 flex items-center justify-center bg-white/5 shadow">
                        <i class="fa-solid fa-cross text-2xl text-white"></i>
                    </div>
                    <h1 class="font-serif-title text-xl font-bold leading-tight tracking-wide text-white">Funeraria<br>Cristo Rey</h1>
                </a>

                <!-- Menú de Navegación -->
                <nav class="p-4 space-y-1.5 text-sm">
                    @if(! $user->hasRole('chofer'))
                        <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'nav-link-active' : 'nav-link' }}">
                            <i class="fa-solid fa-house w-5 text-center"></i><span>Dashboard</span>
                        </a>
                    @endif

                    @if($user->hasRole('administrador', 'gerencia', 'asistente'))
                        <a href="{{ route('servicios.index') }}" class="{{ request()->routeIs('servicios.*') ? 'nav-link-active' : 'nav-link' }} justify-between">
                            <div class="flex items-center space-x-3.5"><i class="fa-solid fa-calendar-check w-5 text-center"></i><span>Servicios</span></div>
                            <i class="fa-solid fa-chevron-right text-xs opacity-60"></i>
                        </a>
                    @endif

                    @if($user->hasRole('administrador', 'gerencia'))
                        <a href="{{ route('usuarios.index') }}" class="{{ request()->routeIs('usuarios.*') ? 'nav-link-active' : 'nav-link' }} justify-between">
                            <div class="flex items-center space-x-3.5"><i class="fa-solid fa-users-gear w-5 text-center"></i><span>Usuarios (RBAC)</span></div>
                            <span class="bg-indigo-400/30 text-white text-[10px] px-1.5 py-0.5 rounded">{{ $userCount ?: 6 }}</span>
                        </a>
                    @endif

                    @if($user->hasRole('administrador', 'gerencia', 'asistente', 'bodega'))
                        <a href="{{ route('bodega.index') }}" class="{{ request()->routeIs('bodega.*') ? 'nav-link-active' : 'nav-link' }}">
                            <i class="fa-solid fa-box-archive w-5 text-center"></i><span>Urnas y Bodega</span>
                        </a>
                    @endif

                    @if($user->hasRole('administrador', 'gerencia', 'asistente'))
                        <a href="{{ route('flota.calendario') }}" class="{{ request()->routeIs('flota.*') ? 'nav-link-active' : 'nav-link' }} justify-between">
                            <div class="flex items-center space-x-3.5"><i class="fa-solid fa-car w-5 text-center"></i><span>Flota</span></div>
                            <i class="fa-solid fa-chevron-right text-xs opacity-60"></i>
                        </a>
                    @endif

                    @if($user->hasRole('administrador', 'gerencia', 'finanzas'))
                        <a href="{{ route('finanzas.index') }}" class="{{ request()->routeIs('finanzas.*') ? 'nav-link-active' : 'nav-link' }} justify-between">
                            <div class="flex items-center space-x-3.5"><i class="fa-solid fa-dollar-sign w-5 text-center"></i><span>Finanzas y AFP</span></div>
                            <i class="fa-solid fa-chevron-right text-xs opacity-60"></i>
                        </a>
                    @endif

                    @if($user->hasRole('administrador', 'gerencia', 'chofer'))
                        <a href="{{ route('movil.chofer') }}" class="{{ request()->routeIs('movil.*') ? 'nav-link-active' : 'nav-link' }} justify-between">
                            <div class="flex items-center space-x-3.5"><i class="fa-solid fa-route w-5 text-center"></i><span>Vista chofer</span></div>
                            <span class="bg-amber-400 text-indigo-950 text-[10px] font-bold px-2 py-0.5 rounded-full">Ruta</span>
                        </a>
                    @endif

                    @if(! $user->hasRole('chofer'))
                        <a href="{{ route('catalogo.publico') }}" target="_blank" rel="noopener" class="nav-link justify-between">
                            <div class="flex items-center space-x-3.5"><i class="fa-solid fa-globe w-5 text-center"></i><span>Catálogo público</span></div>
                            <i class="fa-solid fa-arrow-up-right-from-square text-[10px] opacity-60"></i>
                        </a>
                    @endif
                </nav>
            </div>

            <!-- Pie de Sidebar con Cerrar Sesión y Lirio -->
            <div class="p-4 border-t border-indigo-800/50 bg-indigo-950/50 backdrop-blur-sm">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full flex items-center space-x-3 px-4 py-2.5 rounded-xl hover:bg-rose-500/20 text-indigo-200 hover:text-rose-200 text-sm font-medium transition">
                        <i class="fa-solid fa-right-from-bracket"></i><span>Cerrar Sesión</span>
                    </button>
                </form>
            </div>
        </aside>

        <!-- CONTENIDO DERECHO -->
        <div class="flex-1 flex flex-col min-w-0">

            <!-- HEADER SUPERIOR BLANCO -->
            <header class="bg-white border-b border-slate-200 px-6 md:px-8 py-4 flex items-center justify-between sticky top-0 z-30 shadow-sm">
                <div class="flex items-center space-x-4">
                    <span class="text-slate-500 text-lg"><i class="fa-solid fa-bars"></i></span>
                    <div>
                        <h2 class="font-serif-title text-xl font-bold text-slate-900">Bienvenido, {{ $user->name }}</h2>
                        <p class="text-xs text-slate-500">@yield('heading', 'Panel de control') - Funeraria Cristo Rey (Sede Calle Blanco)</p>
                    </div>
                </div>

                <div class="flex items-center space-x-5">
                    @if($user->hasRole('administrador', 'gerencia', 'asistente'))
                        <a href="{{ route('servicios.index') }}" class="relative text-slate-600 hover:text-indigo-900 transition" title="Alertas Activas (Semáforo Documental)">
                            <i class="fa-solid fa-bell text-xl"></i>
                            <span class="absolute -top-1.5 -right-1.5 bg-rose-600 text-white text-[10px] font-bold w-4 h-4 rounded-full flex items-center justify-center">{{ $alertCount ?: 0 }}</span>
                        </a>
                    @endif

                    <!-- Perfil Usuario y Cierre Rápido de Sesión -->
                    <div class="flex items-center space-x-3 pl-3 border-l border-slate-200">
                        <div class="w-9 h-9 rounded-full bg-indigo-950 text-white flex items-center justify-center font-bold text-sm">
                            <i class="fa-solid fa-user"></i>
                        </div>
                        <div class="text-xs">
                            <p class="font-bold text-slate-800">{{ $user->name }}</p>
                            <p class="text-slate-500">{{ $user->roleLabel() }}</p>
                        </div>
                        <form method="POST" action="{{ route('logout') }}" class="pl-2">
                            @csrf
                            <button type="submit" title="Cerrar sesión e ir al Login" class="px-2.5 py-1.5 rounded-lg bg-slate-100 hover:bg-rose-50 text-slate-600 hover:text-rose-700 border border-slate-200 hover:border-rose-200 text-xs font-semibold transition flex items-center space-x-1">
                                <i class="fa-solid fa-right-from-bracket"></i>
                                <span class="hidden sm:inline">Salir</span>
                            </button>
                        </form>
                    </div>
                </div>
            </header>

            <!-- ÁREA DE CONTENIDO DINÁMICO -->
            <main class="p-5 md:p-7 space-y-6 flex-1 overflow-y-auto">

                <!-- BANNER HERO INSTITUCIONAL (CON LIRIO, FRASE Y FECHA COMO EN LAS FOTOS) -->
                <div class="hero-banner rounded-2xl p-6 text-white shadow-md flex flex-col md:flex-row items-center justify-between gap-4 relative overflow-hidden border border-indigo-200/30">
                    <div class="flex items-center space-x-6 z-10">
                        <img src="https://images.unsplash.com/photo-1508615039623-a25605d2b022?auto=format&fit=crop&w=240&q=80" alt="Lirio Blanco" class="w-28 h-20 object-cover rounded-xl border border-white/30 shadow hidden sm:block">
                        <div class="border-l-2 border-white/40 pl-5">
                            <h3 class="font-serif-title italic text-xl md:text-2xl font-normal tracking-wide text-white">“Un servicio con respeto, cercanía y profesionalismo”</h3>
                            <p class="text-xs text-indigo-100 mt-1">— Funeraria Cristo Rey · Temuco</p>
                        </div>
                    </div>
                    <div class="z-10 bg-indigo-950/70 backdrop-blur-md border border-white/20 px-4 py-2 rounded-xl text-xs flex items-center space-x-2 whitespace-nowrap">
                        <i class="fa-regular fa-calendar text-indigo-300"></i>
                        <span>{{ ucfirst(now()->translatedFormat('F Y')) }}</span>
                    </div>
                </div>

                @if(session('status') || session('success'))
                    <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs font-semibold text-emerald-800 flex items-center space-x-2 shadow-sm" role="status">
                        <i class="fa-solid fa-circle-check text-emerald-600"></i>
                        <span>{{ session('status') ?? session('success') }}</span>
                    </div>
                @endif

                @if(session('error'))
                    <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-xs font-semibold text-rose-800 flex items-center space-x-2 shadow-sm" role="alert">
                        <i class="fa-solid fa-circle-exclamation text-rose-600"></i>
                        <span>{{ session('error') }}</span>
                    </div>
                @endif

                @if($errors->any())
                    <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-xs text-rose-800 shadow-sm" role="alert">
                        <p class="font-bold"><i class="fa-solid fa-triangle-exclamation mr-1"></i>Revisa los datos ingresados:</p>
                        <ul class="mt-1 list-inside list-disc">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>
    @stack('scripts')
</body>
</html>

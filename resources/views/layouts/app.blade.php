<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Panel') · Funeraria Cristo Rey</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div class="min-h-screen md:flex">
        <aside class="flex w-full flex-col border-b border-stone-200 bg-white md:min-h-screen md:w-64 md:border-b-0 md:border-r">
            <a href="{{ route('dashboard') }}" class="border-b border-stone-100 px-6 py-5">
                <span class="block text-xs font-semibold uppercase tracking-[0.2em] text-amber-800">Funeraria</span>
                <span class="mt-1 block text-lg font-semibold text-stone-900">Cristo Rey</span>
            </a>
            <nav class="flex gap-1 overflow-x-auto p-3 md:flex-1 md:flex-col">
                @if(auth()->user()->role !== 'chofer')
                    <a class="nav-link" href="{{ route('dashboard') }}">Dashboard</a>
                @endif
                @if(in_array(auth()->user()->role, ['administrador', 'gerencia', 'asistente'], true))
                    <a class="nav-link" href="{{ route('servicios.index') }}">Servicios / Expedientes</a>
                @endif
                @if(in_array(auth()->user()->role, ['administrador', 'gerencia', 'asistente', 'bodega'], true))
                    <a class="nav-link" href="{{ route('bodega.index') }}">Bodega de urnas</a>
                @endif
                @if(in_array(auth()->user()->role, ['administrador', 'gerencia', 'asistente'], true))
                    <a class="nav-link" href="{{ route('flota.calendario') }}">Flota</a>
                @endif
                @if(in_array(auth()->user()->role, ['administrador', 'gerencia', 'finanzas'], true))
                    <a class="nav-link" href="{{ route('finanzas.index') }}">Finanzas</a>
                @endif
                @if(in_array(auth()->user()->role, ['administrador', 'gerencia', 'chofer'], true))
                    <a class="nav-link" href="{{ route('movil.chofer') }}">Vista chofer</a>
                @endif
                @if(auth()->user()->role !== 'chofer')
                    <a class="nav-link" href="{{ route('catalogo.publico') }}" target="_blank" rel="noopener">Catálogo público</a>
                @endif
            </nav>
            <div class="hidden border-t border-stone-100 p-4 md:block">
                <p class="truncate text-sm font-medium text-stone-800">{{ auth()->user()->name }}</p>
                <p class="text-xs text-stone-500">{{ auth()->user()->roleLabel() }}</p>
                <form method="POST" action="{{ route('logout') }}" class="mt-3">
                    @csrf
                    <button class="text-sm text-stone-600 hover:text-stone-900">Cerrar sesión</button>
                </form>
            </div>
        </aside>

        <main class="min-w-0 flex-1">
            <header class="flex items-center justify-between border-b border-stone-200 bg-white px-5 py-4 md:px-8">
                <div>
                    <p class="text-xs uppercase tracking-[0.16em] text-stone-500">Administración</p>
                    <h1 class="mt-1 text-xl">@yield('heading', 'Panel')</h1>
                </div>
                <div class="flex items-center gap-3 md:hidden">
                    <span class="text-right text-xs text-stone-600">{{ auth()->user()->name }}<br>{{ auth()->user()->roleLabel() }}</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="text-xs font-medium text-stone-700">Salir</button>
                    </form>
                </div>
            </header>
            <div class="mx-auto max-w-7xl space-y-6 p-4 md:p-8">
                @if(session('status'))
                    <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">{{ session('status') }}</div>
                @endif
                @if($errors->any())
                    <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
                        <p class="font-semibold">Revisa los datos ingresados:</p>
                        <ul class="mt-1 list-inside list-disc">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                    </div>
                @endif
                @yield('content')
            </div>
        </main>
    </div>
    @stack('scripts')
</body>
</html>
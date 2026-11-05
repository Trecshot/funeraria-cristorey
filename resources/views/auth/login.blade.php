<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Iniciar sesión · Funeraria Cristo Rey</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen items-center justify-center px-4 py-10">
    <main class="w-full max-w-md">
        <div class="panel p-8">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-amber-800">Funeraria</p>
            <h1 class="mt-2 text-2xl">Cristo Rey</h1>
            <p class="mt-2 text-sm text-stone-500">Ingresa con tu cuenta para continuar.</p>
            @if($errors->any())
                <div class="mt-5 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800" role="alert">{{ $errors->first() }}</div>
            @endif
            <form method="POST" action="{{ route('login.store') }}" class="mt-6 space-y-5">
                @csrf
                <label class="block">
                    <span class="label">Correo electrónico</span>
                    <input class="field" type="email" name="email" value="{{ old('email') }}" autocomplete="username" required autofocus>
                </label>
                <label class="block">
                    <span class="label">Contraseña</span>
                    <input class="field" type="password" name="password" autocomplete="current-password" required>
                </label>
                <label class="flex items-center gap-2 text-sm text-stone-600">
                    <input type="checkbox" name="remember" value="1" class="rounded border-stone-300 text-amber-800 focus:ring-amber-700">
                    Mantener sesión iniciada
                </label>
                <button class="button-primary w-full">Iniciar sesión</button>
            </form>
        </div>
    </main>
</body>
</html>
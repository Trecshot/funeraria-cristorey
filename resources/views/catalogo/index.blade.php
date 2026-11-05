<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Catálogo · Funeraria Cristo Rey</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <main class="mx-auto min-h-screen max-w-6xl px-4 py-10 md:px-8">
        <header class="border-b border-stone-200 pb-6">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-amber-800">Funeraria</p>
            <h1 class="mt-2 text-3xl">Cristo Rey</h1>
            <p class="mt-2 text-stone-600">Catálogo de modelos disponibles. Consulta por WhatsApp.</p>
        </header>
        <section class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @forelse($productos as $producto)
                <article class="panel">
                    <p class="text-xs font-semibold uppercase tracking-wide text-amber-800">Disponible</p>
                    <h2 class="mt-2 text-xl">{{ $producto->modelo }}</h2>
                    <dl class="mt-3 space-y-1 text-sm text-stone-600">
                        @if($producto->color)<div><dt class="inline">Color: </dt><dd class="inline">{{ $producto->color }}</dd></div>@endif
                        @if($producto->tallado)<div><dt class="inline">Tallado: </dt><dd class="inline">{{ $producto->tallado }}</dd></div>@endif
                    </dl>
                    <a class="button-primary mt-5 w-full" target="_blank" rel="noopener" href="https://wa.me/?text={{ urlencode('Hola, quisiera consultar por el modelo '.$producto->modelo.' de Funeraria Cristo Rey.') }}">Consultar por WhatsApp</a>
                </article>
            @empty
                <p class="panel text-sm text-stone-600 sm:col-span-2 lg:col-span-3">El catálogo se está actualizando. Contáctanos para consultar disponibilidad.</p>
            @endforelse
        </section>
        <footer class="mt-10 border-t border-stone-200 pt-5 text-sm text-stone-500">Funeraria Cristo Rey</footer>
    </main>
</body>
</html>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Catálogo · Funeraria Cristo Rey</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="text-slate-800 min-h-screen">
    <main class="mx-auto min-h-screen max-w-6xl px-4 py-8 md:px-8 space-y-6">
        <!-- Top Bar -->
        <header class="bg-white rounded-2xl px-6 py-4 shadow-sm border border-slate-100 flex flex-wrap justify-between items-center gap-4">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-full bg-indigo-950 text-white flex items-center justify-center">
                    <i class="fa-solid fa-cross"></i>
                </div>
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-indigo-800">Funeraria</p>
                    <h1 class="font-serif-title text-xl font-bold text-slate-900">Cristo Rey · Temuco</h1>
                </div>
            </div>
            <div class="flex items-center space-x-3 text-xs">
                <span class="text-slate-500 hidden sm:inline">Sede Calle Blanco (Frente al Hospital)</span>
                <a href="{{ route('login') }}" class="text-indigo-800 font-bold hover:underline">Acceso Interno →</a>
            </div>
        </header>

        <!-- Hero Banner -->
        <div class="hero-banner rounded-3xl p-8 text-white flex flex-col md:flex-row items-center justify-between gap-6 shadow-lg">
            <div class="max-w-lg space-y-2">
                <span class="bg-white/20 backdrop-blur-sm text-white text-[10px] font-bold px-3 py-1 rounded-full uppercase">Vista Web Pública (Enlace para WhatsApp)</span>
                <h2 class="font-serif-title text-3xl font-bold text-white">Honramos su memoria con el mayor respeto</h2>
                <p class="text-xs text-indigo-100">Catálogo digital en tiempo real de Funeraria Cristo Rey. Consulte el código del modelo disponible sin exposición de valores internos.</p>
            </div>
            <a target="_blank" rel="noopener" href="https://wa.me/?text={{ urlencode('Hola, quisiera consultar por un servicio en Funeraria Cristo Rey.') }}" class="bg-emerald-500 hover:bg-emerald-600 text-slate-950 font-bold px-6 py-3 rounded-full text-xs shadow-lg flex items-center whitespace-nowrap">
                <i class="fa-brands fa-whatsapp text-lg mr-2"></i>Atención 24/7 por WhatsApp
            </a>
        </div>

        <!-- Logos de Convenios -->
        <section class="grid grid-cols-2 md:grid-cols-6 gap-2.5 text-center text-xs font-bold">
            <div class="p-3 rounded-xl bg-indigo-50 text-indigo-950 border border-indigo-100">IPS / PGU<span class="block text-[10px] font-normal text-slate-500">Asignación Muerte</span></div>
            <div class="p-3 rounded-xl bg-indigo-50 text-indigo-950 border border-indigo-100">Todas las AFP<span class="block text-[10px] font-normal text-slate-500">Cuota 15 UF</span></div>
            <div class="p-3 rounded-xl bg-indigo-50 text-indigo-950 border border-indigo-100">DIPRECA<span class="block text-[10px] font-normal text-slate-500">Carabineros</span></div>
            <div class="p-3 rounded-xl bg-indigo-50 text-indigo-950 border border-indigo-100">CAPREDENA<span class="block text-[10px] font-normal text-slate-500">FF.AA.</span></div>
            <div class="p-3 rounded-xl bg-indigo-50 text-indigo-950 border border-indigo-100">Sindicato INACAP<span class="block text-[10px] font-normal text-slate-500">Convenio Activo</span></div>
            <div class="p-3 rounded-xl bg-indigo-50 text-indigo-950 border border-indigo-100">Sodimac / Salud<span class="block text-[10px] font-normal text-slate-500">Descuento Directo</span></div>
        </section>

        <!-- Urnas Públicas sin precio -->
        <section class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3 text-xs">
            @forelse($productos as $producto)
                <article class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm flex flex-col justify-between space-y-4 hover:border-indigo-300 transition">
                    <div class="space-y-2.5">
                        <div class="flex justify-between items-center gap-2">
                            <span class="bg-indigo-900 text-white font-mono font-bold px-2.5 py-0.5 rounded-full text-[10px]">
                                {{ $producto->codigo }}
                            </span>
                            <div class="flex items-center space-x-1.5">
                                @if($producto->personalizada)
                                    <span class="bg-violet-100 text-violet-800 text-[10px] font-bold px-2 py-0.5 rounded-full border border-violet-200">
                                        Personalizada
                                    </span>
                                @endif
                                <span class="text-emerald-700 bg-emerald-50 text-[10px] font-bold px-2 py-0.5 rounded-full border border-emerald-200">
                                    Disponible
                                </span>
                            </div>
                        </div>

                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-indigo-700 block">
                                Categoría {{ $producto->categoria_etaria }} · {{ $producto->medidas_cm }}
                            </span>
                            <h3 class="font-serif-title text-lg font-bold text-slate-900 mt-0.5">{{ $producto->modelo }}</h3>
                        </div>

                        <dl class="space-y-1 text-xs text-slate-600 bg-slate-50 p-2.5 rounded-xl border border-slate-100">
                            @if($producto->color)<div><dt class="inline font-semibold text-slate-800">Color: </dt><dd class="inline">{{ $producto->color }}</dd></div>@endif
                            @if($producto->tallado)<div><dt class="inline font-semibold text-slate-800">Tallado: </dt><dd class="inline">{{ $producto->tallado }}</dd></div>@endif
                            <div><dt class="inline font-semibold text-slate-800">Dimensiones: </dt><dd class="inline font-mono">{{ $producto->medidas_cm }}</dd></div>
                        </dl>
                        <p class="text-slate-500 pt-0.5 text-[11px]">Incluye capilla ardiente, cruz grabada, tarjetas de condolencias, carroza ceremonial y asesoría en trámites de defunción.</p>
                    </div>
                    <a class="button-primary w-full" target="_blank" rel="noopener" href="https://wa.me/?text={{ urlencode('Hola, quisiera consultar por el modelo '.$producto->modelo.' ('.$producto->codigo.') de Funeraria Cristo Rey.') }}">
                        <i class="fa-brands fa-whatsapp mr-1.5 text-sm"></i>Consultar disponibilidad por WhatsApp
                    </a>
                </article>
            @empty
                <p class="panel text-xs text-slate-600 sm:col-span-2 lg:col-span-3 text-center">El catálogo se está actualizando. Contáctanos por WhatsApp para consultar disponibilidad inmediata.</p>
            @endforelse
        </section>

        <footer class="border-t border-slate-200 pt-5 text-xs text-slate-500 flex justify-between">
            <span>Funeraria Cristo Rey · Temuco</span>
            <span>“Un servicio con respeto, cercanía y profesionalismo”</span>
        </footer>
    </main>
</body>
</html>

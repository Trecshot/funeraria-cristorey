<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\BodegaController;
use App\Http\Controllers\CatalogoPublicoController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FinanzaController;
use App\Http\Controllers\FlotaController;
use App\Http\Controllers\ServicioController;
use App\Http\Controllers\UsuarioController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function (Request $request) {
    if (Auth::check()) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }

    return redirect()->route('login');
});

Route::get('/catalogo', [CatalogoPublicoController::class, 'index'])->name('catalogo.publico');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware('role:administrador,gerencia,asistente,bodega,finanzas')
        ->name('dashboard');

    Route::middleware('role:administrador,gerencia,asistente')->group(function () {
        Route::resource('servicios', ServicioController::class);
        Route::get('/servicios/{servicio}/documentos/{documento}', [ServicioController::class, 'descargarDocumento'])
            ->name('servicios.documentos.descargar');

        Route::get('/flota/calendario', [FlotaController::class, 'calendario'])
            ->name('flota.calendario');
        Route::patch('/flota/traslados/{traslado}', [FlotaController::class, 'asignar'])
            ->name('flota.traslados.asignar');
        Route::patch('/flota/vehiculos/{id}/estado', [FlotaController::class, 'actualizarEstadoVehiculo'])
            ->name('flota.vehiculos.estado');
        Route::post('/flota/choferes', [FlotaController::class, 'crearChofer'])
            ->name('flota.choferes.store');
        Route::patch('/flota/choferes/{chofer}', [FlotaController::class, 'actualizarChofer'])
            ->name('flota.choferes.update');
    });

    Route::middleware('role:administrador,gerencia,asistente,bodega')->group(function () {
        Route::get('/bodega', [BodegaController::class, 'index'])->name('bodega.index');
        Route::post('/bodega/productos', [BodegaController::class, 'crearProducto'])->name('bodega.productos.store');
        Route::post('/bodega/urnas', [BodegaController::class, 'store'])->name('bodega.urnas.store');
    });

    Route::post('/bodega/traspaso', [BodegaController::class, 'traspaso'])
        ->middleware('role:administrador,gerencia,asistente,bodega,chofer')
        ->name('bodega.traspaso');

    Route::middleware('role:administrador,gerencia')->group(function () {
        Route::get('/usuarios', [UsuarioController::class, 'index'])->name('usuarios.index');
        Route::post('/usuarios', [UsuarioController::class, 'store'])->name('usuarios.store');
        Route::post('/flota/vehiculos', [FlotaController::class, 'crearVehiculo'])->name('flota.vehiculos.store');
    });

    Route::middleware('role:administrador,gerencia,finanzas')->group(function () {
        Route::get('/finanzas', [FinanzaController::class, 'index'])->name('finanzas.index');
        Route::get('/finanzas/comprobante/{servicio}', [FinanzaController::class, 'generarPdf'])->name('finanzas.pdf');
    });

    Route::middleware('role:administrador,gerencia,chofer')->group(function () {
        Route::get('/movil/chofer', [FlotaController::class, 'chofer'])->name('movil.chofer');
        Route::patch('/movil/chofer/traslados/{traslado}', [FlotaController::class, 'actualizarEstado'])->name('movil.chofer.estado');
    });
});

<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\BodegaController;
use App\Http\Controllers\CatalogoPublicoController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FinanzaController;
use App\Http\Controllers\FlotaController;
use App\Http\Controllers\ServicioController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (! auth()->check()) {
        return redirect()->route('login');
    }

    return auth()->user()->role === 'chofer'
        ? redirect()->route('movil.chofer')
        : redirect()->route('dashboard');
});

Route::get('/catalogo', [CatalogoPublicoController::class, 'index'])->name('catalogo.publico');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware('role:administrador,gerencia,asistente,bodega,finanzas')
        ->name('dashboard');

    Route::resource('servicios', ServicioController::class)
        ->middleware('role:administrador,gerencia,asistente');
    Route::get('/servicios/{servicio}/documentos/{documento}', [ServicioController::class, 'descargarDocumento'])
        ->middleware('role:administrador,gerencia,asistente')
        ->name('servicios.documentos.descargar');

    Route::get('/bodega', [BodegaController::class, 'index'])
        ->middleware('role:administrador,gerencia,asistente,bodega')
        ->name('bodega.index');
    Route::post('/bodega/traspaso', [BodegaController::class, 'traspaso'])
        ->middleware('role:administrador,gerencia,asistente,bodega')
        ->name('bodega.traspaso');
    Route::post('/bodega/productos', [BodegaController::class, 'crearProducto'])
        ->middleware('role:administrador,gerencia,asistente,bodega')
        ->name('bodega.productos.store');

    Route::get('/flota/calendario', [FlotaController::class, 'calendario'])
        ->middleware('role:administrador,gerencia,asistente')
        ->name('flota.calendario');
    Route::post('/flota/vehiculos', [FlotaController::class, 'crearVehiculo'])
        ->middleware('role:administrador,gerencia')
        ->name('flota.vehiculos.store');
    Route::patch('/flota/traslados/{traslado}', [FlotaController::class, 'asignar'])
        ->middleware('role:administrador,gerencia,asistente')
        ->name('flota.traslados.asignar');

    Route::get('/finanzas', [FinanzaController::class, 'index'])
        ->middleware('role:administrador,gerencia,finanzas')
        ->name('finanzas.index');
    Route::get('/finanzas/comprobante/{servicio}', [FinanzaController::class, 'generarPdf'])
        ->middleware('role:administrador,gerencia,finanzas')
        ->name('finanzas.pdf');

    Route::get('/movil/chofer', [FlotaController::class, 'chofer'])
        ->middleware('role:administrador,gerencia,chofer')
        ->name('movil.chofer');
    Route::patch('/movil/chofer/traslados/{traslado}', [FlotaController::class, 'actualizarEstado'])
        ->middleware('role:administrador,gerencia,chofer')
        ->name('movil.chofer.estado');
});

<?php

namespace App\Providers;

use App\Models\Servicio;
use App\Models\User;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View as ViewInstance;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('layouts.app', function (ViewInstance $view): void {
            $user = auth()->user();
            $data = $view->getData();

            $alertCount = isset($data['serviciosCriticos'])
                ? $data['serviciosCriticos']->count()
                : ($user?->hasRole('administrador', 'gerencia', 'asistente')
                    ? Servicio::criticosConDocumentosFaltantes()->count()
                    : 0);

            $userCount = $user?->hasRole('administrador', 'gerencia')
                ? User::count()
                : 0;

            $view->with([
                'user' => $user,
                'alertCount' => $alertCount,
                'userCount' => $userCount,
            ]);
        });
    }
}

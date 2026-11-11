<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Automatización de Alertas de Morosidad (diario a las 00:01 AM)
Schedule::command('cuotas:verificar-atrasos')->dailyAt('00:01');

<?php

namespace App\Console\Commands;

use App\Models\CuotaPago;
use App\Models\HistorialServicio;
use Carbon\Carbon;
use Illuminate\Console\Command;

class VerificarCuotasAtrasadas extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cuotas:verificar-atrasos';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verifica cuotas de copago vencidas, actualiza estado a moroso y genera alertas en la trazabilidad del expediente';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $hoy = Carbon::today();

        // Buscar cuotas pendientes cuya fecha de vencimiento ya pasó
        $cuotasAtrasadas = CuotaPago::with(['liquidacion.servicio'])
            ->where('estado', CuotaPago::ESTADO_PENDIENTE)
            ->where('fecha_vencimiento', '<', $hoy)
            ->get();

        $procesadas = 0;

        foreach ($cuotasAtrasadas as $cuota) {
            // 1. Cambiar estado de la cuota a atrasada
            $cuota->update(['estado' => CuotaPago::ESTADO_ATRASADA]);

            // 2. Recalcular estado general de la liquidación (marca como Moroso)
            $cuota->liquidacion->recalcularEstados();

            // 3. Generar registro de auditoría en la trazabilidad cronológica del servicio
            if ($servicio = $cuota->liquidacion->servicio) {
                $fechaStr = $cuota->fecha_vencimiento ? $cuota->fecha_vencimiento->format('d/m/Y') : 'recientemente';
                $servicio->registrarHito(
                    HistorialServicio::HITO_FINALIZADO,
                    'Alerta Financiera de Morosidad',
                    "La cuota #{$cuota->numero_cuota} de {$cuota->montoFormateado()} venció el {$fechaStr}. Expediente marcado en estado Moroso.",
                    null
                );
            }

            $procesadas++;
        }

        $this->info("Verificación de cuotas completada: {$procesadas} cuota(s) actualizada(s) a morosidad.");

        return Command::SUCCESS;
    }
}

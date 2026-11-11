<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IndicadoresService
{
    /**
     * Fallback por defecto si no hay conexión ni configuración local (UF referencial chilena)
     */
    public const UF_FALLBACK_DEFAULT = 38500.0;

    /**
     * Clave de caché para el valor del día
     */
    public const CACHE_KEY_HOY = 'valor_uf_hoy';

    /**
     * Clave de respaldo persistente para último valor obtenido exitosamente
     */
    public const CACHE_KEY_BACKUP = 'valor_uf_ultimo_conocido';

    /**
     * Obtiene el valor actual de la UF en CLP desde mindicador.cl con caché de 24 horas y fallbacks.
     */
    public function getValorUF(): float
    {
        return (float) Cache::remember(self::CACHE_KEY_HOY, 86400, function () {
            try {
                $response = Http::timeout(4)
                    ->acceptJson()
                    ->get('https://mindicador.cl/api/uf');

                if ($response->successful()) {
                    $data = $response->json();
                    if (! empty($data['serie'][0]['valor'])) {
                        $valor = (float) $data['serie'][0]['valor'];
                        // Guardar como respaldo histórico en caso de futuras caídas de la API
                        Cache::forever(self::CACHE_KEY_BACKUP, $valor);

                        return $valor;
                    }
                }
            } catch (Exception $e) {
                Log::warning('Error consultando API mindicador.cl para UF: '.$e->getMessage());
            }

            // Fallback 1: Último valor exitoso registrado en caché
            if (Cache::has(self::CACHE_KEY_BACKUP)) {
                return (float) Cache::get(self::CACHE_KEY_BACKUP);
            }

            // Fallback 2: Variable de entorno configurada en config/funeraria.php
            $configValue = config('funeraria.uf_value_clp');
            if (filled($configValue) && is_numeric($configValue)) {
                return (float) $configValue;
            }

            // Fallback 3: Valor por defecto de seguridad
            return self::UF_FALLBACK_DEFAULT;
        });
    }

    /**
     * Retorna el valor de la UF formateado en moneda chilena (CLP)
     */
    public function getValorUFFormateado(): string
    {
        return '$'.number_format($this->getValorUF(), 0, ',', '.');
    }

    /**
     * Calcula el monto en CLP equivalente a un número de UFs (por defecto 15 UF para cuota mortuoria)
     */
    public function calcularUfEnClp(float $ufCantidad = 15.0): float
    {
        return round($ufCantidad * $this->getValorUF());
    }

    /**
     * Limpia la caché para forzar una actualización en caso de ser necesario
     */
    public function forzarActualizacion(): float
    {
        Cache::forget(self::CACHE_KEY_HOY);

        return $this->getValorUF();
    }
}

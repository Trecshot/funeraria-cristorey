<?php

namespace App\Services;

use App\Models\MovimientoBodega;
use App\Models\Urna;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BodegaService
{
    public function crearUrna(array $payload): Urna
    {
        return Urna::create($payload);
    }

    public function registrarTraspaso(int $urnaId, string $origen, string $destino, int $cantidad, int $userId): void
    {
        DB::transaction(function () use ($urnaId, $origen, $destino, $cantidad, $userId): void {
            $urna = Urna::whereKey($urnaId)->lockForUpdate()->firstOrFail();

            $origenColumn = $urna->stockColumn($origen);
            $destinoColumn = $urna->stockColumn($destino);

            if ($urna->{$origenColumn} < $cantidad) {
                throw ValidationException::withMessages([
                    'cantidad' => 'No hay existencias suficientes en la ubicación de origen.',
                ]);
            }

            $urna->decrement($origenColumn, $cantidad);
            $urna->increment($destinoColumn, $cantidad);

            MovimientoBodega::create([
                'producto_bodega_id' => $urna->id,
                'urna_id' => $urna->id,
                'user_id' => $userId,
                'origen' => $origen,
                'destino' => $destino,
                'cantidad' => $cantidad,
            ]);
        });
    }
}

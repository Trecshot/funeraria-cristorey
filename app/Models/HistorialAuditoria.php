<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistorialAuditoria extends Model
{
    protected $table = 'historial_auditorias';

    protected $fillable = [
        'expediente_id',
        'user_id',
        'action_type',
        'target_entity',
        'descripcion',
        'ip_address',
        'transaction_hash',
    ];

    public function servicio(): BelongsTo
    {
        return $this->belongsTo(Servicio::class, 'expediente_id');
    }

    public function expediente(): BelongsTo
    {
        return $this->belongsTo(Servicio::class, 'expediente_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Registro de auditoría inmutable con hash SHA-256 (Ley N° 21.663 de Ciberseguridad)
     */
    public static function registrar(
        int $expedienteId,
        ?int $userId,
        string $actionType,
        string $targetEntity,
        string $descripcion,
        ?string $ipAddress = null,
        ?string $extraData = null
    ): self {
        $ip = $ipAddress ?: (request()?->ip() ?? '127.0.0.1');
        $hash = hash('sha256', $expedienteId . microtime(true) . ($userId ?: 'system') . $ip . ($extraData ?: ''));

        return self::create([
            'expediente_id' => $expedienteId,
            'user_id' => $userId,
            'action_type' => $actionType,
            'target_entity' => $targetEntity,
            'descripcion' => $descripcion,
            'ip_address' => $ip,
            'transaction_hash' => $hash,
        ]);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistorialServicio extends Model
{
    protected $table = 'historial_servicios';

    public const HITO_APERTURA = 'apertura';
    public const HITO_LOGISTICA_URNA = 'logistica_urna';
    public const HITO_DOCUMENTAL = 'documental';
    public const HITO_CORTEJO_FLOTA = 'cortejo_flota';
    public const HITO_FINALIZADO = 'finalizado';

    public const HITOS_DEFINICION = [
        self::HITO_APERTURA => [
            'numero' => 1,
            'nombre' => 'Apertura',
            'titulo' => 'Apertura de Expediente',
            'icono' => 'fa-folder-open',
        ],
        self::HITO_LOGISTICA_URNA => [
            'numero' => 2,
            'nombre' => 'Logística / Urna',
            'titulo' => 'Asignación de Urna desde Bodega',
            'icono' => 'fa-box-archive',
        ],
        self::HITO_DOCUMENTAL => [
            'numero' => 3,
            'nombre' => 'Legal y Documental',
            'titulo' => 'Validación Seremi y Respaldo S3',
            'icono' => 'fa-file-shield',
        ],
        self::HITO_CORTEJO_FLOTA => [
            'numero' => 4,
            'nombre' => 'Cortejo y Flota',
            'titulo' => 'Programación y Asignación de Flota',
            'icono' => 'fa-van-shuttle',
        ],
        self::HITO_FINALIZADO => [
            'numero' => 5,
            'nombre' => 'Finalizado',
            'titulo' => 'Servicio Concluido',
            'icono' => 'fa-circle-check',
        ],
    ];

    protected $fillable = [
        'servicio_id',
        'hito',
        'titulo',
        'descripcion',
        'usuario_id',
        'completado_at',
    ];

    protected function casts(): array
    {
        return [
            'completado_at' => 'datetime',
        ];
    }

    public function servicio(): BelongsTo
    {
        return $this->belongsTo(Servicio::class, 'servicio_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function hitoDefinicion(): array
    {
        return self::HITOS_DEFINICION[$this->hito] ?? [
            'numero' => 0,
            'nombre' => ucfirst($this->hito),
            'titulo' => $this->titulo,
            'icono' => 'fa-clock',
        ];
    }
}

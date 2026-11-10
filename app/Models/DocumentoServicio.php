<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentoServicio extends Model
{
    public const TIPOS = [
        'certificado_defuncion' => 'Certificado de defunción',
        'resolucion_seremi' => 'Resolución Seremi',
        'oficio_fiscalia_sml' => 'Oficio de Fiscalía / SML',
    ];

    public const ALERT_LABELS = [
        'resolucion_seremi' => 'Resolución de Traslado Seremi',
        'oficio_fiscalia_sml' => 'Oficio de Fiscalía (SML)',
    ];

    protected $table = 'documentos_servicio';

    protected $fillable = ['servicio_id', 'uploaded_by', 'tipo', 'nombre_original', 'disk', 'path', 'ruta'];

    protected static function booted(): void
    {
        static::saving(function (self $documento): void {
            if ($documento->path && ! $documento->ruta) {
                $documento->ruta = $documento->path;
            }
        });
    }

    public function getDiskAttribute(?string $value): string
    {
        return $value ?: 'local';
    }

    public function getPathAttribute(?string $value): ?string
    {
        return $value ?: $this->attributes['ruta'] ?? null;
    }

    public function tipoLabel(): string
    {
        return self::TIPOS[$this->tipo] ?? $this->tipo;
    }

    public function servicio(): BelongsTo
    {
        return $this->belongsTo(Servicio::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}

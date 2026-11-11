<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

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

    public function pesoFormateado(): string
    {
        try {
            if ($this->path && Storage::disk($this->disk)->exists($this->path)) {
                $bytes = Storage::disk($this->disk)->size($this->path);
                if ($bytes >= 1048576) {
                    return number_format($bytes / 1048576, 1, ',', '.') . ' MB';
                }
                return number_format($bytes / 1024, 0, ',', '.') . ' KB';
            }
        } catch (\Throwable $e) {
            // Silencioso ante fallos temporales de almacenamiento
        }

        return '';
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

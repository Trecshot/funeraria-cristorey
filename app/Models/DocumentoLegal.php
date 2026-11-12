<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentoLegal extends Model
{
    use HasUuids;

    protected $table = 'documentos_legales';

    public const CONTEXTO_NATURAL = 'NATURAL';
    public const CONTEXTO_SML_VIOLENTO = 'SML_VIOLENTO';
    public const CONTEXTO_RURAL_TESTIGOS = 'RURAL_TESTIGOS';

    public const CONTEXTOS = [
        self::CONTEXTO_NATURAL => 'Muerte Natural (Certificado Médico)',
        self::CONTEXTO_SML_VIOLENTO => 'Muerte Violenta / SML (Parte Fiscalía)',
        self::CONTEXTO_RURAL_TESTIGOS => 'Muerte en Zona Rural (2 Testigos)',
    ];

    protected $fillable = [
        'expediente_id',
        'contexto_deceso',
        'traslado_interurbano',
        'folio_certificado_medico',
        'folio_pase_sepultacion',
        'ruta_orden_autopsia_sml',
        'ruta_resolucion_seremi',
        'ruta_cesion_derechos',
        'testigos_rurales',
    ];

    /**
     * Cifrado automático en reposo (Data-at-Rest Encryption - Ley N° 21.719)
     * Laravel cifra y descifra automáticamente mediante AES-256-CBC.
     */
    protected $casts = [
        'folio_certificado_medico' => 'encrypted',
        'folio_pase_sepultacion' => 'encrypted',
        'testigos_rurales' => 'array',
        'traslado_interurbano' => 'boolean',
    ];

    public function servicio(): BelongsTo
    {
        return $this->belongsTo(Servicio::class, 'expediente_id');
    }

    public function expediente(): BelongsTo
    {
        return $this->belongsTo(Servicio::class, 'expediente_id');
    }

    public function esNatural(): bool
    {
        return $this->contexto_deceso === self::CONTEXTO_NATURAL;
    }

    public function esSmlViolento(): bool
    {
        return $this->contexto_deceso === self::CONTEXTO_SML_VIOLENTO;
    }

    public function esRural(): bool
    {
        return $this->contexto_deceso === self::CONTEXTO_RURAL_TESTIGOS;
    }

    public function contextoLabel(): string
    {
        return self::CONTEXTOS[$this->contexto_deceso] ?? $this->contexto_deceso;
    }
}

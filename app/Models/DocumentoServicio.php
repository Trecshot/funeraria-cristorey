<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentoServicio extends Model
{
    protected $table = 'documentos_servicio';

    protected $fillable = ['servicio_id', 'uploaded_by', 'tipo', 'nombre_original', 'disk', 'path'];

    public function getDiskAttribute(?string $value): string
    {
        return $value ?: 'local';
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

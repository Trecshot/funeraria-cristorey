<?php

namespace App\Models\Concerns;

use App\Models\HistorialServicio;
use App\Services\HitosOperativosService;
use Illuminate\Support\Collection;

trait GestionaHitosYDocumentos
{
    /**
     * @return list<string>
     */
    public function documentosRequeridos(): array
    {
        $requeridos = ['resolucion_seremi'];
        if (filled($this->causa_sml)) {
            $requeridos[] = 'oficio_fiscalia_sml';
        }

        return $requeridos;
    }

    /**
     * @return list<string>
     */
    public function calcularDocumentosFaltantes(): array
    {
        $presentes = $this->documentos->pluck('tipo')->all();

        return array_values(array_diff($this->documentosRequeridos(), $presentes));
    }

    /**
     * @return Collection<int, static>
     */
    public static function criticosConDocumentosFaltantes(): Collection
    {
        return static::query()
            ->whereBetween('cortejo_at', [now(), now()->addHours(3)])
            ->with('documentos:id,servicio_id,tipo')
            ->orderBy('cortejo_at')
            ->get()
            ->map(function (self $servicio): self {
                $servicio->documentosFaltantes = $servicio->calcularDocumentosFaltantes();

                return $servicio;
            })
            ->filter(fn (self $servicio): bool => $servicio->documentosFaltantes !== [])
            ->values();
    }

    public function registrarHito(
        string $hito,
        string $titulo,
        ?string $descripcion = null,
        ?int $usuarioId = null,
        mixed $completadoAt = null
    ): HistorialServicio {
        return $this->historial()->updateOrCreate(
            ['hito' => $hito],
            [
                'titulo' => $titulo,
                'descripcion' => $descripcion,
                'usuario_id' => $usuarioId ?: (auth()->id() ?: $this->created_by),
                'completado_at' => $completadoAt ?: now(),
            ]
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function sincronizarHitosOperativos(?int $usuarioId = null): array
    {
        return app(HitosOperativosService::class)->sincronizarHitos($this, $usuarioId);
    }

    public function calcularPorcentajeAvance(): int
    {
        return app(HitosOperativosService::class)->calcularPorcentajeAvance($this);
    }
}

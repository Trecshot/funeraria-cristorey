<?php

namespace App\Http\Requests\Bodega;

use App\Models\Urna;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUrnaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('codigo')) {
            $prefijo = match ($this->input('categoria_etaria')) {
                'Niño' => 'URN-NN',
                'Juvenil' => 'URN-JV',
                'Especial' => 'URN-XL',
                default => 'URN-AD',
            };
            $siguienteNumero = Urna::count() + 1;
            $this->merge([
                'codigo' => sprintf('%s-%02d', $prefijo, $siguienteNumero),
            ]);
        }

        if (! $this->has('stock_salon') && ! $this->has('stock_taller')) {
            $ubicacion = $this->input('ubicacion', 'salon');
            $stock = (int) $this->input('stock', 0);
            $this->merge([
                'stock_salon' => $ubicacion === 'salon' ? $stock : 0,
                'stock_taller' => $ubicacion === 'taller' ? $stock : 0,
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'codigo' => ['required', 'string', 'max:50', 'unique:urnas,codigo'],
            'modelo' => ['required', 'string', 'max:100'],
            'color' => ['required', 'string', 'max:50'],
            'tallado' => ['nullable', 'string', 'max:100'],
            'categoria_etaria' => ['required', Rule::in(Urna::CATEGORIAS_ETARIAS)],
            'medidas_cm' => ['required', 'string', 'max:50'],
            'personalizada' => ['nullable', 'boolean'],
            'precio' => ['required', 'numeric', 'min:0'],
            'stock_salon' => ['required', 'integer', 'min:0'],
            'stock_taller' => ['required', 'integer', 'min:0'],
            'stock_minimo' => ['required', 'integer', 'min:0'],
            'mostrar_catalogo' => ['nullable', 'boolean'],
        ];
    }

    public function normalizedPayload(): array
    {
        $validated = $this->validated();
        $validated['personalizada'] = $this->boolean('personalizada');
        $validated['mostrar_catalogo'] = $this->has('mostrar_catalogo')
            ? $this->boolean('mostrar_catalogo')
            : true;
        $validated['publicado'] = $validated['mostrar_catalogo'];

        return $validated;
    }
}

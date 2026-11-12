<?php

namespace App\Http\Requests\Bodega;

use App\Models\Urna;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TraspasoBodegaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function idKey(): string
    {
        return $this->has('urna_id') ? 'urna_id' : 'producto_id';
    }

    public function rules(): array
    {
        $idKey = $this->idKey();

        return [
            $idKey => ['required', 'integer', 'exists:urnas,id'],
            'origen' => ['required', Rule::in(Urna::UBICACIONES)],
            'destino' => ['required', Rule::in(Urna::UBICACIONES), 'different:origen'],
            'cantidad' => ['required', 'integer', 'min:1'],
        ];
    }

    public function urnaId(): int
    {
        return (int) $this->validated($this->idKey());
    }
}

<?php

namespace App\Http\Requests\Reportes;

use Illuminate\Foundation\Http\FormRequest;

class ExportarReporteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
            'contexto' => ['nullable', 'string', 'in:TODOS,NATURAL,SML_VIOLENTO,RURAL_TESTIGOS'],
            'anonimizado' => ['nullable', 'boolean'],
            'solo_interurbano' => ['nullable', 'boolean'],
            'formato' => ['nullable', 'string', 'in:csv,pdf'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'fecha_inicio.required' => 'Debes seleccionar una fecha de inicio para el informe.',
            'fecha_fin.required' => 'Debes seleccionar una fecha de término para el informe.',
            'fecha_fin.after_or_equal' => 'La fecha de término debe ser igual o posterior a la fecha de inicio.',
        ];
    }
}

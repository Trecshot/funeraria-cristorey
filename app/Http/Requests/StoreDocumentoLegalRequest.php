<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentoLegalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'contexto_deceso' => ['required', 'in:NATURAL,SML_VIOLENTO,RURAL_TESTIGOS'],
            'folio_certificado_medico' => ['required_if:contexto_deceso,NATURAL', 'nullable', 'string', 'max:100'],
            'folio_pase_sepultacion' => ['nullable', 'string', 'max:100'],
            'orden_autopsia_sml' => ['required_if:contexto_deceso,SML_VIOLENTO', 'nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:15360'],
            'testigos' => ['required_if:contexto_deceso,RURAL_TESTIGOS', 'nullable', 'array', 'min:2'],
            'testigos.*.nombre' => ['required_with:testigos', 'nullable', 'string', 'max:255'],
            'testigos.*.rut' => ['required_with:testigos', 'nullable', 'string', 'max:20'],
            'traslado_interurbano' => ['nullable', 'boolean'],
            'resolucion_seremi' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:15360'],
        ];
    }

    public function messages(): array
    {
        return [
            'contexto_deceso.required' => 'Debes indicar el contexto del deceso para determinar los requisitos legales.',
            'folio_certificado_medico.required_if' => 'El Folio del Certificado Médico de Defunción es obligatorio en defunciones por causa natural.',
            'orden_autopsia_sml.required_if' => 'Debe adjuntar la Orden de Autopsia / Oficio de Fiscalía para fallecimientos con intervención del SML.',
            'testigos.required_if' => 'Para fallecimientos en zona rural se requiere la identificación de al menos 2 testigos.',
            'testigos.min' => 'Se requieren al menos 2 testigos presenciales para la inscripción en zona rural.',
            'testigos.*.nombre.required_with' => 'El nombre completo del testigo es obligatorio.',
            'testigos.*.rut.required_with' => 'El RUT del testigo es obligatorio.',
            'resolucion_seremi.required_if' => 'El traslado interurbano exige la Resolución Sanitaria de la Seremi de Salud (Art. 75 DTO 357).',
        ];
    }
}

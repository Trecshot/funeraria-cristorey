<?php

namespace App\Http\Requests\Servicio;

use App\Models\DocumentoServicio;
use App\Models\Servicio;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GuardarServicioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        /** @var Servicio|null $servicio */
        $servicio = $this->route('servicio');

        $rules = [
            'cliente_nombre' => ['required', 'string', 'max:255'],
            'cliente_telefono' => ['nullable', 'string', 'max:30'],
            'difunto_nombre' => ['required', 'string', 'max:255'],
            'escolaridad' => ['nullable', 'string', 'max:255'],
            'oficio' => ['nullable', 'string', 'max:255'],
            'causa_sml' => ['nullable', 'string', 'max:5000'],
            'convenio' => ['required', Rule::in(array_keys(Servicio::CONVENIOS))],
            'urna_id' => ['nullable', 'exists:urnas,id'],
            'monto_servicio' => ['required', 'integer', 'min:0', 'max:9999999999'],
            'cortejo_at' => ['nullable', 'date'],
            'destino' => ['nullable', 'string', 'max:255'],
            'estado' => ['required', Rule::in(array_keys(Servicio::ESTADOS))],

            'contexto_deceso' => ['nullable', Rule::in(['NATURAL', 'SML_VIOLENTO', 'RURAL_TESTIGOS'])],
            'folio_certificado_medico' => ['nullable', 'string', 'max:100'],
            'folio_pase_sepultacion' => ['nullable', 'string', 'max:100'],
            'traslado_interurbano' => ['nullable', 'boolean'],
            'orden_autopsia_sml' => ['nullable', 'file', 'max:15360', 'mimes:pdf,jpg,jpeg,png'],
            'resolucion_seremi' => ['nullable', 'file', 'max:15360', 'mimes:pdf,jpg,jpeg,png'],
            'testigos' => ['nullable', 'array'],
            'testigos.*.nombre' => ['nullable', 'string', 'max:255'],
            'testigos.*.rut' => ['nullable', 'string', 'max:20'],

            'documentos' => ['sometimes', 'array'],
            'documentos.*' => ['nullable', 'file', 'max:15360', 'mimes:pdf,jpg,jpeg,png'],
            'tipos_documentos' => ['sometimes', 'array'],
            'tipos_documentos.*' => ['required', Rule::in(array_keys(DocumentoServicio::TIPOS))],
        ];

        $contexto = $this->input('contexto_deceso');
        if ($contexto === 'NATURAL') {
            $rules['folio_certificado_medico'] = ['required', 'string', 'max:100'];
        } elseif ($contexto === 'SML_VIOLENTO') {
            $tieneArchivoSml = $servicio?->documentoLegal?->ruta_orden_autopsia_sml;
            if (! $tieneArchivoSml && ! $this->hasFile('orden_autopsia_sml')) {
                $rules['orden_autopsia_sml'] = ['required', 'file', 'max:15360', 'mimes:pdf,jpg,jpeg,png'];
            }
        } elseif ($contexto === 'RURAL_TESTIGOS') {
            $rules['testigos'] = ['required', 'array', 'min:2'];
            $rules['testigos.*.nombre'] = ['required', 'string', 'max:255'];
            $rules['testigos.*.rut'] = ['required', 'string', 'max:20'];
        }

        if ($this->boolean('traslado_interurbano')) {
            $tieneSeremi = ($servicio?->documentoLegal?->ruta_resolucion_seremi)
                || ($servicio && $servicio->documentos()->where('tipo', 'resolucion_seremi')->exists());
            if (! $tieneSeremi && ! $this->hasFile('resolucion_seremi')) {
                $rules['resolucion_seremi'] = ['required', 'file', 'max:15360', 'mimes:pdf,jpg,jpeg,png'];
            }
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'contexto_deceso.required' => 'Debes indicar el contexto del deceso para determinar los requisitos legales.',
            'folio_certificado_medico.required' => 'El Folio del Certificado Médico de Defunción es obligatorio en fallecimientos por causa natural.',
            'orden_autopsia_sml.required' => 'Debe adjuntar la Orden de Autopsia / Oficio de Fiscalía para fallecimientos con intervención del SML.',
            'testigos.required' => 'Para fallecimientos en zona rural se requiere la identificación de al menos 2 testigos.',
            'testigos.min' => 'Se requieren al menos 2 testigos presenciales para la inscripción en zona rural.',
            'testigos.*.nombre.required' => 'El nombre completo del testigo rural es obligatorio.',
            'testigos.*.rut.required' => 'El RUT del testigo rural es obligatorio.',
            'resolucion_seremi.required' => 'El traslado interurbano exige la Resolución Sanitaria de la Seremi de Salud (Art. 75 DTO 357).',
        ];
    }
}

<?php

namespace Database\Seeders;

use App\Models\Chofer;
use App\Models\DocumentoLegal;
use App\Models\HistorialAuditoria;
use App\Models\HistorialServicio;
use App\Models\Servicio;
use App\Models\Traslado;
use App\Models\Urna;
use App\Models\User;
use App\Models\Vehiculo;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class MatrizDecesosSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', 'administrador')->first() ?? User::first();
        $adminId = $admin?->id ?? 1;

        $disk = config('filesystems.disks.s3.bucket') ? 's3' : config('filesystems.default', 'local');

        $choferes = User::where('role', 'chofer')->get()->keyBy('name');
        $vehiculos = Vehiculo::all()->keyBy('patente');
        $urnas = Urna::all()->keyBy('codigo');

        // =========================================================================
        // CASO 1: MUERTE NATURAL (TEMUCO URBANO - LEY 21.719 Y REGLA DE 48 HORAS)
        // =========================================================================
        $servicioNatural = Servicio::updateOrCreate(
            ['folio' => 'FC-2026-010'],
            [
                'created_by' => $adminId,
                'cliente_nombre' => 'Carmen Gloria Morales Soto',
                'cliente_rut' => '14.882.103-2',
                'cliente_telefono' => '+56 9 7821 4432',
                'difunto_nombre' => 'Don Eleuterio Morales Riffo',
                'difunto_rut' => '6.842.119-3',
                'escolaridad' => 'Técnica Completa',
                'oficio' => 'Profesor Normalista Jubilado',
                'causa_sml' => null,
                'requiere_sml' => false,
                'convenio' => 'ips',
                'urna_id' => $urnas->get('URN-AD-03')?->id ?? Urna::first()?->id,
                'monto_servicio' => 1550000,
                'aporte_convenio' => 1070000,
                'copago' => 480000,
                'cortejo_at' => Carbon::now()->addHours(24),
                'estado' => 'en_preparacion',
            ]
        );

        $docLegalNatural = DocumentoLegal::updateOrCreate(
            ['expediente_id' => $servicioNatural->id],
            [
                'contexto_deceso' => DocumentoLegal::CONTEXTO_NATURAL,
                'traslado_interurbano' => false,
                'folio_certificado_medico' => 'CMD-2026-09841',
                'folio_pase_sepultacion' => 'PS-TEMUCO-4412',
                'ruta_orden_autopsia_sml' => null,
                'ruta_resolucion_seremi' => null,
                'ruta_cesion_derechos' => null,
                'testigos_rurales' => null,
            ]
        );

        Traslado::updateOrCreate(
            ['servicio_id' => $servicioNatural->id],
            [
                'vehiculo_id' => $vehiculos->get('KH-TY-18')?->id,
                'chofer_id' => $choferes->get('Carlos Soto')?->id,
                'cementerio' => 'Cementerio General de Temuco (Av. Balmaceda)',
                'salida_at' => Carbon::now()->addHours(24),
                'implementos' => ['Cruz de madera biselada', 'Tarjetas de pésame', 'Libro de condolencias'],
                'estado' => 'programado',
            ]
        );

        HistorialAuditoria::firstOrCreate(
            [
                'expediente_id' => $servicioNatural->id,
                'action_type' => 'CREATE',
                'target_entity' => 'documentos_legales',
            ],
            [
                'user_id' => $adminId,
                'descripcion' => 'Inscripción de Folio Médico CMD-2026-09841 y Pase PS-TEMUCO-4412 con cifrado AES-256',
                'ip_address' => '192.168.1.15',
                'transaction_hash' => hash('sha256', $servicioNatural->id . 'NATURAL' . $adminId . '192.168.1.15'),
            ]
        );

        $servicioNatural->sincronizarHitosOperativos();

        // =========================================================================
        // CASO 2: MUERTE VIOLENTA / JUDICIAL SML (ACCIDENTE RUTA 5 SUR - BYPASS 48H)
        // =========================================================================
        $servicioSml = Servicio::updateOrCreate(
            ['folio' => 'FC-2026-020'],
            [
                'created_by' => $adminId,
                'cliente_nombre' => 'Patricia Oyarzún Lagos',
                'cliente_rut' => '10.455.912-8',
                'cliente_telefono' => '+56 9 8452 1109',
                'difunto_nombre' => 'Ignacio Andrés Valenzuela Oyarzún',
                'difunto_rut' => '17.921.340-K',
                'escolaridad' => 'Universitaria Completa',
                'oficio' => 'Ingeniero en Prevención de Riesgos',
                'causa_sml' => 'Politraumatismo torácico y abdominal por colisión vial de alta energía (Ruta 5 Sur Km 680)',
                'requiere_sml' => true,
                'convenio' => 'particular',
                'urna_id' => $urnas->get('URN-AD-01')?->id ?? Urna::first()?->id,
                'monto_servicio' => 1890000,
                'aporte_convenio' => 0,
                'copago' => 1890000,
                'cortejo_at' => Carbon::now()->addDays(3)->setHour(16)->setMinute(30),
                'estado' => 'en_preparacion',
            ]
        );

        $pathPdfSml = "legales/{$servicioSml->id}/oficio_fiscalia_sml_causa_982.pdf";
        $pdfSmlContent = $this->generarPdf(
            'MINISTERIO PUBLICO - FISCALIA REGIONAL DE LA ARAUCANIA',
            'Oficio Ordinario Nro. 982-2026 / Causa RUC 260019482-1',
            [
                'Materia: Autorizacion de Entrega y Sepultacion SML Temuco.',
                'Fallecido: Ignacio Andres Valenzuela Oyarzun - RUT 17.921.340-K.',
                'Causa de Deceso: Politraumatismo vial de alta energia (Accidente transito).',
                'Fiscal de Turno: Abg. Gonzalo Martinez Alarcon.',
                'Destinatario: Servicio Medico Legal de Temuco y Funeraria Cristo Rey.',
                'Certifico: Levantamiento de peritaje tanatologico y autopsia concluida.',
                'Se autoriza sepultacion en Parque del Sendero Temuco sin restriccion.',
                'Emision: Temuco, 12 de Noviembre de 2026.',
            ]
        );
        Storage::disk($disk)->put($pathPdfSml, $pdfSmlContent);

        DocumentoLegal::updateOrCreate(
            ['expediente_id' => $servicioSml->id],
            [
                'contexto_deceso' => DocumentoLegal::CONTEXTO_SML_VIOLENTO,
                'traslado_interurbano' => false,
                'folio_certificado_medico' => null,
                'folio_pase_sepultacion' => 'PS-SML-7712-TEM',
                'ruta_orden_autopsia_sml' => $pathPdfSml,
                'ruta_resolucion_seremi' => null,
                'ruta_cesion_derechos' => null,
                'testigos_rurales' => null,
            ]
        );

        Traslado::updateOrCreate(
            ['servicio_id' => $servicioSml->id],
            [
                'vehiculo_id' => $vehiculos->get('JF-KR-32')?->id ?? $vehiculos->first()?->id,
                'chofer_id' => $choferes->get('Patricio Riquelme')?->id ?? $choferes->first()?->id,
                'cementerio' => 'Parque del Sendero Temuco (Sector Las Encinas)',
                'salida_at' => Carbon::now()->addDays(3)->setHour(16)->setMinute(30),
                'implementos' => ['Capilla ardiente plateada', 'Cruz biselada', 'Flores naturales'],
                'estado' => 'programado',
            ]
        );

        HistorialAuditoria::firstOrCreate(
            [
                'expediente_id' => $servicioSml->id,
                'action_type' => 'UPDATE',
                'target_entity' => 'documentos_legales',
            ],
            [
                'user_id' => $adminId,
                'descripcion' => 'Carga de Orden de Autopsia / Parte de Fiscalía SML (Causa RUC 260019482-1) y visación judicial',
                'ip_address' => '192.168.1.18',
                'transaction_hash' => hash('sha256', $servicioSml->id . 'SML_VIOLENTO' . $adminId . '192.168.1.18'),
            ]
        );

        $servicioSml->sincronizarHitosOperativos();

        // =========================================================================
        // CASO 3: MUERTE EN ZONA RURAL CON 2 TESTIGOS (COMUNIDAD HUICHAHUE / CUNCO)
        // =========================================================================
        $servicioRural = Servicio::updateOrCreate(
            ['folio' => 'FC-2026-030'],
            [
                'created_by' => $adminId,
                'cliente_nombre' => 'Mateo Catrileo Colicheo',
                'cliente_rut' => '15.772.931-1',
                'cliente_telefono' => '+56 9 9123 7744',
                'difunto_nombre' => 'Lorenzo Catrileo Nahuelpan',
                'difunto_rut' => '7.109.844-5',
                'escolaridad' => 'Básica Incompleta',
                'oficio' => 'Agricultor / Lonko de Comunidad',
                'causa_sml' => null,
                'requiere_sml' => false,
                'convenio' => 'afp',
                'urna_id' => $urnas->get('URN-AD-06')?->id ?? Urna::first()?->id,
                'monto_servicio' => 1350000,
                'aporte_convenio' => 580000,
                'copago' => 770000,
                'cortejo_at' => Carbon::now()->addHours(36),
                'estado' => 'abierto',
            ]
        );

        DocumentoLegal::updateOrCreate(
            ['expediente_id' => $servicioRural->id],
            [
                'contexto_deceso' => DocumentoLegal::CONTEXTO_RURAL_TESTIGOS,
                'traslado_interurbano' => false,
                'folio_certificado_medico' => null,
                'folio_pase_sepultacion' => 'PS-RURAL-CUNCO-110',
                'ruta_orden_autopsia_sml' => null,
                'ruta_resolucion_seremi' => null,
                'ruta_cesion_derechos' => null,
                'testigos_rurales' => [
                    [
                        'nombre' => 'Guillermo Painemal Linco',
                        'rut' => '11.450.219-4',
                    ],
                    [
                        'nombre' => 'Juana Marilaf Huenchumil',
                        'rut' => '13.882.109-1',
                    ],
                ],
            ]
        );

        Traslado::updateOrCreate(
            ['servicio_id' => $servicioRural->id],
            [
                'vehiculo_id' => $vehiculos->get('GH-YR-42')?->id ?? $vehiculos->first()?->id,
                'chofer_id' => $choferes->get('Esteban Morales')?->id ?? $choferes->first()?->id,
                'cementerio' => 'Cementerio Indígena Comunidad Huichahue (Sector Rural Cunco)',
                'salida_at' => Carbon::now()->addHours(36),
                'implementos' => [
                    'Polietileno (Nailon rural de protección)',
                    'Cruz de madera rústica tallada',
                    'Cintas de luto',
                    'Equipo de tracción barro 4x4',
                ],
                'estado' => 'programado',
            ]
        );

        HistorialAuditoria::firstOrCreate(
            [
                'expediente_id' => $servicioRural->id,
                'action_type' => 'CREATE',
                'target_entity' => 'documentos_legales',
            ],
            [
                'user_id' => $adminId,
                'descripcion' => 'Inscripción de Defunción en Zona Rural con 2 Testigos Presenciales (Art. 45 Ley Registro Civil N° 4.808)',
                'ip_address' => '192.168.1.22',
                'transaction_hash' => hash('sha256', $servicioRural->id . 'RURAL_TESTIGOS' . $adminId . '192.168.1.22'),
            ]
        );

        $servicioRural->sincronizarHitosOperativos();

        // =========================================================================
        // CASO 4: TRASLADO INTERURBANO REGIONAL CON SEREMI (TEMUCO A VALDIVIA)
        // =========================================================================
        $servicioInterurbano = Servicio::updateOrCreate(
            ['folio' => 'FC-2026-040'],
            [
                'created_by' => $adminId,
                'cliente_nombre' => 'Roberto Carrasco Fuentes',
                'cliente_rut' => '11.399.882-6',
                'cliente_telefono' => '+56 9 6632 8810',
                'difunto_nombre' => 'Marta Eugenia Carrasco Fuentes',
                'difunto_rut' => '9.743.201-8',
                'escolaridad' => 'Educación Media Completa',
                'oficio' => 'Comerciante',
                'causa_sml' => null,
                'requiere_sml' => false,
                'convenio' => 'dipreca',
                'urna_id' => $urnas->get('URN-AD-08')?->id ?? Urna::first()?->id,
                'monto_servicio' => 2100000,
                'aporte_convenio' => 1200000,
                'copago' => 900000,
                'cortejo_at' => Carbon::now()->addDays(2)->setHour(10)->setMinute(0),
                'estado' => 'en_preparacion',
            ]
        );

        $pathPdfSeremi = "legales/{$servicioInterurbano->id}/resolucion_seremi_traslado_357_valdivia.pdf";
        $pdfSeremiContent = $this->generarPdf(
            'SEREMI DE SALUD - REGION DE LA ARAUCANIA',
            'Resolucion Exenta Nro. 357-TR/2026 / Departamento Accion Sanitaria',
            [
                'Visto: Solicitud de Traslado Interurbano Cadaverico (Art. 75 DTO 357).',
                'Fallecida: Marta Eugenia Carrasco Fuentes - RUT 9.743.201-8.',
                'Origen: Clinica Alemana de Temuco / Domicilio Calle Blanco 1120.',
                'Destino: Cementerio Parque Aleman de Valdivia (Region de Los Rios).',
                'Vehiculo Habilitado: Carroza Funeraria Patente KH-TY-18.',
                'Conductor Asignado: Carlos Soto (Licencia Profesional A2).',
                'Verificacion Sanitaria: Cierre hermetico de urna y sello verificado.',
                'Se autoriza el transito por Ruta 5 Sur entre IX y XIV Regiones.',
                'Resolucion emitida en Temuco a 12 de Noviembre de 2026.',
            ]
        );
        Storage::disk($disk)->put($pathPdfSeremi, $pdfSeremiContent);

        DocumentoLegal::updateOrCreate(
            ['expediente_id' => $servicioInterurbano->id],
            [
                'contexto_deceso' => DocumentoLegal::CONTEXTO_NATURAL,
                'traslado_interurbano' => true,
                'folio_certificado_medico' => 'CMD-2026-11842',
                'folio_pase_sepultacion' => 'PS-TEMUCO-9021',
                'ruta_orden_autopsia_sml' => null,
                'ruta_resolucion_seremi' => $pathPdfSeremi,
                'ruta_cesion_derechos' => null,
                'testigos_rurales' => null,
            ]
        );

        Traslado::updateOrCreate(
            ['servicio_id' => $servicioInterurbano->id],
            [
                'vehiculo_id' => $vehiculos->get('KH-TY-18')?->id ?? $vehiculos->first()?->id,
                'chofer_id' => $choferes->get('Carlos Soto')?->id ?? $choferes->first()?->id,
                'cementerio' => 'Cementerio Parque Alemán de Valdivia (Ruta 5 Sur Km 830)',
                'salida_at' => Carbon::now()->addDays(2)->setHour(10)->setMinute(0),
                'implementos' => [
                    'Sellado sanitario reglamentario DTO 357',
                    'Copia física Resolución Seremi en guantera',
                    'Flores de cortejo de largo viaje',
                ],
                'estado' => 'programado',
            ]
        );

        HistorialAuditoria::firstOrCreate(
            [
                'expediente_id' => $servicioInterurbano->id,
                'action_type' => 'UPDATE',
                'target_entity' => 'documentos_legales',
            ],
            [
                'user_id' => $adminId,
                'descripcion' => 'Carga y visación de Resolución Sanitaria de Traslado Interurbano Temuco-Valdivia (Art. 75 DTO 357)',
                'ip_address' => '192.168.1.15',
                'transaction_hash' => hash('sha256', $servicioInterurbano->id . 'SEREMI_VALDIVIA' . $adminId . '192.168.1.15'),
            ]
        );

        $servicioInterurbano->sincronizarHitosOperativos();

        // =========================================================================
        // CASO 5: CASO MIXTO - SML CORDILLERANO + TRASLADO INTERURBANO A CURACAUTIN
        // =========================================================================
        $servicioMixto = Servicio::updateOrCreate(
            ['folio' => 'FC-2026-050'],
            [
                'created_by' => $adminId,
                'cliente_nombre' => 'Camila Alarcón Beltrán',
                'cliente_rut' => '18.120.302-5',
                'cliente_telefono' => '+56 9 5541 2290',
                'difunto_nombre' => 'Esteban Rodrigo Alarcón Beltrán',
                'difunto_rut' => '15.340.812-7',
                'escolaridad' => 'Técnica Completa',
                'oficio' => 'Guía de Turismo Aventura Cordillera',
                'causa_sml' => 'Asfixia por inmersión en Río Cautín (Sector Cordillerano Curacautín)',
                'requiere_sml' => true,
                'convenio' => 'afp',
                'urna_id' => $urnas->get('URN-AD-04')?->id ?? Urna::first()?->id,
                'monto_servicio' => 1750000,
                'aporte_convenio' => 580000,
                'copago' => 1170000,
                'cortejo_at' => Carbon::now()->addDays(4)->setHour(14)->setMinute(0),
                'estado' => 'abierto',
            ]
        );

        $pathPdfSmlCuracautin = "legales/{$servicioMixto->id}/orden_autopsia_sml_curacautin.pdf";
        $pdfSmlCuracautinContent = $this->generarPdf(
            'FISCALIA LOCAL DE CURACAUTIN - IX REGION',
            'Parte Judicial Nro. 441/2026 / Seccion Tanatologia SML Temuco',
            [
                'Causa: Hallazgo de cadaver en Ribera Rio Cautin Km 14.',
                'Fallecido: Esteban Rodrigo Alarcon Beltran - RUT 15.340.812-7.',
                'Autopsia Medico Legal Nro. 240-2026 practicada en SML Temuco.',
                'Concluye: Sumersion accidental sin intervencion de terceras personas.',
                'Se autoriza retiro de restos y traslado a comuna de Curacautin.',
                'Fiscal: Abg. Mauricio San Martin V.',
            ]
        );
        Storage::disk($disk)->put($pathPdfSmlCuracautin, $pdfSmlCuracautinContent);

        $pathPdfSeremiCuracautin = "legales/{$servicioMixto->id}/resolucion_seremi_traslado_curacautin.pdf";
        $pdfSeremiCuracautinContent = $this->generarPdf(
            'SEREMI DE SALUD DE LA ARAUCANIA - TRASLADO PROVINCIAL',
            'Autorizacion Sanitaria de Transito Funerario Temuco - Curacautin',
            [
                'Vehiculo: Toyota Hiace Patente JF-KR-32.',
                'Trayecto: Morgue SML Temuco hacia Cementerio Municipal de Curacautin.',
                'Distancia: 89 Kilometros por Ruta CH-181.',
                'Acompañamiento familiar autorizado.',
                'Cumplimiento de norma DTO 357 verificado por inspector sanitario.',
            ]
        );
        Storage::disk($disk)->put($pathPdfSeremiCuracautin, $pdfSeremiCuracautinContent);

        DocumentoLegal::updateOrCreate(
            ['expediente_id' => $servicioMixto->id],
            [
                'contexto_deceso' => DocumentoLegal::CONTEXTO_SML_VIOLENTO,
                'traslado_interurbano' => true,
                'folio_certificado_medico' => null,
                'folio_pase_sepultacion' => 'PS-SML-8830-TEM',
                'ruta_orden_autopsia_sml' => $pathPdfSmlCuracautin,
                'ruta_resolucion_seremi' => $pathPdfSeremiCuracautin,
                'ruta_cesion_derechos' => null,
                'testigos_rurales' => null,
            ]
        );

        Traslado::updateOrCreate(
            ['servicio_id' => $servicioMixto->id],
            [
                'vehiculo_id' => $vehiculos->get('JF-KR-32')?->id ?? $vehiculos->first()?->id,
                'chofer_id' => $choferes->get('Patricio Riquelme')?->id ?? $choferes->first()?->id,
                'cementerio' => 'Cementerio Municipal de Curacautin (Pabellón Cordillera)',
                'salida_at' => Carbon::now()->addDays(4)->setHour(14)->setMinute(0),
                'implementos' => [
                    'Sello de transporte hermético',
                    'Orden Judicial Fiscalía SML original',
                    'Resolución de tránsito Seremi Temuco-Curacautin',
                ],
                'estado' => 'programado',
            ]
        );

        HistorialAuditoria::firstOrCreate(
            [
                'expediente_id' => $servicioMixto->id,
                'action_type' => 'UPDATE',
                'target_entity' => 'documentos_legales',
            ],
            [
                'user_id' => $adminId,
                'descripcion' => 'Carga conjunta de Orden de Fiscalía SML y Resolución Seremi para Traslado Cordillerano Curacautín',
                'ip_address' => '192.168.1.15',
                'transaction_hash' => hash('sha256', $servicioMixto->id . 'MIXTO_CURACAUTIN' . $adminId . '192.168.1.15'),
            ]
        );

        $servicioMixto->sincronizarHitosOperativos();

        // Asegurar que los servicios previos de FlotaSeeder también tengan DocumentoLegal
        $previos = Servicio::whereNotIn('folio', [
            'FC-2026-010', 'FC-2026-020', 'FC-2026-030', 'FC-2026-040', 'FC-2026-050',
        ])->get();

        foreach ($previos as $srv) {
            DocumentoLegal::firstOrCreate(
                ['expediente_id' => $srv->id],
                [
                    'contexto_deceso' => DocumentoLegal::CONTEXTO_NATURAL,
                    'traslado_interurbano' => false,
                    'folio_certificado_medico' => 'CMD-2026-' . str_pad((string) $srv->id, 5, '0', STR_PAD_LEFT),
                    'folio_pase_sepultacion' => 'PS-TEM-' . str_pad((string) $srv->id, 4, '0', STR_PAD_LEFT),
                ]
            );
        }
    }

    /**
     * Generador de PDF válido (PDF 1.4) puro sin dependencias externas
     */
    private function generarPdf(string $titulo, string $subtitulo, array $lineas): string
    {
        $contentStream = "BT\n/F1 13 Tf\n50 720 Td\n(" . addcslashes($titulo, "()\\") . ") Tj\n";
        $contentStream .= "0 -22 Td\n/F1 10 Tf\n(" . addcslashes($subtitulo, "()\\") . ") Tj\n";
        $contentStream .= "0 -28 Td\n/F1 9 Tf\n";
        foreach ($lineas as $linea) {
            $contentStream .= "(" . addcslashes($linea, "()\\") . ") Tj\n0 -16 Td\n";
        }
        $contentStream .= "ET\n";

        $len = strlen($contentStream);

        $obj1 = "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
        $obj2 = "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
        $obj3 = "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>\nendobj\n";
        $obj4 = "4 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n";
        $obj5 = "5 0 obj\n<< /Length {$len} >>\nstream\n{$contentStream}endstream\nendobj\n";

        $pdfHeader = "%PDF-1.4\n";
        $offset1 = strlen($pdfHeader);
        $offset2 = $offset1 + strlen($obj1);
        $offset3 = $offset2 + strlen($obj2);
        $offset4 = $offset3 + strlen($obj3);
        $offset5 = $offset4 + strlen($obj4);
        $xrefOffset = $offset5 + strlen($obj5);

        $xref = "xref\n0 6\n" .
            "0000000000 65535 f \n" .
            sprintf("%010d 00000 n \n", $offset1) .
            sprintf("%010d 00000 n \n", $offset2) .
            sprintf("%010d 00000 n \n", $offset3) .
            sprintf("%010d 00000 n \n", $offset4) .
            sprintf("%010d 00000 n \n", $offset5);

        $trailer = "trailer\n<< /Size 6 /Root 1 0 R >>\nstartxref\n{$xrefOffset}\n%%EOF";

        return $pdfHeader . $obj1 . $obj2 . $obj3 . $obj4 . $obj5 . $xref . $trailer;
    }
}

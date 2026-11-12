<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('documentos_legales', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('expediente_id')->constrained('servicios')->onDelete('cascade');
            $table->enum('contexto_deceso', ['NATURAL', 'SML_VIOLENTO', 'RURAL_TESTIGOS'])->default('NATURAL');
            $table->boolean('traslado_interurbano')->default(false);

            // Campos de texto para folios (Se encriptarán en el Modelo vía AES-256)
            $table->text('folio_certificado_medico')->nullable();
            $table->text('folio_pase_sepultacion')->nullable();

            // Rutas a los archivos físicos (S3 / almacenamiento privado)
            $table->string('ruta_orden_autopsia_sml')->nullable();
            $table->string('ruta_resolucion_seremi')->nullable();
            $table->string('ruta_cesion_derechos')->nullable();

            // JSON para almacenar testigos si es zona rural
            $table->json('testigos_rurales')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documentos_legales');
    }
};

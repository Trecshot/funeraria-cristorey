<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Agregar campo urna_id a servicios si no existe
        if (! Schema::hasColumn('servicios', 'urna_id')) {
            Schema::table('servicios', function (Blueprint $table) {
                $table->foreignId('urna_id')
                    ->nullable()
                    ->after('convenio')
                    ->constrained('urnas')
                    ->nullOnDelete();
            });
        }

        // 2. Crear tabla para registrar la trazabilidad cronológica de los 5 hitos
        Schema::create('historial_servicios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('servicio_id')->constrained('servicios')->cascadeOnDelete();
            $table->string('hito'); // apertura, logistica_urna, documental, cortejo_flota, finalizado
            $table->string('titulo');
            $table->text('descripcion')->nullable();
            $table->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('completado_at')->nullable();
            $table->timestamps();

            $table->index(['servicio_id', 'hito']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historial_servicios');

        if (Schema::hasColumn('servicios', 'urna_id')) {
            Schema::table('servicios', function (Blueprint $table) {
                $table->dropForeign(['urna_id']);
                $table->dropColumn('urna_id');
            });
        }
    }
};

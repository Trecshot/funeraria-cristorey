<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('servicios', function (Blueprint $table) {
            $table->id();
            $table->string('folio')->unique();
            $table->string('cliente_nombre');
            $table->string('cliente_rut')->nullable();
            $table->string('cliente_telefono')->nullable();
            $table->string('difunto_nombre');
            $table->string('difunto_rut')->nullable();
            $table->string('escolaridad')->nullable();
            $table->string('oficio')->nullable();
            $table->text('causa_sml')->nullable();
            $table->boolean('requiere_sml')->default(false);
            $table->dateTime('cortejo_at')->nullable();
            $table->string('convenio')->default('particular');
            $table->unsignedBigInteger('monto_servicio')->default(0);
            $table->unsignedBigInteger('aporte_convenio')->default(0);
            $table->unsignedBigInteger('copago')->default(0);
            $table->unsignedInteger('uf_value_clp')->nullable();
            $table->string('estado')->default('abierto');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['estado', 'cortejo_at']);
        });

        Schema::create('documentos_servicio', function (Blueprint $table) {
            $table->id();
            $table->foreignId('servicio_id')->constrained('servicios')->cascadeOnDelete();
            $table->string('tipo');
            $table->string('ruta');
            $table->string('nombre_original');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['servicio_id', 'tipo']);
        });

        Schema::create('productos_bodega', function (Blueprint $table) {
            $table->id();
            $table->string('modelo');
            $table->string('color')->nullable();
            $table->unsignedInteger('stock_salon')->default(0);
            $table->unsignedInteger('stock_taller')->default(0);
            $table->unsignedInteger('stock_minimo')->default(1);
            $table->boolean('publicado')->default(false);
            $table->timestamps();
            $table->index(['publicado', 'modelo']);
        });

        Schema::create('vehiculos', function (Blueprint $table) {
            $table->id();
            $table->string('patente')->unique();
            $table->string('nombre');
            $table->string('estado')->default('disponible');
            $table->timestamps();
        });

        Schema::create('traslados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('servicio_id')->nullable()->constrained('servicios')->nullOnDelete();
            $table->foreignId('vehiculo_id')->nullable()->constrained('vehiculos')->nullOnDelete();
            $table->foreignId('chofer_id')->constrained('users')->cascadeOnDelete();
            $table->string('cementerio');
            $table->dateTime('salida_at');
            $table->json('implementos')->nullable();
            $table->string('estado')->default('programado');
            $table->timestamps();
            $table->index(['chofer_id', 'salida_at', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('traslados');
        Schema::dropIfExists('vehiculos');
        Schema::dropIfExists('productos_bodega');
        Schema::dropIfExists('documentos_servicio');
        Schema::dropIfExists('servicios');
    }
};

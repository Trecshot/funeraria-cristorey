<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('liquidaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('servicio_id')->unique()->constrained('servicios')->cascadeOnDelete();
            $table->string('folio_factura')->nullable()->index();
            $table->string('folio_boleta')->nullable()->index();
            $table->unsignedBigInteger('monto_total')->default(0);
            $table->unsignedBigInteger('monto_subvencion')->default(0);
            $table->unsignedBigInteger('monto_copago_familia')->default(0);
            $table->unsignedBigInteger('monto_copago_pagado')->default(0);
            $table->string('estado_subvencion')->default('tramitacion'); // tramitacion, aprobado, rechazado, liquidado
            $table->string('estado_copago')->default('pendiente'); // pendiente, abonado, pagado_total
            $table->string('metodo_pago_familia')->nullable(); // Transferencia, Transbank, Efectivo, etc.
            $table->dateTime('fecha_liquidacion')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->index(['estado_subvencion', 'estado_copago']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('liquidaciones');
    }
};

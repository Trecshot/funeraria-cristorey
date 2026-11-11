<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('liquidaciones', 'estado_general')) {
            Schema::table('liquidaciones', function (Blueprint $table) {
                $table->string('estado_general')->default('pendiente')->after('estado_copago');
                $table->index('estado_general');
            });
        }

        Schema::create('cuotas_pago', function (Blueprint $table) {
            $table->id();
            $table->foreignId('liquidacion_id')->constrained('liquidaciones')->cascadeOnDelete();
            $table->unsignedInteger('numero_cuota');
            $table->unsignedBigInteger('monto_cuota');
            $table->date('fecha_vencimiento');
            $table->date('fecha_pago')->nullable();
            $table->string('estado')->default('pendiente'); // pendiente, pagada, atrasada
            $table->string('metodo_pago')->nullable(); // Transferencia, Transbank, Efectivo, etc.
            $table->string('comprobante_referencia')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->index(['liquidacion_id', 'estado']);
            $table->index(['estado', 'fecha_vencimiento']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cuotas_pago');

        if (Schema::hasColumn('liquidaciones', 'estado_general')) {
            Schema::table('liquidaciones', function (Blueprint $table) {
                $table->dropColumn('estado_general');
            });
        }
    }
};

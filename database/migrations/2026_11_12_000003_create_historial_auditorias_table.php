<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('historial_auditorias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expediente_id')->constrained('servicios')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action_type', 30)->default('UPDATE');
            $table->string('target_entity', 60)->default('documentos_legales');
            $table->text('descripcion');
            $table->string('ip_address', 45)->nullable();
            $table->string('transaction_hash', 64)->nullable();
            $table->timestamps();

            $table->index(['expediente_id', 'target_entity']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historial_auditorias');
    }
};

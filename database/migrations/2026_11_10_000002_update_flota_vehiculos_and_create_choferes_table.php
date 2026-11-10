<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehiculos', function (Blueprint $table) {
            if (! Schema::hasColumn('vehiculos', 'modelo_uso')) {
                $table->string('modelo_uso', 150)->nullable()->after('patente');
            }
            if (! Schema::hasColumn('vehiculos', 'apto_rural')) {
                $table->boolean('apto_rural')->default(false)->after('nombre');
            }
            if (! Schema::hasColumn('vehiculos', 'licencia_requerida')) {
                $table->string('licencia_requerida', 10)->default('A2')->after('apto_rural');
            }
            if (! Schema::hasColumn('vehiculos', 'kilometraje')) {
                $table->unsignedInteger('kilometraje')->default(0)->after('estado');
            }
        });

        // Sincronizar modelo_uso con nombre si ya existían registros
        DB::statement('UPDATE vehiculos SET modelo_uso = nombre WHERE modelo_uso IS NULL AND nombre IS NOT NULL');

        if (! Schema::hasTable('choferes')) {
            Schema::create('choferes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('nombre_completo', 150);
                $table->string('rut', 20)->unique();
                $table->string('telefono', 30)->nullable();
                $table->string('licencia_clase', 10)->default('A2');
                $table->enum('estado', ['Disponible', 'Asignado en Ruta', 'Descanso', 'Permiso'])->default('Disponible');
                $table->foreignId('vehiculo_asignado_id')->nullable()->constrained('vehiculos')->nullOnDelete();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('choferes');

        Schema::table('vehiculos', function (Blueprint $table) {
            $columns = array_filter(
                ['modelo_uso', 'apto_rural', 'licencia_requerida', 'kilometraje'],
                fn ($col) => Schema::hasColumn('vehiculos', $col)
            );
            if (! empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};

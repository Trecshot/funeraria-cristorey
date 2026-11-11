<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('rut', 15)->nullable()->unique()->after('name');
            $table->string('telefono', 25)->nullable()->after('email');
            $table->string('tipo_licencia', 10)->nullable()->after('role'); // A1, A2, A3, B
            $table->string('sede_asignada', 50)->default('Calle Blanco')->after('tipo_licencia');
            $table->string('estado_operativo', 50)->default('Disponible')->after('sede_asignada');
            $table->string('estado_cuenta', 30)->default('activa')->after('estado_operativo');
        });

        // Población inicial coherente para los usuarios existentes de Funeraria Cristo Rey
        DB::table('users')->where('email', 'admin@funeraria-cristorey.local')->update([
            'rut' => '10.540.320-1',
            'telefono' => '+56 9 9000 0001',
            'sede_asignada' => 'Calle Blanco',
            'estado_operativo' => 'Disponible',
            'estado_cuenta' => 'activa',
        ]);

        DB::table('users')->where('email', 'renato.osses@cristorey.cl')->update([
            'rut' => '14.285.912-4',
            'telefono' => '+56 9 8412 5590',
            'sede_asignada' => 'Calle Blanco',
            'estado_operativo' => 'Disponible',
            'estado_cuenta' => 'activa',
        ]);

        DB::table('users')->where('email', 'carlos.soto@cristorey.cl')->update([
            'rut' => '16.742.109-8',
            'telefono' => '+56 9 9345 1120',
            'tipo_licencia' => 'A2',
            'sede_asignada' => 'Calle Blanco',
            'estado_operativo' => 'En Ruta',
            'estado_cuenta' => 'activa',
        ]);

        DB::table('users')->where('email', 'esteban.morales@cristorey.cl')->update([
            'rut' => '15.932.884-1',
            'telefono' => '+56 9 8223 4491',
            'tipo_licencia' => 'A2',
            'sede_asignada' => 'Calle Blanco',
            'estado_operativo' => 'Disponible',
            'estado_cuenta' => 'activa',
        ]);

        DB::table('users')->where('email', 'patricio.riquelme@cristorey.cl')->update([
            'rut' => '13.882.451-9',
            'telefono' => '+56 9 7611 2309',
            'tipo_licencia' => 'A3',
            'sede_asignada' => 'Taller',
            'estado_operativo' => 'Disponible',
            'estado_cuenta' => 'activa',
        ]);

        DB::table('users')->where('email', 'hector.curamil@cristorey.cl')->update([
            'rut' => '17.102.390-3',
            'telefono' => '+56 9 9410 8821',
            'tipo_licencia' => 'A2',
            'sede_asignada' => 'Ambas',
            'estado_operativo' => 'Vacaciones',
            'estado_cuenta' => 'activa',
        ]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'rut',
                'telefono',
                'tipo_licencia',
                'sede_asignada',
                'estado_operativo',
                'estado_cuenta',
            ]);
        });
    }
};

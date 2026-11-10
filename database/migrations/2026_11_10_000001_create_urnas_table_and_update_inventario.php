<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('productos_bodega') && ! Schema::hasTable('urnas')) {
            Schema::rename('productos_bodega', 'urnas');
        } elseif (! Schema::hasTable('urnas')) {
            Schema::create('urnas', function (Blueprint $table) {
                $table->id();
                $table->string('codigo', 50)->unique();
                $table->string('modelo', 100);
                $table->string('color', 50);
                $table->string('tallado', 100)->nullable();
                $table->enum('categoria_etaria', ['Niño', 'Juvenil', 'Adulto', 'Especial'])->default('Adulto');
                $table->string('medidas_cm', 50)->default('1.90m x 60cm');
                $table->boolean('personalizada')->default(false);
                $table->decimal('precio', 10, 2)->default(0);
                $table->unsignedInteger('stock_salon')->default(0);
                $table->unsignedInteger('stock_taller')->default(0);
                $table->unsignedInteger('stock_minimo')->default(1);
                $table->boolean('mostrar_catalogo')->default(true);
                $table->boolean('publicado')->default(true);
                $table->timestamps();
            });
        }

        Schema::table('urnas', function (Blueprint $table) {
            if (! Schema::hasColumn('urnas', 'codigo')) {
                $table->string('codigo', 50)->nullable()->unique()->after('id');
            }
            if (! Schema::hasColumn('urnas', 'categoria_etaria')) {
                $table->enum('categoria_etaria', ['Niño', 'Juvenil', 'Adulto', 'Especial'])->default('Adulto')->after('color');
            }
            if (! Schema::hasColumn('urnas', 'medidas_cm')) {
                $table->string('medidas_cm', 50)->default('1.90m x 60cm')->after('categoria_etaria');
            }
            if (! Schema::hasColumn('urnas', 'personalizada')) {
                $table->boolean('personalizada')->default(false)->after('medidas_cm');
            }
            if (! Schema::hasColumn('urnas', 'precio')) {
                $table->decimal('precio', 10, 2)->default(0)->after('personalizada');
            }
            if (! Schema::hasColumn('urnas', 'mostrar_catalogo')) {
                $table->boolean('mostrar_catalogo')->default(true)->after('stock_minimo');
            }
            if (! Schema::hasColumn('urnas', 'tallado')) {
                $table->string('tallado', 100)->nullable()->after('color');
            }
            if (! Schema::hasColumn('urnas', 'publicado')) {
                $table->boolean('publicado')->default(true)->after('mostrar_catalogo');
            }
        });

        // Asegurar compatibilidad en movimientos_bodega
        if (Schema::hasTable('movimientos_bodega')) {
            Schema::table('movimientos_bodega', function (Blueprint $table) {
                if (! Schema::hasColumn('movimientos_bodega', 'urna_id')) {
                    $table->unsignedBigInteger('urna_id')->nullable()->after('producto_bodega_id');
                }
            });

            DB::statement('UPDATE movimientos_bodega SET urna_id = producto_bodega_id WHERE urna_id IS NULL AND producto_bodega_id IS NOT NULL');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('urnas') && ! Schema::hasTable('productos_bodega')) {
            Schema::rename('urnas', 'productos_bodega');
        }
    }
};

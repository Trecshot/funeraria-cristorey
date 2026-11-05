<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('servicios', function (Blueprint $table) {
            if (! Schema::hasColumn('servicios', 'folio')) {
                $table->string('folio')->nullable()->unique();
            }
            if (! Schema::hasColumn('servicios', 'cliente_nombre')) {
                $table->string('cliente_nombre')->nullable();
            }
            if (! Schema::hasColumn('servicios', 'cliente_telefono')) {
                $table->string('cliente_telefono')->nullable();
            }
            if (! Schema::hasColumn('servicios', 'difunto_nombre')) {
                $table->string('difunto_nombre')->nullable();
            }
            if (! Schema::hasColumn('servicios', 'difunto_rut')) {
                $table->string('difunto_rut')->nullable();
            }
            if (! Schema::hasColumn('servicios', 'requiere_sml')) {
                $table->boolean('requiere_sml')->default(false);
            }
            if (! Schema::hasColumn('servicios', 'monto_servicio')) {
                $table->unsignedBigInteger('monto_servicio')->default(0);
            }
            if (! Schema::hasColumn('servicios', 'aporte_convenio')) {
                $table->unsignedBigInteger('aporte_convenio')->default(0);
            }
            if (! Schema::hasColumn('servicios', 'copago')) {
                $table->unsignedBigInteger('copago')->default(0);
            }
            if (! Schema::hasColumn('servicios', 'uf_value_clp')) {
                $table->unsignedInteger('uf_value_clp')->nullable();
            }
        });

        if (Schema::hasColumn('servicios', 'cliente_contratante')) {
            DB::table('servicios')->whereNull('cliente_nombre')
                ->update(['cliente_nombre' => DB::raw('cliente_contratante')]);
        }
        if (Schema::hasColumn('servicios', 'telefono_cliente')) {
            DB::table('servicios')->whereNull('cliente_telefono')
                ->update(['cliente_telefono' => DB::raw('telefono_cliente')]);
        }
        if (Schema::hasColumn('servicios', 'difunto')) {
            DB::table('servicios')->whereNull('difunto_nombre')
                ->update(['difunto_nombre' => DB::raw('difunto')]);
        }
        if (Schema::hasColumn('servicios', 'total_servicio')) {
            DB::table('servicios')->where('monto_servicio', 0)
                ->update(['monto_servicio' => DB::raw('total_servicio')]);
        }
        DB::table('servicios')->whereNull('folio')
            ->update(['folio' => DB::raw("CONCAT('FC-', id)")]);

        Schema::table('documentos_servicio', function (Blueprint $table) {
            if (! Schema::hasColumn('documentos_servicio', 'path')) {
                $table->string('path')->nullable();
            }
            if (! Schema::hasColumn('documentos_servicio', 'disk')) {
                $table->string('disk')->nullable();
            }
        });

        if (Schema::hasColumn('documentos_servicio', 'ruta')) {
            DB::table('documentos_servicio')->whereNull('path')
                ->update(['path' => DB::raw('ruta')]);
            DB::table('documentos_servicio')->whereNull('disk')->update(['disk' => 'local']);
        }

        Schema::table('productos_bodega', function (Blueprint $table) {
            if (! Schema::hasColumn('productos_bodega', 'tallado')) {
                $table->string('tallado')->nullable();
            }
            if (! Schema::hasColumn('productos_bodega', 'stock_salon')) {
                $table->unsignedInteger('stock_salon')->default(0);
            }
            if (! Schema::hasColumn('productos_bodega', 'stock_taller')) {
                $table->unsignedInteger('stock_taller')->default(0);
            }
        });

        if (Schema::hasColumn('productos_bodega', 'stock') && Schema::hasColumn('productos_bodega', 'ubicacion')) {
            DB::table('productos_bodega')->where('ubicacion', 'salon')
                ->update(['stock_salon' => DB::raw('stock')]);
            DB::table('productos_bodega')->where('ubicacion', 'taller')
                ->update(['stock_taller' => DB::raw('stock')]);
        }

        Schema::table('vehiculos', function (Blueprint $table) {
            if (! Schema::hasColumn('vehiculos', 'nombre')) {
                $table->string('nombre')->nullable();
            }
        });
        if (Schema::hasColumn('vehiculos', 'modelo')) {
            DB::table('vehiculos')->whereNull('nombre')
                ->update(['nombre' => DB::raw('modelo')]);
        }

        Schema::table('traslados', function (Blueprint $table) {
            if (! Schema::hasColumn('traslados', 'cementerio')) {
                $table->string('cementerio')->nullable();
            }
        });
        if (Schema::hasColumn('traslados', 'destino')) {
            DB::table('traslados')->whereNull('cementerio')
                ->update(['cementerio' => DB::raw('destino')]);
        }

        $choferColumn = DB::selectOne("SHOW COLUMNS FROM traslados LIKE 'chofer_id'");
        if ($choferColumn && $choferColumn->Null === 'NO') {
            Schema::table('traslados', function (Blueprint $table) {
                $table->unsignedBigInteger('chofer_id')->nullable()->change();
            });
        }

        if (! Schema::hasTable('movimientos_bodega')) {
            Schema::create('movimientos_bodega', function (Blueprint $table) {
                $table->id();
                $table->foreignId('producto_bodega_id')->constrained('productos_bodega')->restrictOnDelete();
                $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
                $table->string('origen');
                $table->string('destino');
                $table->unsignedInteger('cantidad');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos_bodega');
    }
};

<?php

namespace Database\Seeders;

use App\Models\Chofer;
use App\Models\Servicio;
use App\Models\Traslado;
use App\Models\User;
use App\Models\Vehiculo;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class FlotaSeeder extends Seeder
{
    public function run(): void
    {
        $vehiculosData = [
            [
                'patente' => 'KH-TY-18',
                'nombre' => 'Mercedes Benz (Carroza Ceremonial Principal)',
                'modelo_uso' => 'Mercedes Benz · Carroza Ceremonial principal',
                'apto_rural' => false,
                'licencia_requerida' => 'A2',
                'estado' => 'Disponible',
                'kilometraje' => 42300,
            ],
            [
                'patente' => 'GH-YR-42',
                'nombre' => 'Furgón de Instalación (Apto barro rural / Carga urnas)',
                'modelo_uso' => 'Furgón de Instalación · Apto barro rural / Carga urnas',
                'apto_rural' => true,
                'licencia_requerida' => 'A2',
                'estado' => 'En Ruta',
                'kilometraje' => 88450,
            ],
            [
                'patente' => 'JF-KR-32',
                'nombre' => 'Toyota Hiace Van Alta (Apto barro rural / SML)',
                'modelo_uso' => 'Toyota Hiace Van Alta · Apto barro rural y traslados',
                'apto_rural' => true,
                'licencia_requerida' => 'A2',
                'estado' => 'Disponible',
                'kilometraje' => 64120,
            ],
            [
                'patente' => 'JK-LP-99',
                'nombre' => 'Furgón Auxiliar Sede (Sede Calle Blanco)',
                'modelo_uso' => 'Furgón Auxiliar Sede · Sede Calle Blanco',
                'apto_rural' => false,
                'licencia_requerida' => 'A1',
                'estado' => 'Mantenimiento',
                'kilometraje' => 115800,
            ],
        ];

        $vehiculosMap = [];
        foreach ($vehiculosData as $v) {
            $vehiculo = Vehiculo::updateOrCreate(
                ['patente' => $v['patente']],
                $v
            );
            $vehiculosMap[$v['patente']] = $vehiculo;
        }

        // 4 Conductores / Choferes
        $choferesData = [
            [
                'nombre_completo' => 'Carlos Soto',
                'rut' => '14.582.391-4',
                'telefono' => '+56 9 8412 3301',
                'licencia_clase' => 'A2',
                'estado' => 'Disponible',
                'patente_asignada' => 'KH-TY-18',
            ],
            [
                'nombre_completo' => 'Esteban Morales',
                'rut' => '16.204.883-K',
                'telefono' => '+56 9 7654 1190',
                'licencia_clase' => 'A2',
                'estado' => 'Asignado en Ruta',
                'patente_asignada' => 'GH-YR-42',
            ],
            [
                'nombre_completo' => 'Patricio Riquelme',
                'rut' => '13.890.112-7',
                'telefono' => '+56 9 9102 4487',
                'licencia_clase' => 'A3',
                'estado' => 'Disponible',
                'patente_asignada' => 'JF-KR-32',
            ],
            [
                'nombre_completo' => 'Héctor Curamil',
                'rut' => '17.441.920-1',
                'telefono' => '+56 9 6338 5520',
                'licencia_clase' => 'A1',
                'estado' => 'Descanso',
                'patente_asignada' => null,
            ],
        ];

        $usuariosChoferes = [];
        foreach ($choferesData as $c) {
            $email = Str::slug($c['nombre_completo'], '.').'@cristorey.cl';
            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $c['nombre_completo'],
                    'password' => 'chofer123',
                    'role' => 'chofer',
                ]
            );
            $usuariosChoferes[$c['nombre_completo']] = $user;

            $vehiculoId = $c['patente_asignada'] && isset($vehiculosMap[$c['patente_asignada']])
                ? $vehiculosMap[$c['patente_asignada']]->id
                : null;

            Chofer::updateOrCreate(
                ['rut' => $c['rut']],
                [
                    'user_id' => $user->id,
                    'nombre_completo' => $c['nombre_completo'],
                    'telefono' => $c['telefono'],
                    'licencia_clase' => $c['licencia_clase'],
                    'estado' => $c['estado'],
                    'vehiculo_asignado_id' => $vehiculoId,
                ]
            );
        }

        // Si no existen traslados programados, crear 2 traslados operativos de demostración
        if (Traslado::count() === 0) {
            $admin = User::where('role', 'administrador')->first() ?? User::first();
            if ($admin) {
                $servicio1 = Servicio::firstOrCreate(
                    ['folio' => 'FC-2026-001'],
                    [
                        'created_by' => $admin->id,
                        'cliente_nombre' => 'Familia Morales Huenchulaf',
                        'cliente_rut' => '12.345.678-9',
                        'cliente_telefono' => '+56 9 8765 4321',
                        'difunto_nombre' => 'Pedro Morales Huenchulaf',
                        'difunto_rut' => '7.890.123-4',
                        'convenio' => 'ips',
                        'monto_servicio' => 1450000,
                        'aporte_convenio' => 1070000,
                        'copago' => 380000,
                        'cortejo_at' => now()->addHours(3),
                        'estado' => 'en_preparacion',
                    ]
                );

                Traslado::create([
                    'servicio_id' => $servicio1->id,
                    'vehiculo_id' => $vehiculosMap['GH-YR-42']->id,
                    'chofer_id' => $usuariosChoferes['Esteban Morales']->id,
                    'cementerio' => 'Cementerio Rural Cholchol (Camino Tierra)',
                    'salida_at' => now()->addHours(3),
                    'implementos' => ['Cruz grabada', 'Tarjetas', 'Polietileno (Nailon rural)'],
                    'estado' => 'en_camino',
                ]);

                $servicio2 = Servicio::firstOrCreate(
                    ['folio' => 'FC-2026-002'],
                    [
                        'created_by' => $admin->id,
                        'cliente_nombre' => 'María Elena Sepúlveda',
                        'cliente_rut' => '11.223.344-5',
                        'cliente_telefono' => '+56 9 9988 7766',
                        'difunto_nombre' => 'Jorge Alberto Sepúlveda Ríos',
                        'difunto_rut' => '6.543.210-K',
                        'convenio' => 'afp',
                        'monto_servicio' => 1680000,
                        'aporte_convenio' => 570000,
                        'copago' => 1110000,
                        'cortejo_at' => now()->addDay()->setHour(11)->setMinute(0),
                        'estado' => 'abierto',
                    ]
                );

                Traslado::create([
                    'servicio_id' => $servicio2->id,
                    'vehiculo_id' => $vehiculosMap['KH-TY-18']->id,
                    'chofer_id' => $usuariosChoferes['Carlos Soto']->id,
                    'cementerio' => 'Parque del Sendero Temuco',
                    'salida_at' => now()->addDay()->setHour(11)->setMinute(0),
                    'implementos' => ['Cruz grabada', 'Tarjetas', 'Libro de condolencias'],
                    'estado' => 'programado',
                ]);
            }
        }
    }
}

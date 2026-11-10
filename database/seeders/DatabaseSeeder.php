<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'renato.osses@cristorey.cl'],
            [
                'name' => 'Renato Osses',
                'password' => 'admin123',
                'role' => 'administrador',
            ]
        );

        $this->call([
            UrnaSeeder::class,
            FlotaSeeder::class,
        ]);
    }
}

<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CrearUsuarioCommand extends Command
{
    protected $signature = 'usuario:crear {nombre} {email} {--rol=administrador}';

    protected $description = 'Crea un usuario para acceder al sistema';

    public function handle(): int
    {
        $roles = ['administrador', 'gerencia', 'asistente', 'bodega', 'chofer', 'finanzas'];
        $data = [
            'name' => $this->argument('nombre'),
            'email' => $this->argument('email'),
            'role' => $this->option('rol'),
        ];

        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', Rule::in($roles)],
        ]);

        if ($validator->fails()) {
            $this->components->error($validator->errors()->first());

            return self::FAILURE;
        }

        $password = $this->secret('Contrasena (minimo 12 caracteres)');
        if (mb_strlen($password) < 12) {
            $this->components->error('La contrasena debe tener al menos 12 caracteres.');

            return self::FAILURE;
        }

        if ($password !== $this->secret('Confirma la contrasena')) {
            $this->components->error('Las contrasenas no coinciden.');

            return self::FAILURE;
        }

        User::create($data + ['password' => $password]);
        $this->components->info("Usuario {$data['email']} creado con rol {$data['role']}.");

        return self::SUCCESS;
    }
}

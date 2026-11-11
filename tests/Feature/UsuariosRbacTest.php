<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UsuariosRbacTest extends TestCase
{
    use RefreshDatabase;

    public function test_usuarios_index_displays_contacto_sede_and_laboral_status(): void
    {
        $admin = User::factory()->create([
            'role' => 'administrador',
            'name' => 'Renato Osses',
            'email' => 'renato@cristorey.cl',
            'rut' => '14.285.912-4',
            'telefono' => '+56 9 8412 5590',
            'sede_asignada' => 'Calle Blanco',
            'estado_operativo' => 'Disponible',
            'estado_cuenta' => 'activa',
        ]);

        $chofer = User::factory()->create([
            'role' => 'chofer',
            'name' => 'Carlos Soto',
            'email' => 'carlos.soto@cristorey.cl',
            'rut' => '16.742.109-8',
            'telefono' => '+56 9 9345 1120',
            'tipo_licencia' => 'A2',
            'sede_asignada' => 'Calle Blanco',
            'estado_operativo' => 'En Ruta',
            'estado_cuenta' => 'activa',
        ]);

        $response = $this->actingAs($admin)->get(route('usuarios.index'));
        $response->assertOk();
        $response->assertSee('Contacto & Sede', false);
        $response->assertSee('Estado Laboral & Cuenta', false);
        $response->assertSee('Credenciales de Acceso (Sistema)');
        $response->assertSee('Datos del Empleado (Operativa)');
        $response->assertSee('+56 9 8412 5590');
        $response->assertSee('Salón Calle Blanco');
        $response->assertSee('En Ruta');
        $response->assertSee('Licencia A2');
    }

    public function test_admin_can_register_new_employee_user_with_rut_sede_and_licencia(): void
    {
        $admin = User::factory()->create(['role' => 'administrador']);

        $response = $this->actingAs($admin)->post(route('usuarios.store'), [
            'name' => 'Carlos Muñoz',
            'email' => 'carlos.munoz@cristorey.cl',
            'role' => 'chofer',
            'rut' => '12.345.678-9',
            'telefono' => '+56 9 8412 5590',
            'sede_asignada' => 'Calle Blanco',
            'tipo_licencia' => 'A2',
            'estado_operativo' => 'Disponible',
            'password' => 'segura-clave-1234',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', [
            'name' => 'Carlos Muñoz',
            'email' => 'carlos.munoz@cristorey.cl',
            'role' => 'chofer',
            'rut' => '12.345.678-9',
            'telefono' => '+56 9 8412 5590',
            'sede_asignada' => 'Calle Blanco',
            'tipo_licencia' => 'A2',
            'estado_operativo' => 'Disponible',
            'estado_cuenta' => 'activa',
        ]);
    }

    public function test_admin_registering_non_chofer_sets_licencia_to_null(): void
    {
        $admin = User::factory()->create(['role' => 'administrador']);

        $response = $this->actingAs($admin)->post(route('usuarios.store'), [
            'name' => 'Ana Asistente',
            'email' => 'ana.asistente@cristorey.cl',
            'role' => 'asistente',
            'rut' => '18.432.109-5',
            'telefono' => '+56 9 7711 2233',
            'sede_asignada' => 'Taller',
            'tipo_licencia' => 'A2', // No aplica para rol asistente
            'password' => 'segura-clave-1234',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'email' => 'ana.asistente@cristorey.cl',
            'role' => 'asistente',
            'tipo_licencia' => null,
        ]);
    }

    public function test_chofer_or_unauthorized_role_cannot_access_usuarios_module(): void
    {
        $chofer = User::factory()->create(['role' => 'chofer']);

        $this->actingAs($chofer)
            ->get(route('usuarios.index'))
            ->assertForbidden();

        $this->actingAs($chofer)
            ->post(route('usuarios.store'), [
                'name' => 'Hacker',
                'email' => 'hack@example.com',
                'password' => 'password12345',
            ])
            ->assertForbidden();
    }
}

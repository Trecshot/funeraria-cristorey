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

    public function test_admin_can_update_estado_operativo(): void
    {
        $admin = User::factory()->create(['role' => 'administrador']);
        $empleado = User::factory()->create([
            'role' => 'chofer',
            'estado_operativo' => 'Disponible',
        ]);

        $response = $this->actingAs($admin)->patch(route('usuarios.estado', $empleado->id), [
            'estado_operativo' => 'Licencia Médica',
        ]);

        $response->assertRedirect();
        $this->assertEquals('Licencia Médica', $empleado->fresh()->estado_operativo);
    }

    public function test_cannot_change_estado_from_en_ruta_unless_disponible(): void
    {
        $admin = User::factory()->create(['role' => 'administrador']);
        $chofer = User::factory()->create([
            'role' => 'chofer',
            'estado_operativo' => 'En Ruta',
        ]);

        // Intento inválido: pasar de 'En Ruta' a 'Vacaciones'
        $response = $this->actingAs($admin)->patch(route('usuarios.estado', $chofer->id), [
            'estado_operativo' => 'Vacaciones',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error', 'El chofer debe finalizar su ruta antes de cambiar su estado.');
        $this->assertEquals('En Ruta', $chofer->fresh()->estado_operativo);

        // Intento válido: pasar de 'En Ruta' a 'Disponible'
        $responseOk = $this->actingAs($admin)->patch(route('usuarios.estado', $chofer->id), [
            'estado_operativo' => 'Disponible',
        ]);

        $responseOk->assertRedirect();
        $this->assertEquals('Disponible', $chofer->fresh()->estado_operativo);
    }

    public function test_admin_cannot_deactivate_self(): void
    {
        $admin = User::factory()->create(['role' => 'administrador']);

        $response = $this->actingAs($admin)->delete(route('usuarios.destroy', $admin->id));

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Infracción de seguridad: No puede desactivar su propia cuenta.');
        $this->assertNotSoftDeleted('users', ['id' => $admin->id]);
    }

    public function test_admin_can_soft_delete_user_and_revokes_visibility(): void
    {
        $admin = User::factory()->create(['role' => 'administrador']);
        $empleado = User::factory()->create([
            'name' => 'Empleado A Desactivar',
            'email' => 'empleado.baja@cristorey.cl',
            'role' => 'chofer',
        ]);

        $response = $this->actingAs($admin)->delete(route('usuarios.destroy', $empleado->id));

        $response->assertRedirect();
        $this->assertSoftDeleted('users', ['id' => $empleado->id]);

        // Ya no debe aparecer en la lista de usuarios activos
        $indexResponse = $this->actingAs($admin)->get(route('usuarios.index'));
        $indexResponse->assertOk();
        $indexResponse->assertDontSee('empleado.baja@cristorey.cl');
    }

    public function test_admin_can_update_user_master_data(): void
    {
        $admin = User::factory()->create(['role' => 'administrador']);
        $empleado = User::factory()->create([
            'name' => 'Nombre Viejo',
            'email' => 'viejo@cristorey.cl',
            'role' => 'asistente',
            'rut' => '11.222.333-4',
            'sede_asignada' => 'Calle Blanco',
        ]);

        $response = $this->actingAs($admin)->put(route('usuarios.update', $empleado->id), [
            'name' => 'Nombre Nuevo',
            'email' => 'nuevo@cristorey.cl',
            'role' => 'asistente',
            'rut' => '11.222.333-4',
            'telefono' => '+56 9 1234 5678',
            'sede_asignada' => 'Taller',
            'estado_operativo' => 'Disponible',
            'estado_cuenta' => 'activa',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'id' => $empleado->id,
            'name' => 'Nombre Nuevo',
            'email' => 'nuevo@cristorey.cl',
            'sede_asignada' => 'Taller',
            'telefono' => '+56 9 1234 5678',
        ]);
    }
}

<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FunerariaWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_homepage_redirects_guests_to_login(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }

    public function test_user_can_sign_in_and_open_authorized_finance_module(): void
    {
        $user = User::factory()->create([
            'email' => 'finanzas@example.test',
            'password' => 'una-clave-segura',
            'role' => 'finanzas',
        ]);

        $this->get('/login')->assertOk()->assertViewIs('auth.login');

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'una-clave-segura',
        ])->assertRedirect(route('dashboard'));

        $this->get('/finanzas')->assertOk()->assertViewIs('finanzas.index');
        $this->get('/servicios')->assertForbidden();
    }

    public function test_chofer_sign_in_redirects_directly_to_the_restricted_route(): void
    {
        $chofer = User::factory()->create([
            'email' => 'chofer@example.test',
            'password' => 'una-clave-segura',
            'role' => 'chofer',
        ]);

        $this->get('/dashboard')->assertRedirect(route('login'));

        $this->post('/login', [
            'email' => $chofer->email,
            'password' => 'una-clave-segura',
        ])->assertRedirect(route('movil.chofer'));
    }
}

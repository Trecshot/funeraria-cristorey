<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UsuarioController extends Controller
{
    public function index(): View
    {
        return view('usuarios.index', [
            'usuarios' => User::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(User::baseValidationRules() + [
            'password' => ['required', 'string', 'min:12'],
        ]);

        User::create($data);

        return back()->with('status', "Usuario {$data['name']} registrado correctamente con rol {$data['role']}.");
    }
}

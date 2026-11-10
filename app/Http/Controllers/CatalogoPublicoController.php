<?php

namespace App\Http\Controllers;

use App\Models\ProductoBodega;
use Illuminate\View\View;

class CatalogoPublicoController extends Controller
{
    public function index(): View
    {
        return view('catalogo.index', [
            'productos' => ProductoBodega::publicadosConStock()
                ->orderBy('modelo')
                ->get(),
        ]);
    }
}

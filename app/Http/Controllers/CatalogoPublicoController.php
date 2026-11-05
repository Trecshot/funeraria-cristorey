<?php

namespace App\Http\Controllers;

use App\Models\ProductoBodega;
use Illuminate\View\View;

class CatalogoPublicoController extends Controller
{
    public function index(): View
    {
        return view('catalogo.index', [
            'productos' => ProductoBodega::where('publicado', true)
                ->where(function ($query) {
                    $query->where('stock_salon', '>', 0)
                        ->orWhere('stock_taller', '>', 0);
                })
                ->orderBy('modelo')
                ->get(),
        ]);
    }
}

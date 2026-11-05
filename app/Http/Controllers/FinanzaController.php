<?php

namespace App\Http\Controllers;

use App\Models\Servicio;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\View\View;

class FinanzaController extends Controller
{
    public function index(): View
    {
        return view('finanzas.index', [
            'servicios' => Servicio::latest()->paginate(15),
            'ufValue' => config('funeraria.uf_value_clp'),
        ]);
    }

    public function generarPdf(Servicio $servicio): Response
    {
        $pdf = Pdf::loadView('finanzas.comprobante', compact('servicio'));

        return $pdf->stream('comprobante-servicio-'.$servicio->id.'.pdf');
    }
}

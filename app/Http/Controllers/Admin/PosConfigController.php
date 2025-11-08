<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PosConfig;

class PosConfigController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('admin.posconfig.index');
    }

    /**
     * Mostrar formulario unificado de creación.
     */
    public function create()
    {
        return view('admin.posconfig.form');
    }

    /**
     * Mostrar formulario unificado de edición.
     */
    public function edit(PosConfig $posConfig)
    {
        return view('admin.posconfig.form', compact('posConfig'));
    }

    /**
     * Eliminar una configuración de POS.
     */
    public function destroy(PosConfig $posConfig)
    {
        $posConfig->delete();

        session()->flash('swalt', [
            'icon' => 'success',
            'title' => '¡Bien hecho!',
            'text' => 'Configuración de POS ha sido eliminada',
        ]);

        return redirect()->route('admin.posconfig.index');
    }
}

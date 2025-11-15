<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PosConfig;
use Illuminate\Support\Facades\Gate;

class PosConfigController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        Gate::authorize('read_posconfig', PosConfig::class);
        return view('admin.posconfig.index');
    }

    /**
     * Mostrar formulario unificado de creación.
     */
    public function create()
    {
        Gate::authorize('create_posconfig', PosConfig::class);
        return view('admin.posconfig.form');
    }

    /**
     * Mostrar formulario unificado de edición.
     */
    public function edit(PosConfig $posConfig)
    {
        Gate::authorize('update_posconfig', $posConfig);
        return view('admin.posconfig.form', compact('posConfig'));
    }

    /**
     * Eliminar una configuración de POS.
     */
    public function destroy(PosConfig $posConfig)
    {
        Gate::authorize('delete_posconfig', $posConfig);
        $posConfig->delete();

        session()->flash('swalt', [
            'icon' => 'success',
            'title' => '¡Bien hecho!',
            'text' => 'Configuración de POS ha sido eliminada',
        ]);

        return redirect()->route('admin.posconfig.index');
    }
}

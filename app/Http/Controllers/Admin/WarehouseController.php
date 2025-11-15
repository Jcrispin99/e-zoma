<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Gate;


class WarehouseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        Gate::authorize('read_warehouses', Warehouse::class);
        return view('admin.warehouses.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        Gate::authorize('create_warehouses', Warehouse::class);
        return view('admin.warehouses.form');
    }



    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Warehouse $warehouse)
    {
        Gate::authorize('update_warehouses', $warehouse);
        return view('admin.warehouses.form', compact('warehouse'));
    }



    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Warehouse $warehouse)
    {
        Gate::authorize('delete_warehouses', $warehouse);
        if ($warehouse->inventories()->exists()) {
            session()->flash('swalt', [
                'icon' => 'error',
                'title' => 'Error',
                'text' => 'No se puede eliminar el almacen porque tiene productos asociados.',
            ]);
            return redirect()->route('admin.warehouses.index');
        }

        $warehouse->delete();
        session()->flash('swalt', [
            'icon' => 'success',
            'title' => 'Bien',
            'text' => 'Almacen eliminado correctamente.',
        ]);
        return redirect()->route('admin.warehouses.index');
    }
}

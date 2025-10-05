<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        return view('admin.warehouses.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.warehouses.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'location' => 'required|string|max:255',
        ]);

        $activeCompanyId = session('active_company_id');

        if (!$activeCompanyId) {
            session()->flash('swalt', [
                'icon' => 'error',
                'title' => 'Error',
                'text' => 'No hay una compañía activa seleccionada. Por favor, seleccione una compañía antes de crear un almacén.',
            ]);
            return redirect()->back();
        }

        $warehouse = Warehouse::create($data + ['company_id' => $activeCompanyId]);

        session()->flash('swalt', [
            'icon' => 'success',
            'title' => 'Bien',
            'text' => 'Almacen guardado correctamente.',
        ]);
        return redirect()->route('admin.warehouses.edit', $warehouse);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Warehouse $warehouse)
    {
        return view('admin.warehouses.edit', compact('warehouse'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Warehouse $warehouse)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'location' => 'required|string|max:255',
        ]);

        $warehouse->update($data);
        session()->flash('swalt', [
            'icon' => 'success',
            'title' => 'Bien',
            'text' => 'Almacen actualizado correctamente.',
        ]);
        return redirect()->route('admin.warehouses.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Warehouse $warehouse)
    {
        //
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

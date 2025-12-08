<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\Request;
use Inertia\Inertia;

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

    public function indexWeb(Request $request)
    {
        Gate::authorize('read_warehouses', Warehouse::class);
        $query = Warehouse::query();

        if ($request->has('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('location', 'like', "%{$search}%");
        }

        $warehouses = $query->paginate(80);

        return Inertia::render('inventory/warehouses/Index', compact('warehouses'));
    }

    public function createWeb()
    {
        Gate::authorize('create_warehouses', Warehouse::class);
        return Inertia::render('inventory/warehouses/CreateEdit');
    }

    public function storeWeb(Request $request)
    {
        Gate::authorize('create_warehouses', Warehouse::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
        ]);

        $validated['company_id'] = 1;

        Warehouse::create($validated);

        return redirect()->route('inventory.warehouses.index')->with('success', 'Almacén creado correctamente');
    }

    public function editWeb(Warehouse $warehouse)
    {
        Gate::authorize('update_warehouses', $warehouse);
        return Inertia::render('inventory/warehouses/CreateEdit', compact('warehouse'));
    }

    public function updateWeb(Request $request, Warehouse $warehouse)
    {
        Gate::authorize('update_warehouses', $warehouse);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
        ]);

        $warehouse->update($validated);

        return redirect()->back()->with('success', 'Almacén actualizado correctamente');
    }

    public function destroyWeb(Warehouse $warehouse)
    {
        Gate::authorize('delete_warehouses', $warehouse);

        if ($warehouse->inventories()->exists()) {
            return redirect()->back()->with('error', 'No se puede eliminar el almacén porque tiene productos asociados.');
        }

        $warehouse->delete();

        return redirect()->route('inventory.warehouses.index')->with('success', 'Almacén eliminado correctamente');
    }

    public function massDestroy(Request $request)
    {
        Gate::authorize('delete_warehouses', Warehouse::class);

        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:warehouses,id',
        ]);

        $count = 0;
        foreach ($request->ids as $id) {
            $warehouse = Warehouse::find($id);
            if (!$warehouse->inventories()->exists()) {
                $warehouse->delete();
                $count++;
            }
        }

        if ($count < count($request->ids)) {
            return redirect()->route('inventory.warehouses.index')->with('warning', "Se eliminaron {$count} almacenes. Algunos no pudieron ser eliminados porque tienen productos asociados.");
        }

        return redirect()->route('inventory.warehouses.index')->with('success', 'Almacenes eliminados correctamente');
    }
}

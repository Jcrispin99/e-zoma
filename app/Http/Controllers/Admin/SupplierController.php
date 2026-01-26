<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\Request;
use App\Models\Identity;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class SupplierController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        Gate::authorize('read_suppliers', Supplier::class);
        return view('admin.suppliers.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        Gate::authorize('create_suppliers', Supplier::class);
        $identities = Identity::all();
        return view('admin.suppliers.create', compact('identities'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Gate::authorize('create_suppliers', Supplier::class);
        $data = $request->validate([
            'identity_id' => 'required | exists:identities,id',
            'document_number' => 'required | string | max:20 | unique:suppliers,document_number',
            'name' => 'required | string | max:255',
            'address' => 'nullable | string | max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable | string | max:20',
        ]);
        $supplier = Supplier::create($data);

        session()->flash('swalt', [
            'icon' => 'success',
            'title' => 'Bien hecho',
            'text' => 'Proveedor creado con éxito',
        ]);

        return redirect()->route('admin.suppliers.edit', $supplier);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Supplier $supplier)
    {
        Gate::authorize('update_suppliers', $supplier);
        $identities = Identity::all();
        return view('admin.suppliers.edit', compact('supplier', 'identities'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Supplier $supplier)
    {
        Gate::authorize('update_suppliers', $supplier);
        $data = $request->validate([
            'identity_id' => 'required | exists:identities,id',
            'document_number' => 'required | string | max:20 | unique:suppliers,document_number,' . $supplier->id,
            'name' => 'required | string | max:255',
            'address' => 'nullable | string | max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable | string | max:20',
        ]);
        $supplier->update($data);

        session()->flash('swalt', [
            'icon' => 'success',
            'title' => 'Bien hecho',
            'text' => 'Proveedor actualizado con éxito',
        ]);

        return redirect()->route('admin.suppliers.edit', $supplier);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Supplier $supplier)
    {
        Gate::authorize('delete_suppliers', $supplier);
        if ($supplier->purchasesOrder()->exists() || $supplier->purchases()->exists()) {
            session()->flash('swalt', [
                'icon' => 'error',
                'title' => 'Error',
                'text' => 'No se puede eliminar el proveedor porque tiene pedidos de compra o compras asociadas',
            ]);
            return redirect()->route('admin.suppliers.index');
        }
        $supplier->delete();
        session()->flash('swalt', [
            'icon' => 'success',
            'title' => 'Bien hecho',
            'text' => 'Proveedor eliminado con éxito',
        ]);
        return redirect()->route('admin.suppliers.index');
    }

    public function indexWeb(Request $request)
    {
        Gate::authorize('read_suppliers', Supplier::class);

        $query = Supplier::with('identity');

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('document_number', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $suppliers = $query->latest()->paginate(80)->withQueryString();

        return Inertia::render('purchases/suppliers/Index', [
            'suppliers' => $suppliers,
            'filters' => $request->only(['search'])
        ]);
    }

    public function createWeb()
    {
        Gate::authorize('create_suppliers', Supplier::class);
        $identities = Identity::all();
        return Inertia::render('purchases/suppliers/CreateEdit', [
            'identities' => $identities
        ]);
    }

    public function editWeb(Supplier $supplier)
    {
        Gate::authorize('update_suppliers', $supplier);
        $identities = Identity::all();
        return Inertia::render('purchases/suppliers/CreateEdit', [
            'supplier' => $supplier,
            'identities' => $identities
        ]);
    }

    public function storeWeb(Request $request)
    {
        Gate::authorize('create_suppliers', Supplier::class);
        $data = $request->validate([
            'identity_id' => 'required | exists:identities,id',
            'document_number' => 'required | string | max:20 | unique:suppliers,document_number',
            'name' => 'required | string | max:255',
            'address' => 'nullable | string | max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable | string | max:20',
        ]);
        Supplier::create($data);

        return redirect()->route('purchases.suppliers.index')->with('success', 'Proveedor creado con éxito');
    }

    public function updateWeb(Request $request, Supplier $supplier)
    {
        Gate::authorize('update_suppliers', $supplier);
        $data = $request->validate([
            'identity_id' => 'required | exists:identities,id',
            'document_number' => 'required | string | max:20 | unique:suppliers,document_number,' . $supplier->id,
            'name' => 'required | string | max:255',
            'address' => 'nullable | string | max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable | string | max:20',
        ]);
        $supplier->update($data);

        return redirect()->back()->with('success', 'Proveedor actualizado con éxito');
    }

    public function destroyWeb(Supplier $supplier)
    {
        Gate::authorize('delete_suppliers', $supplier);
        if ($supplier->purchasesOrder()->exists() || $supplier->purchases()->exists()) {
            return redirect()->back()->with('error', 'No se puede eliminar porque tiene registros asociados');
        }
        $supplier->delete();
        return redirect()->route('purchases.suppliers.index')->with('success', 'Proveedor eliminado correctamente');
    }

    public function massDestroyWeb(Request $request)
    {
        Gate::authorize('delete_suppliers', Supplier::class);
        $ids = $request->input('ids');
        if (empty($ids)) {
            return redirect()->back()->with('error', 'No se seleccionaron registros.');
        }

        $suppliersWithRelations = Supplier::whereIn('id', $ids)
            ->where(function ($query) {
                $query->has('purchasesOrder')->orHas('purchases');
            })->count();

        if ($suppliersWithRelations > 0) {
            return redirect()->back()->with('error', 'Algunos proveedores tienen registros asociados y no se pueden eliminar.');
        }

        Supplier::whereIn('id', $ids)->delete();
        return redirect()->route('purchases.suppliers.index')->with('success', 'Proveedores eliminados correctamente');
    }
}

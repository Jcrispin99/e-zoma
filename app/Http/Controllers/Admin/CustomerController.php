<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use App\Models\Identity;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        Gate::authorize('read_customers', Customer::class);
        return view('admin.customers.index');
    }

    public function indexWeb(Request $request)
    {
        // Gate::authorize('read_customers', Customer::class);

        $query = Customer::with('identity');

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('document_number', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $customers = $query->latest()->paginate(80)->withQueryString();

        return Inertia::render('sales/customers/Index', [
            'customers' => $customers,
            'filters' => $request->only(['search'])
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        Gate::authorize('create_customers', Customer::class);
        $identities = Identity::all();
        return view('admin.customers.create', compact('identities'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Gate::authorize('create_customers', Customer::class);
        $data = $request->validate([
            'identity_id' => 'required | exists:identities,id',
            'document_number' => 'required | string | max:20 | unique:customers,document_number',
            'name' => 'required | string | max:255',
            'address' => 'nullable | string | max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable | string | max:20',
        ]);
        $customer = Customer::create($data);

        session()->flash('swalt', [
            'icon' => 'success',
            'title' => 'Bien hecho',
            'text' => 'Cliente creado con éxito',
        ]);

        return redirect()->route('admin.customers.edit', $customer);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Customer $customer)
    {
        Gate::authorize('update_customers', $customer);
        $identities = Identity::all();
        return view('admin.customers.edit', compact('customer', 'identities'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Customer $customer)
    {
        Gate::authorize('update_customers', $customer);
        $data = $request->validate([
            'identity_id' => 'required | exists:identities,id',
            'document_number' => 'required | string | max:20 | unique:customers,document_number,' . $customer->id,
            'name' => 'required | string | max:255',
            'address' => 'nullable | string | max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable | string | max:20',
        ]);
        $customer->update($data);

        session()->flash('swalt', [
            'icon' => 'success',
            'title' => 'Bien hecho',
            'text' => 'Cliente actualizado con éxito',
        ]);

        return redirect()->route('admin.customers.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Customer $customer)
    {
        Gate::authorize('delete_customers', $customer);
        if ($customer->quotes()->exists() || $customer->sales()->exists()) {
            session()->flash('swalt', [
                'icon' => 'error',
                'title' => 'Error',
                'text' => 'No se puede eliminar el cliente porque tiene cotizaciones o ventas asociadas',
            ]);
            return redirect()->route('admin.customers.index');
        }
        $customer->delete();
        session()->flash('swalt', [
            'icon' => 'success',
            'title' => 'Bien hecho',
            'text' => 'Cliente eliminado con éxito',
        ]);
        return redirect()->route('admin.customers.index');
    }
}

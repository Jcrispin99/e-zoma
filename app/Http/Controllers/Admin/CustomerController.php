<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use App\Models\Identity;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('admin.customers.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $identities = Identity::all();
        return view('admin.customers.create', compact('identities'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
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
        $identities = Identity::all();
        return view('admin.customers.edit', compact('customer', 'identities'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Customer $customer)
    {
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

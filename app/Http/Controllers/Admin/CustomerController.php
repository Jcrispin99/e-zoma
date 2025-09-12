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

        return redirect()->route('admin.customers.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Customer $customer)
    {
        return view('admin.customers.edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Customer $customer) {}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Customer $customer)
    {
        //
    }
}
